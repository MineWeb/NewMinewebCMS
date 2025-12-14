<?php
declare(strict_types=1);

namespace App\Service\Package;

use App\Service\HttpService;
use App\Service\Package\Filesystem\FilesystemService;
use App\Service\Package\Manifest\ManifestLoader;
use App\Service\Package\Requirement\RequirementChecker;
use App\Service\Package\Source\GitHubSource;
use App\Service\Package\Transaction\DirectorySwapTransaction;
use App\Service\Package\Transaction\FileBackupTransaction;
use App\Service\PermissionSynchronizer;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Migrations\Migrations;
use Throwable;
use ZipArchive;

final class PackageManager
{
    use LocatorAwareTrait;

    public function __construct(
        private readonly HttpService $http,
        private readonly FilesystemService $fs,
        private readonly GitHubSource $github,
        private readonly ManifestLoader $manifests,
        private readonly PermissionSynchronizer $permissionSync,
        private readonly RequirementChecker $requirements,
        private readonly UpdateStateStore $updateState,
    ) {
    }

    public function cmsCurrentVersion(): string
    {
        $manifestPath = ROOT . DS . 'manifest.json';
        if (is_file($manifestPath)) {
            $raw = file_get_contents($manifestPath);
            if (is_string($raw) && $raw !== '') {
                $decoded = json_decode($raw, true);
                if (is_array($decoded) && isset($decoded['version']) && is_string($decoded['version'])) {
                    return trim($decoded['version']) ?: '0.0.0';
                }
            }
        }

        $versionFile = ROOT . DS . 'VERSION';
        if (is_file($versionFile)) {
            $v = trim((string)file_get_contents($versionFile));

            return $v !== '' ? $v : '0.0.0';
        }

        return '0.0.0';
    }

    public function cmsLatestVersion(): ?string
    {
        $cfg = (array)Configure::read('Update.cms', []);
        $repo = (string)($cfg['repository'] ?? '');
        if ($repo === '') {
            return null;
        }

        $cacheKey = 'cms_latest_version';
        $cached = Cache::read($cacheKey, 'default');
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $res = $this->github->fetchLatestRelease($repo);
        $status = (int)($res['status'] ?? 0);
        $body = (string)($res['body'] ?? '');

        if ($status < 200 || $status >= 300 || $body === '') {
            return null;
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            return null;
        }

        $tag = (string)($json['tag_name'] ?? $json['name'] ?? '');
        $tag = ltrim($tag, 'v');
        $tag = trim($tag);

        if ($tag === '') {
            return null;
        }

        Cache::write($cacheKey, $tag, 'default');

        return $tag;
    }

    public function clearCmsLatestCache(): void
    {
        Cache::delete('cms_latest_version', 'default');
    }

