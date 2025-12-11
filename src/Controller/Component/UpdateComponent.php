<?php
namespace App\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Log\Log;
use Cake\Routing\Router;
use Migrations\Migrations;
use Throwable;
use ZipArchive;

class UpdateComponent extends Component
{
    public array $components = ['Session', 'Configuration', 'Util'];

    public string $cmsVersion;
    public string $lastVersion;
    public string $errorUpdate;
    private array $bypassFiles = [
        '.DS_Store',
        '.htaccess',
        'empty',
        'app/Config/database.php',
        'config/secure',
        '__MACOSX',
        'config.json',
        'theme.default.json',
    ];
    private array $source = [
        'repo' => 'MineWebCMS',
        'owner' => 'MineWeb',
        'versionFile' => 'VERSION',
    ];
    private string $updateCacheFile;
    private $controller;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->controller = $this->_registry->getController();
        $this->controller->set('Update', $this);

        $this->updateCacheFile = ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'update';
        $this->errorUpdate = __('UPDATE__FAILED');

        $this->check();
    }

    private function check(): void
    {
        $versionFile = ROOT . DIRECTORY_SEPARATOR . $this->source['versionFile'];
        $this->cmsVersion = is_file($versionFile) ? trim((string)file_get_contents($versionFile)) : '0.0.0';

        $remoteVersion = null;
        $cacheExists = is_file($this->updateCacheFile);
        $cacheExpired = !($cacheExists && (filemtime($this->updateCacheFile) !== false)) || strtotime('+5 hours', filemtime($this->updateCacheFile)) < time();

        if (!$cacheExists || $cacheExpired) {
            $remoteVersion = $this->getLatestRelease();
            if ($remoteVersion) {
                file_put_contents($this->updateCacheFile, $remoteVersion);
            }
        }

        $cached = is_file($this->updateCacheFile) ? trim((string)file_get_contents($this->updateCacheFile)) : null;
        $this->lastVersion = $remoteVersion ?: ($cached ?: $this->cmsVersion);
    }

    private function getLatestRelease(): ?string
    {
        try {
            $url = "https://api.github.com/repos/{$this->source['owner']}/{$this->source['repo']}/releases/latest";
            $json = $this->controller->sendGetRequest($url);
            if ($json === '') {
                return null;
            }
            $release = json_decode($json);
            if (!is_object($release) || empty($release->name)) {
                return null;
            }
            return ltrim((string)$release->name, 'v');
        } catch (Throwable $e) {
            Log::error('[Update] Failed to fetch latest release: ' . $e->getMessage());
            return null;
        }
    }

    public function available(): string
    {
        if (version_compare($this->cmsVersion, $this->lastVersion, '<')) {
            $url = Router::url(['_name' => 'admin_update_index']);
            return "<div class='alert alert-secondary'>"
                . __('UPDATE__AVAILABLE_TYPE_CMS') . ' '
                . __('UPDATE__AVAILABLE') . ' '
                . __('UPDATE__CMS_VERSION') . ' : '
                . $this->cmsVersion . ', '
                . __('UPDATE__LAST_VERSION') . ' : '
                . $this->lastVersion . " "
                . "<a href='" . $url . "' style='margin-top: -6px;' class='btn float-right'>"
                . __('GLOBAL__UPDATE')
                . '</a>'
                . '</div>';
        }

        return '';
    }

    public function updateCMS(bool $componentUpdated = false): bool
    {
        if (!$this->lastVersion || $this->lastVersion === $this->cmsVersion) {
            return true;
        }

        $zipPath = ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $this->lastVersion . '.zip';
        if (!is_dir(dirname($zipPath))) {
            mkdir(dirname($zipPath), 0775, true);
        }

        if (!is_file($zipPath) && !$this->downloadUpdate($zipPath)) {
            return false;
        }

        $zip = new ZipArchive();
        if ($zip->open($zipPath) !== true) {
            $this->errorUpdate = __('UPDATE__FAILED');
            Log::error('[Update] Unable to open zip: ' . $zipPath);
            return false;
        }

        $rootPrefix = $this->source['repo'] . '-' . $this->lastVersion . '/';

        if (!$componentUpdated) {
            $componentInZip = $rootPrefix . 'src/Controller/Component/UpdateComponent.php';
            $newContent = $zip->getFromName($componentInZip);
            if ($newContent === false || $newContent === '') {
                $this->errorUpdate = __('UPDATE__FAILED');
                Log::error('[Update] UpdateComponent.php not found in archive: ' . $componentInZip);
                $zip->close();
                return false;
            }

            $target = ROOT . DIRECTORY_SEPARATOR . 'src' . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component' . DIRECTORY_SEPARATOR . 'UpdateComponent.php';
            if (file_put_contents($target, $newContent) === false) {
                $this->errorUpdate = __('UPDATE__FAILED_FILE', ['{FILE}' => $target]);
                Log::error('[Update] Failed to write updated UpdateComponent.php to ' . $target);
                $zip->close();
                return false;
            }

            $zip->close();
            return true;
        }

        $filesToUpdate = [];

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $entryName = $zip->getNameIndex($i);
            if ($entryName === false) {
                continue;
            }

            if (!str_starts_with($entryName, $rootPrefix)) {
                continue;
            }

            $relative = substr($entryName, strlen($rootPrefix));
            if ($relative === '' || str_ends_with($relative, '/')) {
                continue;
            }

            if (in_array($relative, $this->bypassFiles, true)) {
                continue;
            }

            $targetPath = ROOT . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relative);
            $targetDir = dirname($targetPath);
            if (!is_dir($targetDir)) {
                mkdir($targetDir, 0775, true);
            }

            if (file_exists($targetPath) && !is_writable($targetPath)) {
                $this->errorUpdate = __('UPDATE__FAILED_FILE', ['{FILE}' => $targetPath]);
                Log::error('[Update] File not writable: ' . $targetPath);
                $zip->close();
                return false;
            }

            $filesToUpdate[$i] = $targetPath;
        }

        Log::info('[Update] Start update to version ' . $this->lastVersion);

        foreach ($filesToUpdate as $index => $targetPath) {
            $content = $zip->getFromIndex($index);
            if ($content === false) {
                Log::error('[Update] Failed to read entry index ' . $index . ' for ' . $targetPath);
                continue;
            }

            if (file_put_contents($targetPath, $content) === false) {
                $this->errorUpdate = __('UPDATE__FAILED_FILE', ['{FILE}' => $targetPath]);
                Log::error('[Update] Failed to write file ' . $targetPath);
                $zip->close();
                return false;
            }

            Log::info('[Update] Updated file ' . $targetPath);
        }

        $zip->close();

        if (is_file($zipPath)) {
            @unlink($zipPath);
        }

        Cache::clearAll();

        return $this->updateDb();
    }

    private function downloadUpdate(string $zipPath): bool
    {
        try {
            $url = "https://github.com/{$this->source['owner']}/{$this->source['repo']}/archive/v{$this->lastVersion}.zip";
            $content = $this->controller->sendGetRequest($url);
            if ($content === '') {
                $this->errorUpdate = __('UPDATE__FAILED');
                Log::error('[Update] Empty content when downloading update zip');
                return false;
            }

            if (file_put_contents($zipPath, $content) === false) {
                $this->errorUpdate = __('UPDATE__FAILED');
                Log::error('[Update] Failed to write zip to ' . $zipPath);
                return false;
            }

            return true;
        } catch (Throwable $e) {
            $this->errorUpdate = __('UPDATE__FAILED');
            Log::error('[Update] Error while downloading update zip: ' . $e->getMessage());
            return false;
        }
    }

    public function updateDb(): bool
    {
        try {
            $migrations = new Migrations();
            $migrations->migrate();
            return true;
        } catch (Throwable $e) {
            $this->errorUpdate = __('UPDATE__FAILED');
            Log::error('[Update] Failed to run migrations: ' . $e->getMessage());
            return false;
        }
    }
}
