<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Http\ServerRequest;
use Cake\Log\Log;
use Cake\Routing\Router;
use Exception;
use PharIo\Version\Version;
use PharIo\Version\VersionConstraintParser;
use ZipArchive;

class ThemeComponent extends Component
{
    public string $themesFolder;
    private array $themesAvailable = [];
    private array $themesInstalled = [];
    private array $alreadyCheckValid = [];
    private string $reference = 'https://raw.githubusercontent.com/MineWeb/mineweb.org/gh-pages/market/themes.json';

    private $controller;
    private $EyPlugin;

    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->themesFolder = ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'Themes';
        parent::__construct($registry, $config);
    }

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->controller = $this->_registry->getController();
        $this->controller->set('Theme', $this);

        $this->EyPlugin = $this->controller->EyPlugin ?? null;
    }

    public function displayAvailableUpdate(): ?string
    {
        $themes = $this->getThemesInstalled(true);
        if (!empty((array)$themes)) {
            foreach ($themes as $value) {
                if (isset($value->lastVersion) && $value->version !== $value->lastVersion) {
                    $this->Lang = $this->controller->Lang;
                    return '<div class="alert alert-secondary">'
                        . $this->Lang->get('UPDATE__AVAILABLE_TYPE_THEME') . ' '
                        . $this->Lang->get('UPDATE__AVAILABLE') . ' '
                        . $this->Lang->get('UPDATE__THEME')
                        . '<a href="'
                        . Router::url(['prefix' => 'Admin', 'controller' => 'Theme', 'action' => 'index'])
                        . '" style="margin-top: -6px;" class="btn float-right">'
                        . $this->Lang->get('GLOBAL__UPDATE_LOOK')
                        . '</a></div>';
                }
            }
        }

        return null;
    }

    public function getThemesInstalled(bool $api = true): object
    {
        if (!empty($this->themesInstalled[$api])) {
            return $this->themesInstalled[$api];
        }

        $dir = $this->themesFolder;
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $themes = scandir($dir);
        if ($themes === false) {
            Log::error('Unable to scan theme folder.');
            return $this->themesInstalled[$api] = (object)[];
        }

        $themesList = (object)[];
        $bypassedFiles = ['.', '..', '.DS_Store', '__MACOSX', '.gitkeep'];

        if ($api) {
            $this->getThemesOnAPI();
        }

        foreach ($themes as $slug) {
            if (in_array($slug, $bypassedFiles, true)) {
                continue;
            }

            $config = $this->getThemeConfig($slug);
            if (empty($config) || !isset($config->slug)) {
                continue;
            }

            $id = strtolower($config->author . '.' . $config->slug);
            $themesList->$id = $config;

            $checkSupported = $this->checkSupported($slug);
            $themesList->$id->supported = empty($checkSupported);
            $themesList->$id->supportedErrors = $checkSupported;
            $themesList->$id->valid = $this->isValid($slug);

            $themeFromApi = $this->getThemeFromAPI($config->slug);
            if ($themeFromApi && isset($themeFromApi['version'])) {
                $themesList->$id->lastVersion = $themeFromApi['version'];
            }
        }

        return $this->themesInstalled[$api] = $themesList;
    }

    public function getThemesOnAPI(bool $all = true, bool $deleteInstalledThemes = false): array
    {
        $type = $all ? 'all' : 'free';
        if (!empty($this->themesAvailable[$type])) {
            return $this->themesAvailable[$type];
        }

        $themesList = @json_decode($this->controller->sendGetRequest($this->reference), true);

        $themes = [];
        if ($themesList) {
            $freeThemes = [];
            foreach ($themesList as $theme) {
                if (!empty($theme['free'])) {
                    $freeThemes[] = $theme;
                } elseif ($all) {
                    $themes[] = $theme;
                }
            }
            $repos = array_column($freeThemes, 'repo');
            $th = $this->getThemesFromRepoNames($repos);
            if ($th) {
                foreach ($th as $t) {
                    $t['free'] = true;
                    $themes[] = $t;
                }
            }
        }

        if ($deleteInstalledThemes) {
            $installed = $this->getThemesInstalled();
            $themeInstalledID = [];
            foreach ($installed as $themeInstalled) {
                $themeInstalledID[] = strtolower($themeInstalled->slug);
            }
            foreach ($themes as $key => $theme) {
                if (isset($theme['slug']) && in_array(strtolower($theme['slug']), $themeInstalledID, true)) {
                    unset($themes[$key]);
                }
            }
        }

        $this->themesAvailable[$type] = $themes;
        return $themes;
    }

    private function getThemeFromRepoName(string $repo)
    {
        $configUrl = 'https://raw.githubusercontent.com/' . $repo . '/master/Config/config.json';
        $config = @json_decode($this->controller->sendGetRequest($configUrl), true);
        if (!$config) {
            return false;
        }
        $config['repo'] = $repo;
        return $config;
    }

    private function getThemesFromRepoNames(array $repos): array
    {
        $urls = [];
        foreach ($repos as $repo) {
            $urls[] = 'https://raw.githubusercontent.com/' . $repo . '/master/Config/config.json';
        }
        $result = $this->controller->sendMultipleGetRequests($urls);
        $results = [];
        $i = 0;
        foreach ($result as $val) {
            $json = json_decode($val, true);
            if ($json === null) {
                $i++;
                continue;
            }
            $json['repo'] = $repos[$i] ?? null;
            $results[] = $json;
            $i++;
        }
        return $results;
    }

    public function getThemeConfig(string $slug, bool $array = false)
    {
        $path = $this->getPath($slug) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.json';
        if (strtolower($slug) === 'default' || !file_exists($path)) {
            $configPath = ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'theme.default.json';
            $config = @json_decode(@file_get_contents($configPath), $array);
            if ($array) {
                return ['slider' => true, 'configurations' => $config];
            }
            return (object)['slider' => true, 'configurations' => $config];
        }
        return @json_decode(@file_get_contents($path), $array);
    }

    public function getPath(string $slug): string
    {
        return $this->themesFolder . DIRECTORY_SEPARATOR . $slug;
    }

    public function checkSupported(string $slug): array
    {
        $config = $this->getThemeConfig($slug);
        $supported = is_object($config) && isset($config->supported) && !empty($config->supported) ? $config->supported : null;
        $errors = [];

        $versionParser = new VersionConstraintParser();
        if (is_array($supported)) {
            foreach ($supported as $type => $version) {
                if ($type === 'CMS') {
                    $versionToCompare = trim((string)@file_get_contents(ROOT . DIRECTORY_SEPARATOR . 'VERSION'));
                } else {
                    if (!$this->EyPlugin) {
                        continue;
                    }
                    $search = $this->EyPlugin->findPlugin('id', $type);
                    if (empty($search)) {
                        continue;
                    }
                    $versionToCompare = $search->version;
                }

                try {
                    $neededVersion = $versionParser->parse($version);
                } catch (Exception $e) {
                    $errors[$type] = $e->getMessage();
                    continue;
                }

                try {
                    if (!$neededVersion->complies(new Version($versionToCompare))) {
                        $errors[$type] = $version;
                    }
                } catch (Exception $exception) {
                    if (isset($search)) {
                        Log::error('Theme (' . $slug . ') check supported: Plugin (' . $search->slug . ') invalid version: ' . $versionToCompare);
                    } else {
                        Log::error('Theme (' . $slug . ') check supported: Invalid version : ' . $versionToCompare . ' (' . $type . ' => ' . $version . ')');
                    }
                }
            }
        }

        return $errors;
    }

    private function isValid(string $slug): bool
    {
        $slugUc = ucfirst($slug);
        $file = $this->themesFolder . DIRECTORY_SEPARATOR . $slugUc;

        if (isset($this->alreadyCheckValid[$slugUc])) {
            return $this->alreadyCheckValid[$slugUc];
        }

        if (!file_exists($file)) {
            Log::error('Themes folder : ' . $file . ' does not exist. Theme not valid.');
            $this->alreadyCheckValid[$slugUc] = false;
            return false;
        }
        if (!is_dir($file)) {
            Log::error('File : ' . $file . ' is not a folder. Theme not valid.');
            $this->alreadyCheckValid[$slugUc] = false;
            return false;
        }

        $neededFiles = ['Config/config.json'];
        foreach ($neededFiles as $value) {
            if (!file_exists($file . DIRECTORY_SEPARATOR . $value)) {
                Log::error('Theme "' . $slugUc . '" not valid. Missing "' . $file . DIRECTORY_SEPARATOR . $value . '"');
                $this->alreadyCheckValid[$slugUc] = false;
                return false;
            }
        }

        $needToBeJSON = ['Config/config.json'];
        foreach ($needToBeJSON as $value) {
            $content = @file_get_contents($file . DIRECTORY_SEPARATOR . $value);
            $json = json_decode((string)$content);
            if ($json === false || $json === null) {
                Log::error('Theme "' . $slugUc . '" not valid. "' . $file . DIRECTORY_SEPARATOR . $value . '" is not valid JSON.');
                $this->alreadyCheckValid[$slugUc] = false;
                return false;
            }
        }

        $configRaw = @file_get_contents($file . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.json');
        $config = json_decode((string)$configRaw, true);
        $needConfigKey = [
            'name' => 'string',
            'slug' => 'string',
            'author' => 'string',
            'version' => 'string',
            'configurations' => 'array',
            'supported' => 'array',
        ];

        foreach ($needConfigKey as $keyName => $type) {
            $keyParts = explode('-', $keyName);
            if (is_array($keyParts) && count($keyParts) > 1) {
                $configKey = $config;
                $multi = true;
                foreach ($keyParts as $v) {
                    if (is_array($configKey) && array_key_exists($v, $configKey)) {
                        $configKey = $configKey[$v];
                    } else {
                        $multi = false;
                        break;
                    }
                }
            } else {
                $configKey = $config[$keyParts[0]] ?? null;
                $multi = null;
            }

            $exists = ($multi === true) || ($multi === null && $configKey !== null);
            if ($exists) {
                $function = 'is_' . $type;
                if (!$function($configKey)) {
                    $keyLabel = is_array($keyParts)
                        ? '["' . implode('"]["', $keyParts) . '"]'
                        : $keyName;
                    Log::error('File : ' . $slugUc . ' is not a valid theme. Config key ' . $keyLabel . ' has wrong type (' . $type . ' required).');
                    $this->alreadyCheckValid[$slugUc] = false;
                    return false;
                }
            } else {
                $keyLabel = is_array($keyParts)
                    ? '["' . implode('"]["', $keyParts) . '"]'
                    : $keyName;
                Log::error('File : ' . $slugUc . ' is not a valid theme. Config key ' . $keyLabel . ' is missing.');
                $this->alreadyCheckValid[$slugUc] = false;
                return false;
            }
        }

        try {
            new Version($config['version']);
        } catch (Exception $e) {
            Log::error('File : ' . $slugUc . ' is not a valid theme. Version is not in a valid format.');
            $this->alreadyCheckValid[$slugUc] = false;
            return false;
        }

        return $this->alreadyCheckValid[$slugUc] = true;
    }

    private function getThemeFromAPI(string $slug)
    {
        if (isset($this->themesAvailable['all'])) {
            foreach ($this->themesAvailable['all'] as $theme) {
                if (isset($theme['slug']) && strtolower($theme['slug']) === strtolower($slug)) {
                    return $theme;
                }
            }
        }
        return false;
    }

    public function getVersion(string $slug)
    {
        $config = $this->getThemeConfig($slug);
        if (!$config) {
            return false;
        }
        return $config->version ?? null;
    }

    public function getCurrentTheme(): array
    {
        $configuredTheme = $this->controller->Configuration->getKey('theme');
        foreach ($this->getThemesInstalled(false) as $theme) {
            if ($configuredTheme === $theme->slug && $theme->valid) {
                return [$theme->slug, (array)$theme->configurations];
            }
        }
        return ['default', $this->getThemeConfig('default', true)['configurations']];
    }

    public function install(string $slug, bool $update = false)
    {
        $download = $this->download($slug);
        if ($download !== true) {
            return $download;
        }

        if ($update) {
            $oldConfigData = $this->getCustomData($slug);
            $oldConfig = $oldConfigData ? $oldConfigData[1] : [];
        }

        $macFolder = $this->themesFolder . DIRECTORY_SEPARATOR . '__MACOSX';
        if (file_exists($macFolder)) {
            @rmdir($macFolder);
        }

        if ($update) {
            $config = $this->getThemeConfig($slug, true);
            if (isset($config['configurations'])) {
                foreach ($config['configurations'] as $key => $value) {
                    if (isset($oldConfig[$key])) {
                        $config['configurations'][$key] = $oldConfig[$key];
                    }
                }
            }
            $this->setThemeConfig($slug, $config);

            $updateFilePath = $this->getPath($slug) . DIRECTORY_SEPARATOR . 'update.json';
            if (file_exists($updateFilePath)) {
                $updateFile = @json_decode($this->controller->sendGetRequest($updateFilePath));
                if ($updateFile) {
                    foreach ($updateFile as $type => $value) {
                        if ($type === 'delete') {
                            foreach ($value as $file) {
                                $filePath = $this->getPath($slug) . DIRECTORY_SEPARATOR . $file;
                                if (file_exists($filePath)) {
                                    @unlink($filePath);
                                }
                            }
                        }
                    }
                }
            }
        }

        return true;
    }

    private function download(string $slug)
    {
        $zipContent = $this->controller->sendGetRequest('https://github.com/MineWeb/Theme-' . $slug . '/archive/master.zip');
        if (!$zipContent) {
            return 'THEME__ERROR_INSTALL_DOWNLOAD_FAILED';
        }

        $zipFile = ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'theme-' . $slug . '.zip';
        $file = fopen($zipFile, 'w+');
        if (!$file || fwrite($file, $zipContent) === false) {
            if ($file) {
                fclose($file);
            }
            Log::error('Error when downloading theme, save file failed.');
            return 'THEME__ERROR_INSTALL_UNZIP';
        }
        fclose($file);

        $zip = new ZipArchive();
        $res = $zip->open($zipFile);
        if ($res !== true) {
            Log::error('Error when downloading theme, unable to open zip. CODE: ' . $res);
            return 'THEME__ERROR_INSTALL_UNZIP';
        }

        $themeDir = ROOT . DIRECTORY_SEPARATOR . 'templates' . DIRECTORY_SEPARATOR . 'Themed' . DIRECTORY_SEPARATOR . $slug;
        if (!file_exists($themeDir) && !mkdir($themeDir, 0755, true) && !is_dir($themeDir)) {
            return 'THEME__ERROR_INSTALL_UNZIP';
        }

        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $fileinfo = pathinfo($filename);
            $stat = $zip->statIndex($i);

            if ($fileinfo['basename'] === 'Theme-' . $slug . '-master') {
                continue;
            }

            $target = 'zip://' . $zipFile . '#' . $filename;
            $relative = substr($filename, strlen('Theme-' . $slug . '-master'));
            $dest = $themeDir . $relative;

            if ($stat['size'] === 0 && strpos($filename, '.') === false) {
                if (!file_exists($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
                    return 'THEME__ERROR_INSTALL_UNZIP';
                }
                continue;
            }

            if (!copy($target, $dest)) {
                $zip->close();
                return 'THEME__ERROR_INSTALL_UNZIP';
            }
        }
        $zip->close();

        @unlink($zipFile);
        return true;
    }

    public function getCustomData(string $slug)
    {
        $config = [];
        if ($slug === 'default') {
            $config = json_decode((string)file_get_contents(ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'theme.default.json'), true);
            $themeName = 'Bootstrap';
        } else {
            $themesInstalled = $this->getThemesInstalled(false);
            foreach ($themesInstalled as $data) {
                if ($data->slug === $slug) {
                    $themeName = $data->name;
                    $config = $data->configurations;
                    break;
                }
            }
        }

        if (isset($config)) {
            $config = (array)$config;
        }

        return isset($themeName) ? [$themeName, $config] : false;
    }

    public function setThemeConfig(string $slug, array $config = [])
    {
        $path = $this->getPath($slug) . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.json';
        if (strtolower($slug) === 'default' || !file_exists($path)) {
            return @file_put_contents(
                ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'theme.default.json',
                json_encode($config)
            );
        }
        return @file_put_contents($path, json_encode($config));
    }

    public function processCustomData(string $slug, ServerRequest $request): bool
    {
        if ($slug === 'default') {
            $data = json_encode($request->getData(), JSON_PRETTY_PRINT);
            $fp = @fopen(ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'theme.default.json', 'w+');
            if (!$fp) {
                Log::error('Unable to save theme config. File not writable.');
                return false;
            }
            fwrite($fp, $data);
            fclose($fp);
            return true;
        }

        $this->Util = $this->controller->Util;
        $this->Session = $this->controller->Session;
        $this->Lang = $this->controller->Lang;
        $this->Flash = $this->controller->Flash;

        $finded = false;
        $themesInstalled = $this->getThemesInstalled(false);
        foreach ($themesInstalled as $data) {
            if ($data->slug === $slug) {
                $finded = true;
                $config = $data->configurations;
                break;
            }
        }

        if (!$finded) {
            return false;
        }

        if ($request->getData('img_edit')) {
            $checkIfImageAlreadyUploaded = $request->getData('img-uploaded') !== null;
            if ($checkIfImageAlreadyUploaded) {
                $request = $request->withData('logo', Router::url('/') . 'img/uploads/' . $request->getData('img-uploaded'));
                $request = $request->withoutData('img-uploaded');
            } else {
                $isValidImg = $this->Util->isValidImage($request, ['png', 'jpg', 'jpeg']);

                if (!$isValidImg['status'] && ($isValidImg['msg'] ?? '') !== $this->Lang->get('FORM__EMPTY_IMG')) {
                    $this->Flash->error($isValidImg['msg']);
                    return false;
                }

                $infos = $isValidImg['infos'] ?? false;
                if ($infos) {
                    $urlImg = WWW_ROOT . 'img' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'theme_logo.' . $infos['extension'];

                    if (!$this->Util->uploadImage($request, $urlImg)) {
                        $this->Flash->error($this->Lang->get('FORM__ERROR_WHEN_UPLOAD'));
                        return false;
                    }

                    $request = $request->withData(
                        'logo',
                        Router::url('/') . 'img/uploads/theme_logo.' . $infos['extension']
                    );
                } else {
                    $request = $request->withData('logo', false);
                }
            }
        } else {
            $request = $request->withData('logo', $config->logo ?? null);
        }

        $jsonPath = $this->themesFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'config.json';
        $json = json_decode((string)file_get_contents($jsonPath));
        $json->configurations = $request->getData();

        $data = json_encode($json, JSON_PRETTY_PRINT);
        $fp = @fopen($jsonPath, 'w+');
        if (!$fp) {
            Log::error('Unable to save theme config. File not writable.');
            return false;
        }
        fwrite($fp, $data);
        fclose($fp);

        return true;
    }
}