    public function prepareCmsUpdate(): void
    {
        $current = $this->cmsCurrentVersion();
        $latest = $this->cmsLatestVersion();

        if ($latest === null || version_compare($current, $latest, '>=')) {
            $this->updateState->clear();

            return;
        }

        $cfg = (array)Configure::read('Update.cms', []);
        $repo = (string)($cfg['repository'] ?? '');
        $assetName = (string)($cfg['asset'] ?? '');

        if ($repo === '' || $assetName === '') {
            throw new PackageException('UPDATE__FAILED');
        }

        $releaseRes = $this->github->fetchLatestRelease($repo);
        $status = (int)($releaseRes['status'] ?? 0);
        $body = (string)($releaseRes['body'] ?? '');
        if ($status < 200 || $status >= 300 || $body === '') {
            throw new PackageException('UPDATE__FAILED');
        }

        $release = json_decode($body, true);
        if (!is_array($release) || !isset($release['assets']) || !is_array($release['assets'])) {
            throw new PackageException('UPDATE__FAILED');
        }

        $assetUrl = null;
        foreach ($release['assets'] as $asset) {
            if (!is_array($asset)) {
                continue;
            }
            if (($asset['name'] ?? null) === $assetName && isset($asset['browser_download_url'])) {
                $assetUrl = (string)$asset['browser_download_url'];
                break;
            }
        }

        if ($assetUrl === null || $assetUrl === '') {
            throw new PackageException('UPDATE__FAILED');
        }

        $baseDir = ROOT . DS . 'tmp' . DS . 'update' . DS . 'cms' . DS . $latest;
        $zipPath = $baseDir . DS . 'package.zip';
        $extractDir = $baseDir . DS . 'extract';

        $this->fs->ensureDir($baseDir);
        $this->fs->deleteDir($extractDir);
        $this->fs->ensureDir($extractDir);

        $dl = $this->http->get($assetUrl, ['headers' => $this->github->githubHeaders()]);
        $dlStatus = (int)($dl['status'] ?? 0);
        $dlBody = (string)($dl['body'] ?? '');

        if ($dlStatus < 200 || $dlStatus >= 300 || $dlBody === '') {
            throw new PackageException('UPDATE__FAILED');
        }

        file_put_contents($zipPath, $dlBody);

        $root = $this->extractZip($zipPath, $extractDir);

        $manifestPath = rtrim($root, DS) . DS . 'manifest.json';
        if (!is_file($manifestPath)) {
            throw new PackageException('UPDATE__FAILED');
        }

        $this->updateState->write([
            'type' => 'cms',
            'version' => $latest,
            'zip' => $zipPath,
            'root' => $root,
            'preparedAt' => time(),
        ]);
    }

    public function applyCmsUpdate(): void
    {
        $state = $this->updateState->read();
        if (!is_array($state) || ($state['type'] ?? null) !== 'cms') {
            throw new PackageException('UPDATE__FAILED');
        }

        $root = (string)($state['root'] ?? '');
        $version = (string)($state['version'] ?? '');

        if ($root === '' || $version === '' || !is_dir($root)) {
            $this->updateState->clear();
            throw new PackageException('UPDATE__FAILED');
        }

        $cfg = (array)Configure::read('Update.cms', []);
        $preserve = (array)($cfg['preserve'] ?? []);

        $backupDir = ROOT . DS . 'tmp' . DS . 'update' . DS . 'cms' . DS . $version . DS . 'backup-' . date('YmdHis');
        $this->fs->ensureDir($backupDir);

        $tx = new FileBackupTransaction($this->fs, ROOT, $backupDir);

        $sourceFiles = $this->fs->listFiles($root);

        $migrations = new Migrations();
        $beforeTarget = $this->latestMigrationVersion($migrations);

        try {
            foreach ($sourceFiles as $absoluteFile) {
                $relative = $this->fs->normalizeRelative($absoluteFile, $root);

                if ($this->fs->matchesAny($relative, $preserve)) {
                    continue;
                }

                $tx->backupTarget($relative);

                $dest = ROOT . DS . str_replace('/', DS, $relative);
                $this->fs->ensureDir(dirname($dest));

                if (!copy($absoluteFile, $dest)) {
                    throw new PackageException('UPDATE__FAILED');
                }
            }

            $migrations->migrate();

            Cache::clearAll();

            $tx->commit();
            $this->updateState->clear();

            $cleanupDir = dirname((string)($state['zip'] ?? ''));
            if ($cleanupDir !== '' && is_dir($cleanupDir)) {
                $this->fs->deleteDir($cleanupDir);
            }
        } catch (Throwable $e) {
            try {
                $migrations->rollback(['target' => $beforeTarget]);
            } catch (Throwable) {
            }

            $tx->rollback();
            $this->updateState->clear();

            throw $e instanceof PackageException ? $e : new PackageException('UPDATE__FAILED');
        }
    }

    public function installAddonFromMarket(string $slug, array $marketEntry): void
    {
        $repo = (string)($marketEntry['repo'] ?? '');
        if ($repo === '') {
            $repo = sprintf((string)Configure::read('Update.addons.repoPattern'), $slug);
        }

        $this->installOrUpdateAddon($slug, $repo, false);
    }

