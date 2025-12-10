<?php
namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\Exception\MissingDatasourceConfigException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Exception;

class LangComponent extends Component
{
    public array $components = ['Cookie'];

    public string $langFolder;

    public array $languages = [];

    public array $lang = [];

    public string $mode = 'config';

    private $controller;

    private bool $dbAvailable = false;

    private bool $pluginsAvailable = false;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->langFolder = ROOT . DS . 'lang';

        if (!is_dir(ROOT . DS . 'logs' . DS . 'update' . DS . 'lang')) {
            mkdir(ROOT . DS . 'logs' . DS . 'update' . DS . 'lang', 0775, true);
        }

        $this->controller = $this->_registry->getController();
        $this->controller->set('Lang', $this);

        $this->dbAvailable = $this->checkDatabaseAvailable();
        $this->pluginsAvailable = $this->checkPluginsAvailable();

        $this->languages = $this->getLanguages();
        $this->lang = $this->getLang();
    }

    private function checkDatabaseAvailable(): bool
    {
        try {
            ConnectionManager::getConfig('default');
        } catch (MissingDatasourceConfigException) {
            return false;
        }

        try {
            ConnectionManager::get('default');
        } catch (Exception) {
            return false;
        }

        return true;
    }

    private function checkPluginsAvailable(): bool
    {
        $controllerName = (string)$this->controller->getRequest()->getParam('controller');

        if ($controllerName === 'Install' || $controllerName === 'Error') {
            return false;
        }

        if (!$this->dbAvailable) {
            return false;
        }

        if (!$this->controller->components()->has('EyPlugin')) {
            return false;
        }

        return true;
    }

    public function getLanguages(): array
    {
        $languagesAvailable = [];

        if (!is_dir($this->langFolder)) {
            return $languagesAvailable;
        }

        $dh = opendir($this->langFolder);
        if ($dh === false) {
            return $languagesAvailable;
        }

        while (($filename = readdir($dh)) !== false) {
            if ($filename === '.' || $filename === '..') {
                continue;
            }

            $parts = explode('.', $filename);
            if (count($parts) < 2) {
                continue;
            }

            $ext = strtolower(end($parts));
            if ($ext !== 'json') {
                continue;
            }

            $fileContent = @file_get_contents($this->langFolder . DS . $filename);
            if ($fileContent === false) {
                continue;
            }

            $fileContent = json_decode($fileContent, true);
            if (!is_array($fileContent)) {
                continue;
            }

            if (!isset($fileContent['INFORMATIONS'], $fileContent['MESSAGES'])) {
                continue;
            }

            $info = $fileContent['INFORMATIONS'];
            if (!isset($info['name'], $info['author'], $info['version'])) {
                continue;
            }

            $key = $parts[0];

            $languagesAvailable[$key] = [
                'name' => $info['name'],
                'author' => $info['author'],
                'version' => $info['version'],
                'path' => $key,
                'fullpath' => $this->langFolder . DS . $filename,
            ];
        }

        closedir($dh);

        return $languagesAvailable;
    }

    public function getLang($mode = false, $language = null): array
    {
        $mode = $mode ?: $this->mode;

        if ($mode === 'cookie' || !$this->dbAvailable) {
            if (isset($_COOKIE['language']) && is_string($_COOKIE['language'])) {
                $language = $_COOKIE['language'];
            } elseif (isset($_SERVER['HTTP_ACCEPT_LANGUAGE']) && is_string($_SERVER['HTTP_ACCEPT_LANGUAGE'])) {
                $langHeader = $_SERVER['HTTP_ACCEPT_LANGUAGE'];
                $language = substr($langHeader, 0, 5);
            }
        } else {
            try {
                $configuration = TableRegistry::getTableLocator()->get('Configuration');
                $language = $configuration->getKey('lang');
            } catch (Exception) {
                $language = null;
            }
        }

        if (!is_string($language) || !isset($this->languages[$language])) {
            if (isset($this->languages['fr_FR'])) {
                $language = 'fr_FR';
            } elseif (!empty($this->languages)) {
                $keys = array_keys($this->languages);
                $language = reset($keys);
            } else {
                return [
                    'name' => null,
                    'author' => null,
                    'version' => null,
                    'path' => null,
                    'fullpath' => $this->langFolder,
                    'messages' => [],
                ];
            }
        }

        $languageData = $this->languages[$language];
        $path = $languageData['path'];

        $content = @file_get_contents($this->langFolder . DS . $path . '.json');
        $messages = [];

        if ($content !== false) {
            $decoded = json_decode($content, true);
            if (is_array($decoded) && isset($decoded['MESSAGES']) && is_array($decoded['MESSAGES'])) {
                $messages = $decoded['MESSAGES'];
            }
        }

        $languageData['messages'] = $messages;

        if ($this->pluginsAvailable && isset($languageData['messages']) && is_array($languageData['messages'])) {
            try {
                $eyPlugin = $this->controller->EyPlugin;
                $themeComponent = $this->controller->loadComponent('Theme');
                $plugins = $eyPlugin->getPluginsActive();
                $themes = $themeComponent->getThemesInstalled(false);
            } catch (Exception) {
                $plugins = [];
                $themes = [];
            }

            if (!empty($plugins)) {
                foreach ($plugins as $value) {
                    $name = $value->slug;
                    $languageFile = $this->getLangFile($eyPlugin->pluginsFolder . DS . $name, $path);
                    if (is_array($languageFile)) {
                        $languageData['messages'] = array_merge($languageData['messages'], $languageFile);
                    }
                }
            }

            if (!empty($themes)) {
                foreach ($themes as $value) {
                    $name = $value->slug;
                    $languageFile = $this->getLangFile($themeComponent->themesFolder . DS . $name, $path);
                    if (is_array($languageFile)) {
                        $languageData['messages'] = array_merge($languageData['messages'], $languageFile);
                    }
                }
            }
        }

        return $languageData;
    }

    private function getLangFile($path, $lang): array
    {
        $languageFile = [];

        $file = $path . DS . 'lang' . DS . $lang . '.json';
        $fallback = $path . DS . 'lang' . DS . 'fr_FR.json';

        if (file_exists($file)) {
            $content = file_get_contents($file);
            $languageFile = json_decode($content, true);
        } elseif (file_exists($fallback)) {
            $content = file_get_contents($fallback);
            $languageFile = json_decode($content, true);
        }

        if (!is_array($languageFile)) {
            return [];
        }

        return $languageFile;
    }

    public function startup($controller): void
    {
    }

    public function get($msg, $vars = [])
    {
        $language = $this->lang;

        if (!is_array($vars)) {
            $vars = [];
        }

        if (isset($language['messages'][$msg])) {
            return strtr($language['messages'][$msg], $vars);
        }

        return $msg;
    }

    public function set($msg, $value): void
    {
        $language = $this->lang;

        if (!isset($language['path']) || !$language['path']) {
            return;
        }

        $lang = file_get_contents($this->langFolder . DS . $language['path'] . '.json');
        $lang = json_decode($lang, true);

        if (!is_array($lang)) {
            return;
        }

        if (!isset($lang['MESSAGES']) || !is_array($lang['MESSAGES'])) {
            $lang['MESSAGES'] = [];
        }

        $lang['MESSAGES'][$msg] = $value;

        $edit = json_encode($lang, JSON_PRETTY_PRINT);
        @file_put_contents($this->langFolder . DS . $language['path'] . '.json', $edit);
    }

    public function setAll($data): void
    {
        $language = $this->getAll();

        $path = $this->lang['path'];

        foreach ($data as $key => $value) {
            foreach ($language as $type => $messages) {
                if (isset($messages[$key])) {
                    $language[$type][$key] = $value;
                }
            }
        }

        foreach ($language as $type => $messages) {
            if ($type === 'CMS') {
                $json = [];
                $json['INFORMATIONS']['name'] = $this->lang['name'];
                $json['INFORMATIONS']['version'] = $this->lang['version'];
                $json['INFORMATIONS']['author'] = $this->lang['author'];

                foreach ($messages as $key => $value) {
                    if ($this->lang['messages'][$key] != $value) {
                        $logPath = ROOT . DS . 'tmp' . DS . 'logs' . DS . 'update' . DS . 'lang' . DS . $path . '.log.json';
                        if (file_exists($logPath)) {
                            $log = file_get_contents($logPath);
                            $log = json_decode($log, true);
                        } else {
                            $log = ['update' => []];
                        }

                        $log['update'][$key] = date('Y-m-d H:i:s');

                        if (!is_dir(dirname($logPath))) {
                            if (!mkdir(dirname($logPath), 0755, true)) {
                                $this->log('Cannot create language log folder');
                            }
                        }

                        @file_put_contents($logPath, json_encode($log, JSON_PRETTY_PRINT));
                    }
                }

                $json['MESSAGES'] = $messages;

                $fp = @fopen($this->langFolder . DS . $path . '.json', 'w+');
                if ($fp !== false) {
                    fwrite($fp, json_encode($json, JSON_PRETTY_PRINT));
                    fclose($fp);
                }
            } else {
                if (isset($this->EyPlugin) && file_exists($this->EyPlugin->pluginsFolder . DS . 'lang' . DS . $path . '.json')) {
                    $fp = fopen($this->EyPlugin->pluginsFolder . DS . 'lang' . DS . $path . '.json', 'w+');
                    fwrite($fp, json_encode($messages, JSON_PRETTY_PRINT));
                    fclose($fp);
                }
            }
        }
    }

    public function getAll(): array
    {
        $language = $this->lang;

        $messages = [];

        if (!isset($language['path']) || !$language['path']) {
            return $messages;
        }

        $lang = file_get_contents($this->langFolder . DS . $language['path'] . '.json');
        $messages['CMS'] = json_decode($lang, true)['MESSAGES'];

        $this->EyPlugin = $this->controller->EyPlugin;

        $plugins = $this->EyPlugin->getPluginsActive();

        if (!empty($plugins)) {
            foreach ($plugins as $value) {
                $name = $value->slug;
                $languageFile = [];

                if (file_exists($this->EyPlugin->pluginsFolder . DS . $name . DS . 'lang' . DS . $language['path'] . '.json')) {
                    $languageFile = file_get_contents($this->EyPlugin->pluginsFolder . DS . $name . DS . 'lang' . DS . $language['path'] . '.json');
                    $languageFile = json_decode($languageFile, true);
                } elseif (file_exists($this->EyPlugin->pluginsFolder . DS . $name . DS . 'lang' . DS . 'fr_FR.json')) {
                    $languageFile = file_get_contents($this->EyPlugin->pluginsFolder . DS . $name . DS . 'lang' . DS . 'fr_FR.json');
                    $languageFile = json_decode($languageFile, true);
                }

                $messages[$name] = $languageFile;
            }
        }

        return $messages;
    }

    public function update($json, $file): void
    {
        if (!file_exists($file)) {
            $fileResource = fopen(ROOT . DS . $file, 'w');
            fwrite($fileResource, $json);
            fclose($fileResource);
            return;
        }

        $fileContent = file_get_contents($file);
        $fileContent = json_decode($fileContent, true);

        $newContent = $fileContent;
        $updatedContent = json_decode($json, true);

        $newContent['INFORMATIONS']['VERSION'] = $updatedContent['INFORMATIONS']['VERSION'];

        $array = explode('/', $file);
        $path = end($array);
        $path = explode('.', $path)[0];

        foreach ($fileContent['MESSAGES'] as $key => $value) {
            $logPath = ROOT . DS . 'tmp' . DS . 'logs' . DS . 'update' . DS . 'lang' . DS . $path . '.log.json';
            if (file_exists($logPath)) {
                $log = file_get_contents($logPath);
                $log = json_decode($log, true);
            } else {
                $log = ['update' => []];
            }

            if (!isset($log['update'][$key])) {
                $newContent['MESSAGES'][$key] = $updatedContent['MESSAGES'][$key];
            }
        }

        foreach ($updatedContent['MESSAGES'] as $key => $value) {
            if (!isset($fileContent['MESSAGES'][$key])) {
                $newContent['MESSAGES'][$key] = $value;
            }
        }

        $fileResource = fopen(ROOT . DS . $file, 'w');
        fwrite($fileResource, json_encode($newContent));
        fclose($fileResource);
    }

    public function date($date)
    {
        $language = $this->lang;

        if (isset($language['messages']['GLOBAL__FORMAT_DATE'])) {
            $dateParts = explode(' ', $date);
            $time = explode(':', $dateParts[1]);
            $date = explode('-', $dateParts[0]);

            $return = str_replace('{%day}', $date[2], $language['messages']['GLOBAL__FORMAT_DATE']);
            $return = str_replace('{%month}', $date[1], $return);
            $return = str_replace('{%year}', $date[0], $return);
            $return = str_replace('{%minutes}', $time[1], $return);

            $if = explode('|', $return);
            $if = explode('}', $if[1]);

            if ($if[0] == 12) {
                if ($time[0] > 12) {
                    $map = [
                        '13' => ['01', 'PM'],
                        '14' => ['02', 'PM'],
                        '15' => ['03', 'PM'],
                        '16' => ['04', 'PM'],
                        '17' => ['05', 'PM'],
                        '18' => ['06', 'PM'],
                        '19' => ['07', 'PM'],
                        '20' => ['08', 'PM'],
                        '21' => ['09', 'PM'],
                        '22' => ['10', 'PM'],
                        '23' => ['11', 'PM'],
                    ];
                    if (isset($map[$time[0]])) {
                        $hour = $map[$time[0]][0];
                        $pmOrAm = $map[$time[0]][1];
                    } else {
                        $hour = $time[0];
                        $pmOrAm = 'PM';
                    }
                } else {
                    $hour = $time[0];
                    $pmOrAm = 'AM';
                }

                $return = str_replace('{%hour|12}', $hour, $return);
                $return = str_replace('{%PM_OR_AM}', $pmOrAm, $return);
            } elseif ($if[0] == 24) {
                $hour = $time[0];
                $return = str_replace('{%hour|24}', $hour, $return);
            } else {
                $return = 'ERROR';
            }
        } else {
            $return = $date;
        }

        return $return;
    }

    public function email_reset($email, $pseudo, $key)
    {
        $msg = 'USER__PASSWORD_RESET_EMAIL_CONTENT';
        $language = $this->lang;

        $code = $language['path'] ?? 'fr_FR';

        if (file_exists(ROOT . DS . 'lang' . DS . $code . '.json')) {
            $languageFile = file_get_contents(ROOT . DS . 'lang' . DS . $code . '.json');
        } else {
            $languageFile = file_get_contents(ROOT . DS . 'lang' . DS . 'fr_FR.json');
        }

        $languageFile = json_decode($languageFile, true);

        if (isset($languageFile['MESSAGES'][$msg])) {
            $text = $languageFile['MESSAGES'][$msg];
            $text = str_replace('{EMAIL}', $email, $text);
            $text = str_replace('{PSEUDO}', $pseudo, $text);
            $text = str_replace('{LINK}', Router::url('/?resetpasswd_' . $key, true), $text);
            return $text;
        }

        return $msg;
    }

    public function history($action)
    {
        $message = $this->lang['messages']['HISTORY__ACTION_' . $action] ?? null;
        return $message ?: $action;
    }
}
