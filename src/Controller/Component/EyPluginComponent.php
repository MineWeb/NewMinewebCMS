<?php
namespace App\Controller\Component;

use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\TableRegistry;
use PharIo\Version\Version;
use PharIo\Version\VersionConstraintParser;
use ZipArchive;
use PDOException;
use Exception;

class EyPluginComponent extends Component
{
    public $pluginsInFolder = [];
    public $pluginsInDB = [];
    public string $pluginsFolder;
    public $pluginsLoaded;
    private $alreadyCheckValid = [];
    private $reference = 'https://raw.githubusercontent.com/MineWeb/mineweb.org/gh-pages/market/plugins.json';
    private $controller;

    private $CmsSqlTables = [];

    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->pluginsFolder = ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'Addons';
        parent::__construct($registry, $config);
    }

    function initialize(array $config): void
    {
        $this->controller = $this->_registry->getController();
        $this->controller->set('EyPlugin', $this);

        $this->models = (object)[
            'Plugin' => TableRegistry::getTableLocator()->get('Plugin'),
            'Permission' => TableRegistry::getTableLocator()->get('Permission')
        ];

        $this->pluginsInFolder = $this->getPluginsInFolder();
        $this->pluginsInDB = $this->getPluginsInDB();
        $this->checkIfNeedToBeInstalled($this->pluginsInFolder['onlyValid'] ?? [], $this->pluginsInDB);
        $this->checkIfNeedToBeDeleted($this->pluginsInFolder['all'] ?? [], $this->pluginsInDB);
        $this->pluginsLoaded = $this->loadPlugins();
    }

    private function getPluginsInFolder()
    {
        $dir = $this->pluginsFolder;
        $plugins = scandir($dir);
        if ($plugins === false) {
            $this->log('Unable to scan plugins folder.');
            return ['all' => [], 'onlyValid' => []];
        }
        $bypassedFiles = ['.', '..', '.DS_Store', '__MACOSX', '.gitkeep'];
        $pluginsList = ['all' => [], 'onlyValid' => []];

        foreach ($plugins as $value) {
            if (in_array($value, $bypassedFiles, true)) {
                continue;
            }
            $pluginsList['all'][] = $value;
            if ($this->isValid($value)) {
                $pluginsList['onlyValid'][] = $value;
            }
        }
        return $pluginsList;
    }

    private function isValid($slug)
    {
        $slug = ucfirst($slug);
        $file = $this->pluginsFolder . DIRECTORY_SEPARATOR . $slug;

        if (isset($this->alreadyCheckValid[$slug])) {
            return $this->alreadyCheckValid[$slug];
        }

        if (!file_exists($file)) {
            $this->log('Plugins folder : ' . $file . ' doesn\'t exist! Plugin not valid!');
            return $this->alreadyCheckValid[$slug] = false;
        }
        if (!is_dir($file)) {
            if (strstr($file, '.gitkeep') === false) {
                $this->log('File : ' . $file . ' is not a folder! Plugin not valid! Please remove this file from the plugin folder.');
            }
            return $this->alreadyCheckValid[$slug] = false;
        }

        $neededFiles = [
            'Config/routes.php',
            'Config/bootstrap.php',
            'lang/fr_FR.json',
            'lang/en_US.json',
            'Controller',
            'Model',
            'View',
            'config.json',
            'SQL/schema.php'
        ];
        foreach ($neededFiles as $value) {
            if (!file_exists($file . DIRECTORY_SEPARATOR . $value)) {
                $this->log('Plugin "' . $slug . '" not valid! The file or folder "' . $file . DIRECTORY_SEPARATOR . $value . '" doesn\'t exist! Please verify documentation for more informations.');
                return $this->alreadyCheckValid[$slug] = false;
            }
        }

        $needToBeJSON = ['lang/fr_FR.json', 'lang/en_US.json', 'config.json'];
        foreach ($needToBeJSON as $value) {
            $content = @file_get_contents($file . DIRECTORY_SEPARATOR . $value);
            $json = json_decode((string)$content);
            if ($json === false || $json === null) {
                $this->log('Plugin "' . $slug . '" not valid! The file "' . $file . DIRECTORY_SEPARATOR . $value . '" is not at JSON format! Please verify documentation for more informations.');
                return $this->alreadyCheckValid[$slug] = false;
            }
        }

        $config = json_decode((string)file_get_contents($file . DIRECTORY_SEPARATOR . 'config.json'), true);
        $needConfigKey = [
            'name' => 'string',
            'author' => 'string',
            'version' => 'string',
            'useEvents' => 'bool',
            'permissions' => 'array',
            'permissions-available' => 'array',
            'permissions-default' => 'array',
            'requirements' => 'array'
        ];
        foreach ($needConfigKey as $key => $value) {
            $keyParts = explode('-', $key);
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
                $function = 'is_' . $value;
                if (!$function($configKey)) {
                    $label = is_array($keyParts) ? '["' . implode('"]["', $keyParts) . '"]' : $key;
                    $this->log('File : ' . $slug . ' is not a valid plugin! The config is not complete! ' . $label . ' is not a good type (' . $value . ' required).');
                    return $this->alreadyCheckValid[$slug] = false;
                }
            } else {
                $label = is_array($keyParts) ? '["' . implode('"]["', $keyParts) . '"]' : $key;
                $this->log('File : ' . $slug . ' is not a valid plugin! The config is not complete! ' . $label . ' is not defined.');
                return $this->alreadyCheckValid[$slug] = false;
            }
        }

        try {
            new Version($config['version']);
        } catch (Exception $e) {
            $this->log('File : ' . $slug . ' is not a valid plugin! The version configured is not at good format !');
            return $this->alreadyCheckValid[$slug] = false;
        }

        $filenameTables = $file . DIRECTORY_SEPARATOR . 'SQL' . DIRECTORY_SEPARATOR . 'schema.php';
        if (!file_exists($filenameTables)) {
            $this->log('File : ' . $slug . ' is not a valid plugin! SQL Schema is not created!');
            return $this->alreadyCheckValid[$slug] = false;
        }

        $nameClass = ucfirst(strtolower($slug)) . 'AppSchema';
        if (!class_exists($nameClass)) {
            require_once $filenameTables;
        }
        if (!class_exists($nameClass)) {
            $this->log('File : ' . $slug . ' is not a valid plugin! SQL Schema is not created!');
            return $this->alreadyCheckValid[$slug] = false;
        }
        $class = new $nameClass();

        if (!method_exists($class, 'before') || !method_exists($class, 'after')) {
            $this->log('File : ' . $slug . ' is not a valid plugin! SQL Schema class is not valid!');
            return $this->alreadyCheckValid[$slug] = false;
        }

        $tables = get_class_vars(get_class($class));
        $ignoredVars = ['name', 'path', 'file', 'connection', 'plugin', 'tables'];
        foreach ($tables as $key => $value) {
            if (!in_array($key, $ignoredVars, true)) {
                $CmsSqlTables = $this->getCmsSqlTables();
                if (!in_array($key, $CmsSqlTables, true)) {
                    $valueExploded = explode('__', $key);
                    if (count($valueExploded) <= 1 || $valueExploded[0] !== strtolower($slug)) {
                        $this->log('File : ' . $slug . ' is not a valid plugin! SQL tables need to be prefixed by slug.');
                        $this->alreadyCheckValid[$slug] = false;
                        return false;
                    }
                }
            }
        }

        return $this->alreadyCheckValid[$slug] = true;
    }

    private function getCmsSqlTables()
    {
        if (empty($this->CmsSqlTables)) {
            require_once ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Schema' . DIRECTORY_SEPARATOR . 'schema.php';
            if (!class_exists('AppSchema')) {
                return [];
            }
            $class = new \AppSchema();
            $tables = get_class_vars(get_class($class));
            $ignoredVars = ['name', 'path', 'file', 'connection', 'plugin', 'tables'];
            foreach ($tables as $key => $value) {
                if (!in_array($key, $ignoredVars, true)) {
                    $this->CmsSqlTables[] = $key;
                }
            }
        }
        return $this->CmsSqlTables;
    }

    private function getPluginsInDB()
    {
        $search = $this->models->Plugin->find()->toArray();
        if (empty($search)) {
            return [];
        }
        $pluginsList = [];
        foreach ($search as $value) {
            $pluginsList[] = $value['name'];
        }
        return $pluginsList;
    }

    private function checkIfNeedToBeInstalled($pluginsInFolder, $pluginsInDB)
    {
        if (empty($pluginsInFolder)) {
            return false;
        }

        $diff = array_diff($pluginsInFolder, $pluginsInDB);
        if (empty($diff)) {
            return false;
        }

        foreach ($diff as $value) {
            $this->install($value);
        }

        return true;
    }

    public function install($slug, $downloaded = false)
    {
        if (!$this->isValid($slug)) {
            if ($downloaded) {
                clearDir($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug);
            }
            Plugin::unload($slug);
            return 'ERROR__PLUGIN_NOT_VALID';
        }

        $addTables = $this->editDatabaseWithSchema($slug, 'CREATE');
        if ($addTables['status']) {
            $tablesName = $addTables['tables'];
        } else {
            return 'ERROR__PLUGIN_SQL_INSTALLATION';
        }

        $config = json_decode((string)file_get_contents($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'config.json'));

        $this->addPermissions($config->permissions);

        $id = null;
        if (($findPlugin = $this->models->Plugin->find('first', ['conditions' => ['name' => $config->name]]))) {
            $id = $findPlugin['id'];
        }
        $pl = $this->models->Plugin->get($id);
        $pl->set([
            'name' => $slug,
            'author' => $config->author,
            'version' => $config->version
        ]);
        $this->models->Plugin->save($pl);

        if (file_exists($this->pluginsFolder . $slug . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component' . DIRECTORY_SEPARATOR . 'MainComponent.php')) {
            App::uses('MainComponent', 'Plugin' . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component');
            $this->Main = new \MainComponent();
            $this->Main->onEnable();
        }

        $this->controller->addPlugin();
        Plugin::load([$slug => ['routes' => true, 'bootstrap' => true]]);
        return true;
    }

    private function editDatabaseWithSchema($slug, $type, $update = false)
    {
        if (!$slug || !in_array($type, ['CREATE', 'DROP'], true)) {
            return false;
        }

        App::uses('CakeSchema', 'Model');

        $options = [
            'name' => ucfirst(strtolower($slug)) . 'AppUpdate',
            'path' => ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'SQL',
            'file' => 'schemaUpdate.php',
            'plugin' => null,
            'connection' => 'default',
            'models' => false
        ];

        $get_new_file = file_get_contents($options['path'] . DIRECTORY_SEPARATOR . 'schema.php');
        $replace_class_name = str_replace('AppSchema', 'AppUpdateSchema', $get_new_file);
        file_put_contents($options['path'] . DIRECTORY_SEPARATOR . $options['file'], $replace_class_name);
        $this->Schema = new \CakeSchema($options);

        $db = ConnectionManager::get('default');
        $db->cacheSources = false;

        $currentSchema = $this->Schema->read($options);
        $pluginSchema = $this->Schema->load($options);
        $compare = $this->Schema->compare($currentSchema, $pluginSchema);
        unlink($options['path'] . DIRECTORY_SEPARATOR . $options['file']);
        $pluginTables = [];
        $contents = [];

        if ($type === 'CREATE') {
            foreach ($compare as $table => $changes) {
                if (!isset($changes['create']) && !isset($changes['add']) && !isset($changes['drop'])) {
                    continue;
                }

                if (isset($changes['add'])) {
                    if (explode('__', $table)[0] !== strtolower($slug)) {
                        foreach ($changes['add'] as $column => $structure) {
                            if (explode('-', $column)[0] !== strtolower($slug)) {
                                unset($compare[$table]['add'][$column]);
                            }
                        }
                    }
                }

                if (isset($compare[$table]['drop'])) {
                    foreach ($compare[$table]['drop'] as $column => $structure) {
                        if (explode('-', $column)[0] !== strtolower($slug) && explode('__', $table)[0] !== strtolower($slug)) {
                            unset($compare[$table]['drop'][$column]);
                        }
                    }
                }

                if (isset($compare[$table]['drop']) && count($compare[$table]['drop']) <= 0) {
                    unset($compare[$table]['drop']);
                }
                if (isset($compare[$table]['add']) && count($compare[$table]['add']) <= 0) {
                    unset($compare[$table]['add']);
                }

                if (isset($compare[$table]) && count($compare[$table]) > 0) {
                    $contents[$table] = $db->alterSchema([$table => $compare[$table]], $table);
                }
            }

            foreach ($compare as $table => $changes) {
                if (isset($changes['create'])) {
                    $contents[$table] = $db->createSchema($pluginSchema, $table);
                    $pluginTables[] = $table;
                }
            }
        } elseif ($type === 'DROP') {
            foreach ($currentSchema['tables'] as $table => $columns) {
                if (explode('__', $table)[0] === strtolower($slug)) {
                    try {
                        $db->query('DROP TABLE IF EXISTS ' . $table);
                    } catch (Exception $e) {
                        $this->log('Error when delete plugin ' . $slug . ' : ' . $e->getMessage());
                    }
                } else {
                    foreach ($columns as $name => $structure) {
                        if (explode('-', $name)[0] === strtolower($slug)) {
                            try {
                                $db->query('ALTER TABLE `' . $table . '` DROP COLUMN `' . $name . '`;');
                            } catch (Exception $e) {
                                $this->log('Error when delete plugin ' . $slug . ' : ' . $e->getMessage());
                            }
                        }
                    }
                }
            }
        }

        $error = [];
        if (!empty($contents)) {
            foreach ($contents as $table => $query) {
                if (empty($query)) {
                    continue;
                }
                try {
                    $db->execute($query);
                } catch (PDOException $e) {
                    $error[] = $table . ': ' . $e->getMessage();
                    $this->log('MYSQL Schema update for "' . $slug . '" plugin (' . $type . ') : ' . $e->getMessage());
                }
            }
        }

        if ($type === 'CREATE') {
            $updateEntries = [];
            if (file_exists($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Schema' . DIRECTORY_SEPARATOR . 'update-entries.php')) {
                include $this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Schema' . DIRECTORY_SEPARATOR . 'update-entries.php';
            }
            $this->Schema->after([], !$update, $updateEntries);

            if (!empty($error)) {
                foreach ($error as $key => $value) {
                    if (strpos($value, 'Base table or view already exists') !== false) {
                        unset($error[$key]);
                    }
                }
            }
        }

        if (empty($error) && $type === 'CREATE') {
            return ['status' => true, 'tables' => $pluginTables];
        }
        if (empty($error) && $type === 'DROP') {
            return ['status' => true];
        }
        return ['status' => false, 'error' => $error];
    }

    private function addPermissions($permissionConfig)
    {
        if (!isset($permissionConfig->default) || empty($permissionConfig->default)) {
            return;
        }

        foreach ($permissionConfig->default as $rank => $permissions) {
            $searchRank = $this->models->Permission->find('first', ['conditions' => ['rank' => $rank]]);
            if (empty($searchRank)) {
                continue;
            }

            $raw = $searchRank['Permission']['permissions'] ?? null;
            $rankPermissions = [];
            if (is_string($raw) && $raw !== '') {
                $tmp = @unserialize($raw);
                if (is_array($tmp)) {
                    $rankPermissions = $tmp;
                }
            }

            foreach ($permissions as $perm) {
                if (!in_array($perm, $rankPermissions, true)) {
                    $rankPermissions[] = $perm;
                }
            }

            $this->models->Permission->read(null, $searchRank['Permission']['id']);
            $this->models->Permission->set(['permissions' => serialize($rankPermissions)]);
            $this->models->Permission->save();
        }
    }

    private function checkIfNeedToBeDeleted($pluginsInFolder, $pluginsInDB)
    {
        if (empty($pluginsInFolder)) {
            return false;
        }

        $diff = array_diff($pluginsInDB, $pluginsInFolder);
        if (empty($diff)) {
            return false;
        }

        foreach ($diff as $value) {
            $this->delete($value, true);
        }

        $this->refreshPermissions();
        this->clearCakeCache();

        return true;
    }

    public function delete($slug)
    {
        if (empty($slug)) {
            return false;
        }

        if (file_exists($this->pluginsFolder . $slug . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component' . DIRECTORY_SEPARATOR . 'MainComponent.php')) {
            App::uses('MainComponent', 'Plugin' . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component');
            $this->Main = new \MainComponent();
            $this->Main->onDisable();
        }

        $this->editDatabaseWithSchema($slug, 'DROP');

        $plugin = $this->models->Plugin->find('first', ['conditions' => ['name' => $slug]]);
        if (!empty($plugin)) {
            $this->models->Plugin->delete($plugin['id']);
        }

        clearDir($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug);
        Plugin::unload($slug);
        $this->clearCakeCache();

        return true;
    }

    public function clearCakeCache()
    {
        Cache::clearGroup(false, '_cake_core_');
        Cache::clearGroup(false, '_cake_model_');
    }

    private function refreshPermissions()
    {
        $defaultPermissions = $this->controller->Permissions->permissions;
        $pluginsPermissions = [];

        foreach ($this->loadPlugins() as $data) {
            if (!isset($data->permissions->available)) {
                continue;
            }
            foreach ($data->permissions->available as $permission) {
                $pluginsPermissions[] = $permission;
            }
        }

        $searchPermissions = $this->models->Permission->find('all');

        foreach ($searchPermissions as $rank) {
            $raw = $rank['Permission']['permissions'] ?? null;
            $permissions = [];
            if (is_string($raw) && $raw !== '') {
                $tmp = @unserialize($raw);
                if (is_array($tmp)) {
                    $permissions = $tmp;
                }
            }

            $permissionsBeforeCheck = $permissions;
            $permissionsChecked = [];

            foreach ($permissions as $key => $perm) {
                $shouldRemove = false;
                if (!in_array($perm, $defaultPermissions, true) && !in_array($perm, $pluginsPermissions, true)) {
                    $shouldRemove = true;
                }
                if (in_array($perm, $permissionsChecked, true)) {
                    $shouldRemove = true;
                }

                if ($shouldRemove) {
                    unset($permissions[$key]);
                } else {
                    $permissionsChecked[] = $perm;
                }
            }

            if (count($permissions) !== count($permissionsBeforeCheck)) {
                $permission = $this->models->Permission->get($rank['Permission']['id']);
                $permission->set(['permissions' => serialize(array_values($permissions))]);
                $this->models->Permission->save($permission);
            }
        }
    }

    public function loadPlugins()
    {
        $dbPlugins = $this->models->Plugin->find('all');
        $pluginList = (object)[];
        $loadedCakePlugins = Plugin::loaded();

        foreach ($dbPlugins as $plugin) {
            $config = $this->getPluginConfig($plugin['name']);
            if (!is_object($config)) {
                Plugin::unload($plugin['name']);
                continue;
            }

            $id = strtolower($plugin['author'] . '.' . $plugin['name']);
            $pluginList->$id = $config;
            $pluginList->$id->id = $id;
            $pluginList->$id->slug = $plugin['name'];
            $pluginList->$id->slugLower = strtolower($plugin['name']);
            $pluginList->$id->DBid = $plugin['id'];
            $pluginList->$id->DBinstall = $plugin['created'];
            $pluginList->$id->active = $plugin['state'];
            $pluginList->$id->isValid = $this->isValid($pluginList->$id->slug);
            $pluginList->$id->loaded = false;

            if (in_array($plugin['name'], $loadedCakePlugins, true)) {
                $pluginList->$id->loaded = true;
            }

            if (!$pluginList->$id->isValid || !$pluginList->$id->active) {
                $pluginList->$id->loaded = false;
                Plugin::unload($pluginList->$id->slug);
            }
        }

        return $pluginList;
    }

    public function getPluginConfig($slug, $array = false)
    {
        $config = @json_decode(@file_get_contents($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'config.json'), $array);
        if (!$config) {
            return false;
        }
        return $config;
    }

    public function displayAvailableUpdate()
    {
        $pluginList = $this->pluginsLoaded;
        if (!empty($pluginList)) {
            $versions = $this->getPluginsLastVersion(array_map(function ($plugin) {
                return $plugin->slug;
            }, (array)$pluginList));
            foreach ($pluginList as $value) {
                $lastVersion = isset($versions[$value->slug]) ? $versions[$value->slug] : false;
                if ($lastVersion && $value->version !== $lastVersion) {
                    $this->Lang = $this->controller->Lang;
                    return '<div class="alert alert-secondary">' . $this->Lang->get('UPDATE__AVAILABLE_TYPE_PLUGIN') . ' ' . $this->Lang->get('UPDATE__AVAILABLE') . ' ' . $this->Lang->get('UPDATE__PLUGIN') . ' <a href="' . Router::url(['controller' => 'plugin', 'action' => 'index', 'admin' => true]) . '" style="margin-top: -6px;" class="btn float-right">' . $this->Lang->get('GLOBAL__UPDATE_LOOK') . '</a></div>';
                }
            }
        }
    }

    public function getPluginsLastVersion(array $slug)
    {
        $plugins = $this->getPluginsFromAPI($slug);
        if ($plugins === false) {
            return false;
        }
        $versions = [];
        foreach ($plugins as $plugin) {
            $versions[$plugin->slug] = $plugin->version;
        }
        return $versions;
    }

    public function getPluginsFromAPI(array $slugs)
    {
        $plugins = $this->getFreePlugins(true);
        if ($plugins === false) {
            return false;
        }

        $pluginsToFind = [];
        foreach ($plugins as $plugin) {
            $plugin = json_decode(json_encode($plugin));
            foreach ($slugs as $slug) {
                if ($plugin->slug === $slug) {
                    $pluginsToFind[$plugin->slug] = $plugin;
                }
            }
        }
        return $pluginsToFind;
    }

    public function getFreePlugins($all = false, $removeInstalledPlugins = false)
    {
        $pluginsList = @json_decode($this->controller->sendGetRequest($this->reference), true);

        $plugins = [];
        if ($pluginsList) {
            $free_plugins = [];
            foreach ($pluginsList as $plugin) {
                if ($plugin['free']) {
                    $free_plugins[] = $plugin;
                } elseif ($all) {
                    $plugins[] = $plugin;
                }
            }
            $plu = $this->getPluginsFromRepoNames(array_column($free_plugins, 'repo'));
            if ($plu) {
                $i = 0;
                foreach ($plu as $pl) {
                    $pl['free'] = true;
                    $pl['slug'] = $free_plugins[$i]['slug'];
                    $plugins[] = $pl;
                    $i++;
                }
            }
        }

        if ($removeInstalledPlugins) {
            $installedPlugins = [];
            foreach ($this->pluginsLoaded as $config) {
                $installedPlugins[] = $config->slug;
            }
            foreach ($plugins as $key => $plugin) {
                if (in_array($plugin['slug'], $installedPlugins, true)) {
                    unset($plugins[$key]);
                }
            }
        }

        return $plugins;
    }

    private function getPluginFromRepoName($repo)
    {
        $configUrl = 'https://raw.githubusercontent.com/' . $repo . '/master/config.json';
        $config = @json_decode($this->controller->sendGetRequest($configUrl), true);
        if (!$config) {
            return false;
        }
        $config['repo'] = $repo;
        return $config;
    }

    private function getPluginsFromRepoNames($repos)
    {
        $urls = [];
        foreach ($repos as $repo) {
            $urls[] = 'https://raw.githubusercontent.com/' . $repo . '/master/config.json';
        }
        $result = $this->controller->sendMultipleGetRequests($urls);
        $results = [];
        $i = 0;
        foreach ($result as $val) {
            $json = json_decode($val, true);
            if (!$json) {
                $i++;
                continue;
            }
            $json['repo'] = $repos[$i];
            $results[] = $json;
            $i++;
        }
        return $results;
    }

    public function initEventsListeners($controller)
    {
        foreach ($this->pluginsLoaded as $plugin) {
            if (!$plugin->useEvents || !$plugin->loaded) {
                continue;
            }
            $slugFormated = ucfirst(strtolower($plugin->slug));
            $eventFolder = $this->pluginsFolder . DIRECTORY_SEPARATOR . $plugin->slug . DIRECTORY_SEPARATOR . 'Event';
            $path = $eventFolder . DIRECTORY_SEPARATOR . $slugFormated . '*EventListener.php';

            foreach (glob($path) as $eventFile) {
                $className = str_replace('.php', '', basename($eventFile));

                App::uses($className, 'Plugin' . DIRECTORY_SEPARATOR . $plugin->slug . DIRECTORY_SEPARATOR . 'Event');
                $controller->getEventManager()->attach(new $className($controller->request, $controller->response, $controller));
            }
        }
    }

    public function getPluginsActive()
    {
        $plugins = $this->pluginsLoaded;
        $pluginList = (object)[];

        foreach ($plugins as $key => $value) {
            if ($value->loaded) {
                $pluginList->$key = $value;
            }
        }
        return $pluginList;
    }

    public function update($slug)
    {
        $config = $this->getPluginFromAPI($slug);
        if (!$config || empty($config)) {
            return 'ERROR__PLUGIN_REQUIREMENTS';
        }

        $dl = $this->download($slug);
        if ($dl !== true) {
            return $dl;
        }

        Plugin::unload($slug);
        $pluginConfig = json_decode((string)file_get_contents($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'config.json'), true);
        $pluginVersion = $pluginConfig['version'];

        $searchPlugin = $this->models->Plugin->find('first', ['conditions' => ['name' => $slug]]);
        $pluginDBID = $searchPlugin['id'];

        if (file_exists($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Update' . DIRECTORY_SEPARATOR . 'beforeSchema.php')) {
            try {
                include $this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Update' . DIRECTORY_SEPARATOR . 'beforeSchema.php';
            } catch (Exception $e) {
                $this->log('Error on plugin update (' . $slug . ') - ' . $e->getMessage());
            }
            unlink($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Update' . DIRECTORY_SEPARATOR . 'beforeSchema.php');
        }

        $addTables = $this->editDatabaseWithSchema($slug, 'CREATE', true);
        if ($addTables['status']) {
            $pluginTables = $addTables['tables'];
        } else {
            return 'ERROR__PLUGIN_SQL_INSTALLATION';
        }

        if (file_exists($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Update' . DIRECTORY_SEPARATOR . 'afterSchema.php')) {
            try {
                include $this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Update' . DIRECTORY_SEPARATOR . 'afterSchema.php';
            } catch (Exception $e) {
                $this->log('Error on plugin update (' . $slug . ') - ' . $e->getMessage());
            }
            unlink($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Update' . DIRECTORY_SEPARATOR . 'afterSchema.php');
        }

        $this->models->Plugin->read(null, $pluginDBID);
        $this->models->Plugin->set(['version' => $pluginVersion, 'tables' => serialize($pluginTables)]);
        $this->models->Plugin->save();
        $this->refreshPermissions();

        $this->clearCakeCache();
        Plugin::load([$slug => ['routes' => true, 'bootstrap' => true]]);

        return true;
    }

    public function getPluginFromAPI($slug)
    {
        $plugins = $this->getPluginsFromAPI([$slug]);
        if (!$plugins || !isset($plugins[$slug])) {
            return false;
        }
        return $plugins[$slug];
    }

    public function download($slug, $install = false)
    {
        $config = $this->getPluginFromAPI($slug);
        if (!$this->requirements($slug, $config)) {
            return 'ERROR__PLUGIN_REQUIREMENTS';
        }

        $zip = $this->controller->sendGetRequest('https://github.com/MineWeb/Plugin-' . $slug . '/archive/master.zip');
        if (!$zip) {
            return 'ERROR__PLUGIN_CANT_BE_DOWNLOADED';
        }

        $zipFile = ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'plugin-' . $slug . '.zip';
        $file = fopen($zipFile, 'w+');
        if (!$file || fwrite($file, $zip) === false) {
            if ($file) {
                fclose($file);
            }
            $this->log('Error when downloading plugin, save files failed.');
            return 'ERROR__PLUGIN_PERMISSIONS';
        }
        fclose($file);

        $zip = new ZipArchive();
        $res = $zip->open($zipFile);
        if ($res !== true) {
            $this->log('Error when downloading plugin, unable to open zip. (CODE: ' . $res . ')');
            return 'ERROR__PLUGIN_PERMISSIONS';
        }
        $pluginDir = ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $slug;
        if (!file_exists($pluginDir) && !mkdir($pluginDir, 0755, true) && !is_dir($pluginDir)) {
            return 'ERROR__PLUGIN_PERMISSIONS';
        }
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $filename = $zip->getNameIndex($i);
            $fileinfo = pathinfo($filename);
            $stat = $zip->statIndex($i);
            if ($fileinfo['basename'] === 'Plugin-' . $slug . '-master') {
                continue;
            }

            $target = 'zip://' . $zipFile . '#' . $filename;
            $dest = $pluginDir . substr($filename, strlen('Plugin-' . $slug . '-master'));
            if ($stat['size'] === 0 && !str_contains($filename, '.')) {
                if (!file_exists($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
                    return 'ERROR__PLUGIN_PERMISSIONS';
                }
                continue;
            }
            if (!copy($target, $dest)) {
                $zip->close();
                return 'ERROR__PLUGIN_PERMISSIONS';
            }
        }
        $zip->close();

        unlink($zipFile);

        $macPath = $this->pluginsFolder . DIRECTORY_SEPARATOR . '__MACOSX';
        $this->deleteDirectoryIfExists($macPath);

        return $install ? $this->install($slug, true) : true;
    }

    private function deleteDirectoryIfExists(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new \RecursiveDirectoryIterator($dir, \FilesystemIterator::SKIP_DOTS);
        $files = new \RecursiveIteratorIterator($iterator, \RecursiveIteratorIterator::CHILD_FIRST);
        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
        @rmdir($dir);
    }

    private function requirements($name, $config = false)
    {
        if (!$config) {
            $config = $this->getPluginConfig($name);
        }

        if (is_object($config)) {
            $requirements = isset($config->requirements) && !empty($config->requirements) ? $config->requirements : null;
        } else {
            $requirements = isset($config['requirements']) && !empty($config['requirements']) ? $config['requirements'] : null;
        }

        if (empty($requirements)) {
            return true;
        }

        $versionParser = new VersionConstraintParser();

        foreach ($requirements as $type => $version) {
            if ($type === 'CMS') {
                $versionToCompare = trim((string)@file_get_contents(ROOT . DIRECTORY_SEPARATOR . 'VERSION'));
            } elseif (count(explode('--', $type)) === 2) {
                $typeExploded = explode('--', $type);
                $kind = $typeExploded[0];
                $id = $typeExploded[1];

                if ($kind === 'plugin') {
                    $search = $this->findPlugin('id', $id);
                    if (empty($search)) {
                        $this->log('Plugin : ' . $name . ' can\'t be installed, plugin ' . $id . ' is missing !');
                        return false;
                    }
                    $versionToCompare = $this->getPluginConfig($search->slug)->version;
                } elseif ($kind === 'theme') {
                    $findThemeVersion = $this->__findThemeVersion($id);
                    if (!$findThemeVersion) {
                        $this->log('Plugin : ' . $name . ' can\'t be installed, theme ' . $id . ' is missing !');
                        return false;
                    }
                    $versionToCompare = $findThemeVersion;
                } else {
                    continue;
                }
            } else {
                continue;
            }

            try {
                $neededVersion = $versionParser->parse($version);
            } catch (Exception $e) {
                $this->log('Plugin: Version exception: ' . $e->getMessage());
                return false;
            }

            if (!$neededVersion->complies(new Version($versionToCompare))) {
                $this->log('Plugin : ' . $name . ' can\'t be installed, requirements not fulfilled (' . $type . ' ' . $version . ') !');
                return false;
            }
        }

        return true;
    }

    public function findPlugin($key, $value)
    {
        foreach ($this->pluginsLoaded as $data) {
            if (isset($data->$key) && $data->$key == $value) {
                return $data;
            }
        }
        return null;
    }

    private function __findThemeVersion($id)
    {
        $themeFolder = ROOT . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Themed';
        $themeFolderContent = scandir($themeFolder);
        if ($themeFolderContent === false) {
            return false;
        }

        $bypassedFiles = ['.', '..', '.DS_Store', '__MACOSX'];

        foreach ($themeFolderContent as $value) {
            if (in_array($value, $bypassedFiles, true)) {
                continue;
            }
            $configPath = $themeFolder . $value;
            $themeConfig = json_decode((string)file_get_contents($configPath), true);
            if (!$themeConfig) {
                continue;
            }
            $themeId = $themeConfig['author'] . '.' . $value;

            if ($themeId === $id) {
                return $themeConfig['version'];
            }
        }

        return false;
    }

    public function isInstalled($id)
    {
        $find = $this->findPlugin('id', $id);
        return (!empty($find) && $find->loaded);
    }

    public function findPluginsLinks()
    {
        $plugins = [];
        foreach ($this->pluginsLoaded as $data) {
            if (isset($data->navbar_routes)) {
                $plugins[$data->slug] = (object)['name' => $data->name, 'routes' => $data->navbar_routes];
            }
        }
        return $plugins;
    }

    public function enable($dbID)
    {
        $this->models->Plugin->read(null, $dbID);
        $this->models->Plugin->set(['state' => 1]);
        $this->models->Plugin->save();

        $plugin = $this->models->Plugin->find('first', ['id' => $dbID]);
        $pluginName = $plugin['name'];

        if (file_exists($this->pluginsFolder . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component' . DIRECTORY_SEPARATOR . 'MainComponent.php')) {
            App::uses('MainComponent', $this->pluginsFolder . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component');
            if (class_exists('MainComponent')) {
                $this->Main = new \MainComponent();
                $this->Main->onEnable();
            }
        }

        Plugin::load([$pluginName => ['routes' => true, 'bootstrap' => true]]);

        return true;
    }

    public function disable($dbID)
    {
        $this->models->Plugin->read(null, $dbID);
        $this->models->Plugin->set(['state' => 0]);
        $this->models->Plugin->save();

        $plugin = $this->models->Plugin->find('first', ['id' => $dbID]);
        $pluginName = $plugin['name'];

        if (file_exists($this->pluginsFolder . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component' . DIRECTORY_SEPARATOR . 'MainComponent.php')) {
            App::uses('MainComponent', $this->pluginsFolder . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component');
            if (class_exists('MainComponent')) {
                $this->Main = new \MainComponent();
                $this->Main->onDisable();
            }
        }

        Plugin::unload($pluginName);
        $this->clearCakeCache();

        return true;
    }

    public function getPluginLastVersion($slug)
    {
        $plugin = $this->getPluginFromAPI($slug);
        if (!$plugin) {
            return false;
        }
        return $plugin->version;
    }
}