    public function updateAddon(string $slug): void
    {
        $targetDir = rtrim((string)Configure::read('Update.addons.folder'), DS) . DS . $slug;
        $manifestPath = $targetDir . DS . (string)Configure::read('Update.addons.manifest', 'manifest.json');

        $repo = '';
        $branch = (string)Configure::read('Update.addons.branch', '2.X');

        if (is_file($manifestPath)) {
            $local = $this->manifests->loadLocal($targetDir, basename($manifestPath));
            $repo = $local->repository;
            $branch = $local->branch ?: $branch;
        } else {
            $repo = sprintf((string)Configure::read('Update.addons.repoPattern'), $slug);
        }

        $this->installOrUpdateAddon($slug, $repo, true);
    }

    private function installOrUpdateAddon(string $slug, string $repository, bool $update): void
    {
        $branch = (string)Configure::read('Update.addons.branch', '2.X');
        $manifestFile = (string)Configure::read('Update.addons.manifest', 'manifest.json');

        $remote = $this->manifests->loadRemote($repository, $branch, $manifestFile);

        if ($remote->type !== PackageType::Addon || strcasecmp($remote->slug, $slug) !== 0) {
            throw new PackageException('ERROR__PLUGIN_NOT_VALID');
        }

        $targetDir = rtrim((string)Configure::read('Update.addons.folder'), DS) . DS . $slug;

        if ($update && is_dir($targetDir)) {
            $local = $this->manifests->loadLocal($targetDir, $manifestFile);

            if (version_compare($local->version, $remote->version, '>=')) {
                return;
            }
        }

        $this->requirements->assertSatisfied(
            $remote,
            fn(string $addonKey) => $this->resolveAddonVersion($addonKey),
            fn(string $themeKey) => $this->resolveThemeVersion($themeKey)
        );

        $staged = $this->downloadAndStageFromBranch($repository, $branch);

        $stagedManifest = $this->manifests->loadLocal($staged, $manifestFile);
        if ($stagedManifest->type !== PackageType::Addon || strcasecmp($stagedManifest->slug, $slug) !== 0) {
            $this->fs->deleteDir($staged);
            throw new PackageException('ERROR__PLUGIN_NOT_VALID');
        }

        $backupDir = ROOT . DS . 'tmp' . DS . 'update' . DS . 'backups' . DS . 'addons' . DS . $slug . DS . date('YmdHis');
        $tx = new DirectorySwapTransaction($this->fs, $targetDir, $staged, $backupDir);

        $migrations = new Migrations(['plugin' => $slug]);
        $beforeTarget = $this->latestMigrationVersion($migrations);

        $Plugins = $this->fetchTable('Plugins');

        try {
            $tx->apply();

            $migrations->migrate();

            $pluginEntity = $Plugins->find()->where(['name' => $slug])->first();
            if ($pluginEntity === null) {
                $pluginEntity = $Plugins->newEmptyEntity();
                $pluginEntity->set('name', $slug);
                $pluginEntity->set('state', 1);
            }

            $pluginEntity->set('author', $remote->author);
            $pluginEntity->set('version', $remote->version);
            $Plugins->saveOrFail($pluginEntity);

            $defaults = (array)($remote->permissions['defaults'] ?? []);
            $this->permissionSync->applyDefaults($defaults);

            $allowed = array_merge(
                $this->permissionSync->corePermissions(),
                (array)($this->allInstalledAddonPermissions())
            );
            $this->permissionSync->refreshAllowedPermissions($allowed);

            Cache::clearAll();

            $tx->commit();
        } catch (Throwable $e) {
            try {
                $migrations->rollback(['target' => $beforeTarget]);
            } catch (Throwable) {
            }

            $tx->rollback();
            Cache::clearAll();

            throw $e instanceof PackageException ? $e : new PackageException('ERROR__INTERNAL_ERROR');
        }
    }

