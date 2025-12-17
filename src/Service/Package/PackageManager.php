<?php
declare(strict_types=1);

namespace App\Service\Package;

use App\Service\HttpService;
use App\Service\Package\Filesystem\FilesystemService;
use App\Service\Package\Manifest\ManifestLoader;
use App\Service\Package\Manifest\PackageManifest;
use App\Service\Package\Market\MarketConfigService;
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
        private readonly LatestReleaseService $releases,
        private readonly MarketConfigService $markets,
    ) {
    }

    public function registerLocalAddon(string $slug): void
    {
        $slug = trim($slug);
        if ($slug === '') {
            throw new PackageException('ERROR__PLUGIN_NOT_VALID');
        }

        $addonsFolder = rtrim((string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons'), DS);
        if (!is_dir($addonsFolder)) {
            $alt = ROOT . DS . 'plugins' . DS . 'Addon';
            if (is_dir($alt)) {
                $addonsFolder = rtrim($alt, DS);
            }
        }

        $targetDir = $addonsFolder . DS . $slug;
        $manifestFile = (string)Configure::read('Update.addons.manifest', 'manifest.json');

        if (!is_dir($targetDir)) {
            throw new PackageException('ERROR__PLUGIN_NOT_VALID');
        }

        $local = $this->manifests->loadLocal($targetDir, $manifestFile);

        if ($local->type !== PackageType::Addon || strcasecmp($local->slug, $slug) !== 0) {
            throw new PackageException('ERROR__PLUGIN_NOT_VALID');
        }

        $migrations = new Migrations(['plugin' => $slug]);

        $this->postInstallAddon($slug, $local, $migrations);

        Cache::clearAll();
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

        $v = $this->releases->latestVersion($repo);
        if (!is_string($v) || $v === '') {
            return null;
        }

        Cache::write($cacheKey, $v, 'default');

        return $v;
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

    public function resolveRef(string $kind, string $repository, ?string $branchFallback = null): array
    {
        $kind = $this->normalizeKind($kind);

        $channel = (string)Configure::read('Update.' . $kind . '.channel', 'release');
        $branchFallback = $branchFallback ?? (string)Configure::read('Update.' . $kind . '.branch', '2.X');

        return $this->resolveRefWithDefaults($repository, $channel, $branchFallback);
    }

    public function installOrUpdateAddonFromMarket(string $slug, array $marketEntry, bool $update): void
    {
        [$repo, $channel, $ref] = $this->marketRepoChannelRef('addons', $slug, $marketEntry);

        $this->installOrUpdatePackageInternal('addons', $slug, $repo, $update, $channel, $ref);
    }

    public function installAddonFromMarket(string $slug, array $marketEntry): void
    {
        $this->installOrUpdateAddonFromMarket($slug, $marketEntry, false);
    }

    public function updateAddon(string $slug): void
    {
        $kind = 'addons';
        $slug = trim($slug);
        if ($slug === '') {
            throw new PackageException('ERROR__PLUGIN_NOT_VALID');
        }

        $targetDir = $this->packageDir($kind, $slug);
        $manifestFile = $this->manifestFile($kind);
        $manifestPath = $targetDir . DS . $manifestFile;

        $repo = '';
        $branch = (string)Configure::read('Update.addons.branch', '2.X');

        if (is_file($manifestPath)) {
            $local = $this->manifests->loadLocal($targetDir, $manifestFile);
            $repo = $local->repository;
            $branch = $local->branch ?: $branch;
        } else {
            $repo = sprintf((string)Configure::read('Update.addons.repoPattern'), $slug);
        }

        [$channel, $ref] = $this->resolveRef($kind, $repo, $branch);

        $this->installOrUpdatePackageInternal($kind, $slug, $repo, true, $channel, $ref);
    }

    public function installOrUpdateThemeFromMarket(string $slug, array $marketEntry, bool $update): void
    {
        [$repo, $channel, $ref] = $this->marketRepoChannelRef('themes', $slug, $marketEntry);

        $this->installOrUpdatePackageInternal('themes', $slug, $repo, $update, $channel, $ref);
    }

    public function installOrUpdateTheme(string $slug, string $repository, bool $update): void
    {
        [$channel, $ref] = $this->resolveRef('themes', $repository);

        $this->installOrUpdatePackageInternal('themes', $slug, $repository, $update, $channel, $ref);
    }

    public function uninstallAddon(string $slug): void
    {
        $this->uninstallPackageInternal('addons', $slug);
    }

    public function uninstallTheme(string $slug): void
    {
        $this->uninstallPackageInternal('themes', $slug);
    }

    private function installOrUpdatePackageInternal(string $kind, string $slug, string $repository, bool $update, string $channel, string $ref): void
    {
        $kind = $this->normalizeKind($kind);
        $slug = trim($slug);
        $repository = trim($repository);

        if ($slug === '' || $repository === '') {
            throw new PackageException($this->errorKeyInvalidPackage($kind));
        }

        $manifestFile = $this->manifestFile($kind);
        $targetDir = $this->packageDir($kind, $slug);

        $remote = $this->manifests->loadRemote($repository, $ref, $manifestFile);

        if ($remote->type !== $this->expectedType($kind) || strcasecmp($remote->slug, $slug) !== 0) {
            throw new PackageException($this->errorKeyInvalidPackage($kind));
        }

        if ($update && is_dir($targetDir)) {
            $local = $this->manifests->loadLocal($targetDir, $manifestFile);
            if (version_compare($local->version, $remote->version, '>=')) {
                return;
            }
        }

        $this->requirements->assertSatisfied(
            $remote,
            fn(string $addonKey) => $this->resolveInstalledVersion('addons', $addonKey),
            fn(string $themeKey) => $this->resolveInstalledVersion('themes', $themeKey),
        );

        $staged = $this->downloadAndStage($repository, $ref, $channel);

        try {
            $stagedManifest = $this->manifests->loadLocal($staged, $manifestFile);
        } catch (Throwable) {
            $this->fs->deleteDir($staged);
            throw new PackageException($this->errorKeyInvalidPackage($kind));
        }

        if ($stagedManifest->type !== $this->expectedType($kind) || strcasecmp($stagedManifest->slug, $slug) !== 0) {
            $this->fs->deleteDir($staged);
            throw new PackageException($this->errorKeyInvalidPackage($kind));
        }

        $backupDir = ROOT . DS . 'tmp' . DS . 'update' . DS . 'backups' . DS . $kind . DS . $slug . DS . date('YmdHis');
        $tx = new DirectorySwapTransaction($this->fs, $targetDir, $staged, $backupDir);

        $migrations = null;
        $beforeTarget = 0;

        if ($kind === 'addons') {
            $migrations = new Migrations(['plugin' => $slug]);
            $beforeTarget = $this->latestMigrationVersion($migrations);
        }

        try {
            $tx->apply();

            if ($kind === 'addons') {
                $this->postInstallAddon($slug, $remote, $migrations);
            }

            Cache::clearAll();

            $tx->commit();
        } catch (Throwable $e) {
            if ($kind === 'addons' && $migrations instanceof Migrations) {
                try {
                    $migrations->rollback(['target' => $beforeTarget]);
                } catch (Throwable) {
                }
            }

            $tx->rollback();
            Cache::clearAll();

            if ($e instanceof PackageException) {
                throw $e;
            }

            throw new PackageException($this->errorKeyInternal($kind));
        }
    }

    private function postInstallAddon(string $slug, PackageManifest $manifest, ?Migrations $migrations): void
    {
        if ($migrations instanceof Migrations) {
            $migrations->migrate();
            try {
                $migrations->seed();
            } catch (Throwable) {
            }
        }

        $Plugins = $this->fetchTable('Plugins');

        $pluginEntity = $Plugins->find()->where(['name' => $slug])->first();
        $isFirstInstall = $pluginEntity === null;

        if ($isFirstInstall) {
            $pluginEntity = $Plugins->newEmptyEntity();
            $pluginEntity->set('name', $slug);
            $pluginEntity->set('state', 1);
        }

        $pluginEntity->set('author', $manifest->author ?? '');
        $pluginEntity->set('version', $manifest->version ?? '');
        $Plugins->saveOrFail($pluginEntity);

        if ($isFirstInstall) {
            $defaults = (array)($manifest->permissions['defaults'] ?? []);
            $this->permissionSync->applyDefaults($defaults);
        }

        $allowed = array_merge(
            $this->permissionSync->corePermissions(),
            $this->allInstalledAddonPermissions(),
        );
        $this->permissionSync->refreshAllowedPermissions($allowed);
    }

    private function uninstallPackageInternal(string $kind, string $slug): void
    {
        $kind = $this->normalizeKind($kind);
        $slug = trim($slug);
        if ($slug === '') {
            return;
        }

        $targetDir = $this->packageDir($kind, $slug);

        if ($kind === 'addons') {
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
                (array)($this->allInstalledAddonPermissions()),
            );
            $this->permissionSync->refreshAllowedPermissions($allowed);

            Cache::clearAll();

            return;
        }

        if (is_dir($targetDir)) {
            $this->fs->deleteDir($targetDir);
            Cache::clearAll();
        }
    }

    private function marketRepoChannelRef(string $kind, string $slug, array $marketEntry): array
    {
        $kind = $this->normalizeKind($kind);

        $repo = $this->markets->computeRepository($kind, $slug, $marketEntry);

        $fetch = is_array($marketEntry['fetch'] ?? null) ? (array)$marketEntry['fetch'] : [];
        $channel = (string)($fetch['channel'] ?? '');
        $ref = (string)($fetch['ref'] ?? '');

        if (($channel === 'release' || $channel === 'branch') && $ref !== '') {
            return [$repo, $channel, $ref];
        }

        [$defaultChannel, $defaultBranch] = $this->markets->marketDefaults($kind, $marketEntry);

        [$fallbackChannel, $fallbackRef] = $this->resolveRefWithDefaults($repo, $defaultChannel, $defaultBranch);

        return [$repo, $fallbackChannel, $fallbackRef];
    }

    private function resolveRefWithDefaults(string $repository, string $channelDefault, string $branchDefault): array
    {
        $repository = trim($repository);
        $channelDefault = $channelDefault === 'branch' ? 'branch' : 'release';
        $branchDefault = trim($branchDefault) !== '' ? trim($branchDefault) : '2.X';

        if ($channelDefault === 'release') {
            $tag = $this->releases->latestTag($repository);
            if (is_string($tag) && $tag !== '') {
                return ['release', $tag];
            }
        }

        return ['branch', $branchDefault];
    }

    private function downloadAndStage(string $repository, string $ref, string $channel): string
    {
        $zipUrl = $channel === 'release'
            ? $this->github->tagZipUrl($repository, $ref)
            : $this->github->branchZipUrl($repository, $ref);

        $tmpBase = ROOT . DS . 'tmp' . DS . 'update' . DS . 'downloads' . DS . sha1($repository . '|' . $ref . '|' . $channel . '|' . microtime(true));
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

    private function resolveInstalledVersion(string $kind, string $key): string
    {
        $kind = $this->normalizeKind($kind);
        $key = strtolower(trim($key));
        if ($key === '') {
            return '';
        }

        if ($kind === 'addons') {
            return $this->resolveAddonVersion($key);
        }

        return $this->resolveThemeVersion($key);
    }

    private function resolveAddonVersion(string $addonKeyLower): string
    {
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

            if (strtolower($m->id()) === $addonKeyLower || strtolower($m->slug) === $addonKeyLower) {
                return $m->version;
            }
        }

        return '';
    }

    private function resolveThemeVersion(string $themeKeyLower): string
    {
        $themesDir = rtrim((string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes'), DS);
        $manifestFile = (string)Configure::read('Update.themes.manifest', 'manifest.json');

        if (!is_dir($themesDir)) {
            return '';
        }

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

            if (strtolower($m->id()) === $themeKeyLower || strtolower($m->slug) === $themeKeyLower) {
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

    private function normalizeKind(string $kind): string
    {
        $kind = strtolower(trim($kind));

        return $kind === 'themes' ? 'themes' : 'addons';
    }

    private function manifestFile(string $kind): string
    {
        $kind = $this->normalizeKind($kind);

        return (string)Configure::read('Update.' . $kind . '.manifest', 'manifest.json');
    }

    private function baseFolder(string $kind): string
    {
        $kind = $this->normalizeKind($kind);

        if ($kind === 'themes') {
            return (string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes');
        }

        return (string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons');
    }

    private function packageDir(string $kind, string $slug): string
    {
        $folder = rtrim($this->baseFolder($kind), DS);

        return $folder . DS . $slug;
    }

    private function expectedType(string $kind): PackageType
    {
        $kind = $this->normalizeKind($kind);

        return $kind === 'themes' ? PackageType::Theme : PackageType::Addon;
    }

    private function errorKeyInvalidPackage(string $kind): string
    {
        $kind = $this->normalizeKind($kind);

        return $kind === 'themes' ? 'THEME__ERROR_INSTALL_UNZIP' : 'ERROR__PLUGIN_NOT_VALID';
    }

    private function errorKeyInternal(string $kind): string
    {
        $kind = $this->normalizeKind($kind);

        return $kind === 'themes' ? 'THEME__ERROR_INSTALL_UNZIP' : 'ERROR__INTERNAL_ERROR';
    }
}