    public function uninstallAddon(string $slug): void
    {
        $targetDir = rtrim((string)Configure::read('Update.addons.folder'), DS) . DS . $slug;

        $migrations = new Migrations(['plugin' => $slug]);

        $Plugins = $this->fetchTable('Plugins');

        try {
            $migrations->rollback(['target' => 0]);
        } catch (Throwable) {
        }

        $entity = $Plugins->find()->where(['name' => $slug])->first();
        if ($entity) {
            $Plugins->delete($entity);
        }

        $this->fs->deleteDir($targetDir);

        $allowed = array_merge(
            $this->permissionSync->corePermissions(),
            (array)($this->allInstalledAddonPermissions())
        );
        $this->permissionSync->refreshAllowedPermissions($allowed);

        Cache::clearAll();
    }

    public function installOrUpdateTheme(string $slug, string $repository, bool $update): void
    {
        $branch = (string)Configure::read('Update.themes.branch', '2.X');
        $manifestFile = (string)Configure::read('Update.themes.manifest', 'manifest.json');

        $remote = $this->manifests->loadRemote($repository, $branch, $manifestFile);

        if ($remote->type !== PackageType::Theme || strcasecmp($remote->slug, $slug) !== 0) {
            throw new PackageException('THEME__ERROR_INSTALL_UNZIP');
        }

        $targetDir = rtrim((string)Configure::read('Update.themes.folder'), DS) . DS . $slug;

        if ($update && is_dir($targetDir)) {
            $local = $this->manifests->loadLocal($targetDir, $manifestFile);

            if (version_compare($local->version, $remote->version, '>=')) {
                return;
            }
        }

        $this->requirements->assertSatisfied(
            $remote,
            fn(string $addonKey) => $this->resolveAddonVersion($addonKey),
            fn(string $themeKey) => $this->resolveThemeVersion($themeKey)
        );

        $staged = $this->downloadAndStageFromBranch($repository, $branch);

        $stagedManifest = $this->manifests->loadLocal($staged, $manifestFile);
        if ($stagedManifest->type !== PackageType::Theme || strcasecmp($stagedManifest->slug, $slug) !== 0) {
            $this->fs->deleteDir($staged);
            throw new PackageException('THEME__ERROR_INSTALL_UNZIP');
        }

        $backupDir = ROOT . DS . 'tmp' . DS . 'update' . DS . 'backups' . DS . 'themes' . DS . $slug . DS . date('YmdHis');
        $tx = new DirectorySwapTransaction($this->fs, $targetDir, $staged, $backupDir);

        try {
            $tx->apply();

            Cache::clearAll();

            $tx->commit();
        } catch (Throwable $e) {
            $tx->rollback();
            Cache::clearAll();

            throw $e instanceof PackageException ? $e : new PackageException('THEME__ERROR_INSTALL_UNZIP');
        }
    }

    public function uninstallTheme(string $slug): void
    {
        $targetDir = rtrim((string)Configure::read('Update.themes.folder'), DS) . DS . $slug;
        if (is_dir($targetDir)) {
            $this->fs->deleteDir($targetDir);
            Cache::clearAll();
        }
    }

    private function downloadAndStageFromBranch(string $repository, string $branch): string
    {
        $zipUrl = $this->github->branchZipUrl($repository, $branch);

        $tmpBase = ROOT . DS . 'tmp' . DS . 'update' . DS . 'downloads' . DS . sha1($repository . '|' . $branch . '|' . microtime(true));
        $zipPath = $tmpBase . DS . 'package.zip';
        $extractDir = $tmpBase . DS . 'extract';

        $this->fs->ensureDir($tmpBase);
        $this->fs->ensureDir($extractDir);

        $res = $this->http->get($zipUrl, ['headers' => $this->github->githubHeaders()]);
        $status = (int)($res['status'] ?? 0);
        $body = (string)($res['body'] ?? '');

        if ($status < 200 || $status >= 300 || $body === '') {
            throw new PackageException('ERROR__PACKAGE_CANT_BE_DOWNLOADED');
        }

        file_put_contents($zipPath, $body);

        return $this->extractZip($zipPath, $extractDir);
    }

    private function extractZip(string $zipFile, string $extractDir): string
    {
        $zip = new ZipArchive();
        $open = $zip->open($zipFile);
        if ($open !== true) {
            throw new PackageException('ERROR__PACKAGE_CANT_BE_DOWNLOADED');
        }

        if (!$zip->extractTo($extractDir)) {
            $zip->close();
            throw new PackageException('ERROR__PACKAGE_CANT_BE_DOWNLOADED');
        }

        $zip->close();

        $entries = array_values(array_filter(scandir($extractDir) ?: [], static fn($v) => $v !== '.' && $v !== '..'));
        if (count($entries) === 1) {
            $single = $extractDir . DS . $entries[0];
            if (is_dir($single)) {
                return $single;
            }
        }

        return $extractDir;
    }

    private function latestMigrationVersion(Migrations $migrations): int
    {
        try {
            $status = $migrations->status();
        } catch (Throwable) {
            return 0;
        }

        $max = 0;
        foreach ($status as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (($row['status'] ?? null) !== 'up') {
                continue;
            }
            $v = (int)($row['version'] ?? 0);
            if ($v > $max) {
                $max = $v;
            }
        }

        return $max;
    }

    private function resolveAddonVersion(string $addonKey): string
    {
        $addonKey = strtolower(trim($addonKey));
        if ($addonKey === '') {
            return '';
        }

        $Plugins = $this->fetchTable('Plugins');
        $rows = $Plugins->find()->select(['name'])->all();

        $addonsFolder = (string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons');
        $manifestFile = (string)Configure::read('Update.addons.manifest', 'manifest.json');

        foreach ($rows as $row) {
            $slug = (string)$row->get('name');
            if ($slug === '') {
                continue;
            }

            $dir = rtrim($addonsFolder, DS) . DS . $slug;
            $manifest = $dir . DS . $manifestFile;

            if (!is_file($manifest)) {
                continue;
            }

            try {
                $m = $this->manifests->loadLocal($dir, $manifestFile);
            } catch (Throwable) {
                continue;
            }

            if (strtolower($m->id()) === $addonKey || strtolower($m->slug) === $addonKey) {
                return $m->version;
            }
        }

        return '';
    }

    private function resolveThemeVersion(string $themeKey): string
    {
        $themeKey = strtolower(trim($themeKey));
        if ($themeKey === '') {
            return '';
        }

        $themesDir = rtrim((string)Configure::read('Update.themes.folder'), DS);
        $manifestFile = (string)Configure::read('Update.themes.manifest', 'manifest.json');

        $entries = scandir($themesDir) ?: [];

        foreach ($entries as $slug) {
            if (!is_string($slug) || $slug === '.' || $slug === '..') {
                continue;
            }

            $dir = $themesDir . DS . $slug;
            if (!is_dir($dir)) {
                continue;
            }

            try {
                $m = $this->manifests->loadLocal($dir, $manifestFile);
            } catch (Throwable) {
                continue;
            }

            if (strtolower($m->id()) === $themeKey || strtolower($m->slug) === $themeKey) {
                return $m->version;
            }
        }

        return '';
    }

    private function allInstalledAddonPermissions(): array
    {
        $out = [];

        $Plugins = $this->fetchTable('Plugins');
        $rows = $Plugins->find()->select(['name'])->all();

        $addonsFolder = (string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons');
        $manifestFile = (string)Configure::read('Update.addons.manifest', 'manifest.json');

        foreach ($rows as $row) {
            $slug = (string)$row->get('name');
            if ($slug === '') {
                continue;
            }

            $dir = rtrim($addonsFolder, DS) . DS . $slug;
            $manifest = $dir . DS . $manifestFile;

            if (!is_file($manifest)) {
                continue;
            }

            try {
                $m = $this->manifests->loadLocal($dir, $manifestFile);
            } catch (Throwable) {
                continue;
            }

            $available = (array)($m->permissions['available'] ?? []);
            foreach ($available as $p) {
                $p = trim((string)$p);
                if ($p !== '') {
                    $out[] = $p;
                }
            }
        }

        $out = array_values(array_unique($out));
        sort($out);

        return $out;
    }
}
