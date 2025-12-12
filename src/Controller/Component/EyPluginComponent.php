<?php
declare(strict_types=1);

namespace App\Controller\Component;

use AppSchema;
use Cake\Cache\Cache;
use Cake\Controller\Component;
use Cake\Controller\ComponentRegistry;
use Cake\Core\App;
use Cake\Core\Plugin;
use Cake\Datasource\ConnectionManager;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use CakeSchema;
use Exception;
use FilesystemIterator;
use MainComponent;
use PDOException;
use PharIo\Version\Version;
use PharIo\Version\VersionConstraintParser;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ZipArchive;

class EyPluginComponent extends Component
{
    use LocatorAwareTrait;

    public array $pluginsInFolder = [];
    public array $pluginsInDB = [];
    public string $pluginsFolder;
    public object $pluginsLoaded;

    private array $alreadyCheckValid = [];
    private string $reference = 'https://raw.githubusercontent.com/MineWeb/mineweb.org/gh-pages/market/plugins.json';

    private mixed $controller = null;

    private array $CmsSqlTables = [];

    private object $models;

    private mixed $Schema = null;
    private mixed $Main = null;

    public function __construct(ComponentRegistry $registry, array $config = [])
    {
        $this->pluginsFolder = ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . 'Addons';
        $this->pluginsLoaded = (object)[];
        parent::__construct($registry, $config);
    }

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->controller = $this->_registry->getController();
        $this->controller->set('EyPlugin', $this);

        $this->models = (object)[
            'Plugin' => $this->fetchTable('Plugins'),
            'Permission' => $this->fetchTable('Permissions'),
        ];

        $this->pluginsInFolder = $this->getPluginsInFolder();
        $this->pluginsInDB = $this->getPluginsInDB();
        $this->checkIfNeedToBeInstalled($this->pluginsInFolder['onlyValid'] ?? [], $this->pluginsInDB);
        $this->checkIfNeedToBeDeleted($this->pluginsInFolder['all'] ?? [], $this->pluginsInDB);
        $this->pluginsLoaded = $this->loadPlugins();
    }

    private function getPluginsInFolder(): array
    {
        $dir = $this->pluginsFolder;
        $plugins = @scandir($dir);
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

    private function isValid(string $slug): bool
    {
        $slug = ucfirst($slug);
        $file = $this->pluginsFolder . DIRECTORY_SEPARATOR . $slug;

        if (isset($this->alreadyCheckValid[$slug])) {
            return (bool)$this->alreadyCheckValid[$slug];
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
            'SQL/schema.php',
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
        if (!is_array($config)) {
            $this->log('File : ' . $slug . ' is not a valid plugin! The config is not readable.');

            return $this->alreadyCheckValid[$slug] = false;
        }

        $needConfigKey = [
            'name' => 'string',
            'author' => 'string',
            'version' => 'string',
            'useEvents' => 'bool',
            'permissions' => 'array',
            'permissions-available' => 'array',
            'permissions-default' => 'array',
            'requirements' => 'array',
        ];

        foreach ($needConfigKey as $key => $value) {
            $keyParts = explode('-', $key);

            if (count($keyParts) > 1) {
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
                if (!function_exists($function) || !$function($configKey)) {
                    $label = '["' . implode('"]["', $keyParts) . '"]';
                    $this->log('File : ' . $slug . ' is not a valid plugin! The config is not complete! ' . $label . ' is not a good type (' . $value . ' required).');

                    return $this->alreadyCheckValid[$slug] = false;
                }
            } else {
                $label = '["' . implode('"]["', $keyParts) . '"]';
                $this->log('File : ' . $slug . ' is not a valid plugin! The config is not complete! ' . $label . ' is not defined.');

                return $this->alreadyCheckValid[$slug] = false;
            }
        }

        try {
            new Version((string)$config['version']);
        } catch (Exception) {
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

        $tables = get_class_vars($class::class);
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

    private function getCmsSqlTables(): array
    {
        if (empty($this->CmsSqlTables)) {
            $schemaPath = ROOT . DIRECTORY_SEPARATOR . 'Config' . DIRECTORY_SEPARATOR . 'Schema' . DIRECTORY_SEPARATOR . 'schema.php';
            if (!file_exists($schemaPath)) {
                return [];
            }

            require_once $schemaPath;
            if (!class_exists('AppSchema')) {
                return [];
            }

            $class = new AppSchema();
            $tables = get_class_vars($class::class);
            $ignoredVars = ['name', 'path', 'file', 'connection', 'plugin', 'tables'];

            foreach ($tables as $key => $value) {
                if (!in_array($key, $ignoredVars, true)) {
                    $this->CmsSqlTables[] = $key;
                }
            }
        }

        return $this->CmsSqlTables;
    }

    private function getPluginsInDB(): array
    {
        $rows = $this->models->Plugin->find()->all()->toArray();
        if (empty($rows)) {
            return [];
        }

        $pluginsList = [];
        foreach ($rows as $row) {
            $name = $row->get('name');
            if (is_string($name) && $name !== '') {
                $pluginsList[] = $name;
            }
        }

        return $pluginsList;
    }

    private function checkIfNeedToBeInstalled(array $pluginsInFolder, array $pluginsInDB): bool
    {
        if (empty($pluginsInFolder)) {
            return false;
        }

        $diff = array_diff($pluginsInFolder, $pluginsInDB);
        if (empty($diff)) {
            return false;
        }

        foreach ($diff as $value) {
            $this->install((string)$value);
        }

        return true;
    }

    public function install(string $slug, bool $downloaded = false): mixed
    {
        if (!$this->isValid($slug)) {
            if ($downloaded) {
                clearDir($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug);
            }
            Plugin::unload($slug);

            return 'ERROR__PLUGIN_NOT_VALID';
        }

        $addTables = $this->editDatabaseWithSchema($slug, 'CREATE');
        if (!is_array($addTables) || empty($addTables['status'])) {
            return 'ERROR__PLUGIN_SQL_INSTALLATION';
        }

        $config = json_decode((string)file_get_contents($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'config.json'));
        if (!is_object($config)) {
            return 'ERROR__PLUGIN_NOT_VALID';
        }

        $this->addPermissions($config->permissions ?? null);

        $existing = $this->models->Plugin->find()->where(['name' => (string)$config->name])->first();
        $entity = $existing ?: $this->models->Plugin->newEmptyEntity();

        $entity->set([
            'name' => $slug,
            'author' => (string)($config->author ?? ''),
            'version' => (string)($config->version ?? ''),
        ]);

        $this->models->Plugin->save($entity);

        $mainPath = $this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component' . DIRECTORY_SEPARATOR . 'MainComponent.php';
        if (file_exists($mainPath)) {
            App::uses('MainComponent', 'Plugin' . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component');
            $this->Main = new MainComponent();
            $this->Main->onEnable();
        }

        if (method_exists($this->controller, 'addPlugin')) {
            $this->controller->addPlugin();
        }

        Plugin::load([$slug => ['routes' => true, 'bootstrap' => true]]);

        return true;
    }

    private function editDatabaseWithSchema(string $slug, string $type, bool $update = false): array|false
    {
        if ($slug === '' || !in_array($type, ['CREATE', 'DROP'], true)) {
            return false;
        }

        App::uses('CakeSchema', 'Model');

        $options = [
            'name' => ucfirst(strtolower($slug)) . 'AppUpdate',
            'path' => ROOT . DIRECTORY_SEPARATOR . 'plugins' . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'SQL',
            'file' => 'schemaUpdate.php',
            'plugin' => null,
            'connection' => 'default',
            'models' => false,
        ];

        $sourceSchema = @file_get_contents($options['path'] . DIRECTORY_SEPARATOR . 'schema.php');
        if (!is_string($sourceSchema) || $sourceSchema === '') {
            return ['status' => false, 'error' => ['schema.php unreadable']];
        }

        $replace_class_name = str_replace('AppSchema', 'AppUpdateSchema', $sourceSchema);
        file_put_contents($options['path'] . DIRECTORY_SEPARATOR . $options['file'], $replace_class_name);

        $this->Schema = new CakeSchema($options);

        $db = ConnectionManager::get('default');
        if (property_exists($db, 'cacheSources')) {
            $db->cacheSources = false;
        }

        $currentSchema = $this->Schema->read($options);
        $pluginSchema = $this->Schema->load($options);
        $compare = $this->Schema->compare($currentSchema, $pluginSchema);

        @unlink($options['path'] . DIRECTORY_SEPARATOR . $options['file']);

        $pluginTables = [];
        $contents = [];

        if ($type === 'CREATE') {
            foreach ($compare as $table => $changes) {
                if (!isset($changes['create']) && !isset($changes['add']) && !isset($changes['drop'])) {
                    continue;
                }

                if (isset($changes['add'])) {
                    if (explode('__', (string)$table)[0] !== strtolower($slug)) {
                        foreach ($changes['add'] as $column => $structure) {
                            if (explode('-', (string)$column)[0] !== strtolower($slug)) {
                                unset($compare[$table]['add'][$column]);
                            }
                        }
                    }
                }

                if (isset($compare[$table]['drop'])) {
                    foreach ($compare[$table]['drop'] as $column => $structure) {
                        if (explode('-', (string)$column)[0] !== strtolower($slug) && explode('__', (string)$table)[0] !== strtolower($slug)) {
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
                    $contents[$table] = $db->alterSchema([$table => $compare[$table]], (string)$table);
                }
            }

            foreach ($compare as $table => $changes) {
                if (isset($changes['create'])) {
                    $contents[$table] = $db->createSchema($pluginSchema, (string)$table);
                    $pluginTables[] = (string)$table;
                }
            }
        }

        if ($type === 'DROP') {
            foreach (($currentSchema['tables'] ?? []) as $table => $columns) {
                if (explode('__', (string)$table)[0] === strtolower($slug)) {
                    try {
                        $db->execute('DROP TABLE IF EXISTS ' . $table);
                    } catch (Exception $e) {
                        $this->log('Error when delete plugin ' . $slug . ' : ' . $e->getMessage());
                    }
                } else {
                    foreach ((array)$columns as $name => $structure) {
                        if (explode('-', (string)$name)[0] === strtolower($slug)) {
                            try {
                                $db->execute('ALTER TABLE `' . $table . '` DROP COLUMN `' . $name . '`;');
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
                    $db->execute((string)$query);
                } catch (PDOException $e) {
                    $error[] = $table . ': ' . $e->getMessage();
                    $this->log('MYSQL Schema update for "' . $slug . '" plugin (' . $type . ') : ' . $e->getMessage());
                }
            }
        }

        if ($type === 'CREATE') {
            $updateEntries = [];
            $updateEntriesFile = $this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Schema' . DIRECTORY_SEPARATOR . 'update-entries.php';
            if (file_exists($updateEntriesFile)) {
                include $updateEntriesFile;
            }

            $this->Schema->after([], !$update, $updateEntries);

            if (!empty($error)) {
                foreach ($error as $key => $value) {
                    if (strpos((string)$value, 'Base table or view already exists') !== false) {
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

    private function addPermissions(mixed $permissionConfig): void
    {
        if (!is_object($permissionConfig) || !isset($permissionConfig->default) || empty($permissionConfig->default)) {
            return;
        }

        foreach ($permissionConfig->default as $rank => $permissions) {
            $rankEntity = $this->models->Permission->find()->where(['rank' => $rank])->first();
            if (!$rankEntity) {
                continue;
            }

            $raw = $rankEntity->get('permissions');
            $rankPermissions = [];
            if (is_string($raw) && $raw !== '') {
                $tmp = @unserialize($raw);
                if (is_array($tmp)) {
                    $rankPermissions = $tmp;
                }
            }

            foreach ((array)$permissions as $perm) {
                if (!in_array($perm, $rankPermissions, true)) {
                    $rankPermissions[] = $perm;
                }
            }

            $rankEntity->set('permissions', serialize($rankPermissions));
            $this->models->Permission->save($rankEntity);
        }
    }

    private function checkIfNeedToBeDeleted(array $pluginsInFolder, array $pluginsInDB): bool
    {
        if (empty($pluginsInFolder)) {
            return false;
        }

        $diff = array_diff($pluginsInDB, $pluginsInFolder);
        if (empty($diff)) {
            return false;
        }

        foreach ($diff as $value) {
            $this->delete((string)$value);
        }

        $this->refreshPermissions();
        $this->clearCakeCache();

        return true;
    }

    public function delete(string $slug): bool
    {
        if ($slug === '') {
            return false;
        }

        $mainPath = $this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component' . DIRECTORY_SEPARATOR . 'MainComponent.php';
        if (file_exists($mainPath)) {
            App::uses('MainComponent', 'Plugin' . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component');
            $this->Main = new MainComponent();
            $this->Main->onDisable();
        }

        $this->editDatabaseWithSchema($slug, 'DROP');

        $pluginEntity = $this->models->Plugin->find()->where(['name' => $slug])->first();
        if ($pluginEntity) {
            $this->models->Plugin->delete($pluginEntity);
        }

        clearDir($this->pluginsFolder . DIRECTORY_SEPARATOR . $slug);
        Plugin::unload($slug);
        $this->clearCakeCache();

        return true;
    }

    public function clearCakeCache(): void
    {
        Cache::clearAll();
    }

    private function refreshPermissions(): void
    {
        $defaultPermissions = $this->controller->Permissions->permissions ?? [];
        if (!is_array($defaultPermissions)) {
            $defaultPermissions = [];
        }

        $pluginsPermissions = [];
        foreach ((array)$this->loadPlugins() as $data) {
            if (!isset($data->permissions->available)) {
                continue;
            }
            foreach ((array)$data->permissions->available as $permission) {
                $pluginsPermissions[] = $permission;
            }
        }

        $ranks = $this->models->Permission->find()->all();

        foreach ($ranks as $rankEntity) {
            $raw = $rankEntity->get('permissions');
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
                $rankEntity->set('permissions', serialize(array_values($permissions)));
                $this->models->Permission->save($rankEntity);
            }
        }
    }

    public function loadPlugins(): object
    {
        $dbPlugins = $this->models->Plugin->find()->all();
        $pluginList = (object)[];
        $loadedCakePlugins = Plugin::loaded();

        foreach ($dbPlugins as $plugin) {
            $name = (string)$plugin->get('name');
            $config = $this->getPluginConfig($name);
            if (!is_object($config)) {
                Plugin::unload($name);
                continue;
            }

            $author = (string)$plugin->get('author');
            $id = strtolower($author . '.' . $name);

            $pluginList->$id = $config;
            $pluginList->$id->id = $id;
            $pluginList->$id->slug = $name;
            $pluginList->$id->slugLower = strtolower($name);
            $pluginList->$id->DBid = (int)$plugin->get('id');
            $pluginList->$id->DBinstall = $plugin->get('created_at');
            $pluginList->$id->active = (bool)$plugin->get('state');
            $pluginList->$id->isValid = $this->isValid($pluginList->$id->slug);
            $pluginList->$id->loaded = in_array($name, $loadedCakePlugins, true);

            if (!$pluginList->$id->isValid || !$pluginList->$id->active) {
                $pluginList->$id->loaded = false;
                Plugin::unload($pluginList->$id->slug);
            }
        }

        return $pluginList;
    }

    public function getPluginConfig(string $slug, bool $array = false): mixed
    {
        $path = $this->pluginsFolder . DIRECTORY_SEPARATOR . $slug . DIRECTORY_SEPARATOR . 'config.json';
        $config = @json_decode((string)@file_get_contents($path), $array);

        return $config ?: false;
    }

    public function displayAvailableUpdate(): ?string
    {
        $pluginList = $this->pluginsLoaded;

        if (!empty((array)$pluginList)) {
            $versions = $this->getPluginsLastVersion(array_map(static function ($plugin) {
                return $plugin->slug;
            }, (array)$pluginList));

            foreach ($pluginList as $value) {
                $lastVersion = $versions[$value->slug] ?? false;
                if ($lastVersion && $value->version !== $lastVersion) {
                    return '<div class="alert alert-secondary">'
                        . __('UPDATE__AVAILABLE_TYPE_PLUGIN') . ' '
                        . __('UPDATE__AVAILABLE') . ' '
                        . __('UPDATE__PLUGIN')
                        . ' <a href="' . Router::url(['_name' => 'admin_plugin_index']) . '" style="margin-top: -6px;" class="btn float-right">'
                        . __('GLOBAL__UPDATE_LOOK')
                        . '</a></div>';
                }
            }
        }

        return null;
    }

    public function getPluginsLastVersion(array $slug): array|false
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

    public function getPluginsFromAPI(array $slugs): array|false
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

    public function getFreePlugins(bool $all = false, bool $removeInstalledPlugins = false): array|false
    {
        $pluginsList = @json_decode((string)$this->controller->sendGetRequest($this->reference), true);

        $plugins = [];
        if ($pluginsList) {
            $free_plugins = [];
            foreach ($pluginsList as $plugin) {
                if (!empty($plugin['free'])) {
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
            foreach ((array)$this->pluginsLoaded as $config) {
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

    private function getPluginsFromRepoNames(array $repos): array|false
    {
        $urls = [];
        foreach ($repos as $repo) {
            $urls[] = 'https://raw.githubusercontent.com/' . $repo . '/master/config.json';
        }

        $result = $this->controller->sendMultipleGetRequests($urls);
        if (!is_array($result)) {
            return false;
        }

        $results = [];
        $i = 0;
        foreach ($result as $val) {
            $json = json_decode((string)$val, true);
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

    public function initEventsListeners($controller): void
    {
        foreach ((array)$this->pluginsLoaded as $plugin) {
            if (empty($plugin->useEvents) || empty($plugin->loaded)) {
                continue;
            }

            $slugFormated = ucfirst(strtolower($plugin->slug));
            $eventFolder = $this->pluginsFolder . DIRECTORY_SEPARATOR . $plugin->slug . DIRECTORY_SEPARATOR . 'Event';
            $path = $eventFolder . DIRECTORY_SEPARATOR . $slugFormated . '*EventListener.php';

            foreach (glob($path) ?: [] as $eventFile) {
                $className = str_replace('.php', '', basename((string)$eventFile));
                App::uses($className, 'Plugin' . DIRECTORY_SEPARATOR . $plugin->slug . DIRECTORY_SEPARATOR . 'Event');
                $controller->getEventManager()->attach(new $className($controller->request, $controller->response, $controller));
            }
        }
    }

    public function getPluginsActive(): object
    {
        $pluginList = (object)[];

        foreach ((array)$this->pluginsLoaded as $key => $value) {
            if (!empty($value->loaded)) {
                $pluginList->$key = $value;
            }
        }

        return $pluginList;
    }

    public function getPluginFromAPI(string $slug): mixed
    {
        $plugins = $this->getPluginsFromAPI([$slug]);
        if (!$plugins || !isset($plugins[$slug])) {
            return false;
        }

        return $plugins[$slug];
    }

    public function download(string $slug, bool $install = false): mixed
    {
        $config = $this->getPluginFromAPI($slug);
        if (!$this->requirements($slug, $config)) {
            return 'ERROR__PLUGIN_REQUIREMENTS';
        }

        $zipContent = $this->controller->sendGetRequest('https://github.com/MineWeb/Plugin-' . $slug . '/archive/master.zip');
        if (!$zipContent) {
            return 'ERROR__PLUGIN_CANT_BE_DOWNLOADED';
        }

        $zipFile = ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'plugin-' . $slug . '.zip';
        $file = fopen($zipFile, 'w+');
        if (!$file || fwrite($file, $zipContent) === false) {
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
            $filename = (string)$zip->getNameIndex($i);
            $fileinfo = pathinfo($filename);
            $stat = $zip->statIndex($i);

            if (($fileinfo['basename'] ?? '') === 'Plugin-' . $slug . '-master') {
                continue;
            }

            $target = 'zip://' . $zipFile . '#' . $filename;
            $dest = $pluginDir . substr($filename, strlen('Plugin-' . $slug . '-master'));

            if (!empty($stat['size']) || $stat['size'] !== 0) {
                if (!copy($target, $dest)) {
                    $zip->close();

                    return 'ERROR__PLUGIN_PERMISSIONS';
                }
                continue;
            }

            if (!str_contains($filename, '.')) {
                if (!file_exists($dest) && !mkdir($dest, 0755, true) && !is_dir($dest)) {
                    return 'ERROR__PLUGIN_PERMISSIONS';
                }
            }
        }

        $zip->close();
        @unlink($zipFile);

        $macPath = $this->pluginsFolder . DIRECTORY_SEPARATOR . '__MACOSX';
        $this->deleteDirectoryIfExists($macPath);

        return $install ? $this->install($slug, true) : true;
    }

    private function deleteDirectoryIfExists(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
        $files = new RecursiveIteratorIterator($iterator, RecursiveIteratorIterator::CHILD_FIRST);

        foreach ($files as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }

        @rmdir($dir);
    }

    private function requirements(string $name, mixed $config = false): bool
    {
        if (!$config) {
            $config = $this->getPluginConfig($name);
        }

        if (is_object($config)) {
            $requirements = !empty($config->requirements) ? $config->requirements : null;
        } else {
            $requirements = !empty($config['requirements']) ? $config['requirements'] : null;
        }

        if (empty($requirements)) {
            return true;
        }

        $versionParser = new VersionConstraintParser();

        foreach ($requirements as $type => $version) {
            if ($type === 'CMS') {
                $versionToCompare = trim((string)@file_get_contents(ROOT . DIRECTORY_SEPARATOR . 'VERSION'));
            } elseif (count(explode('--', (string)$type)) === 2) {
                $typeExploded = explode('--', (string)$type);
                $kind = $typeExploded[0] ?? '';
                $id = $typeExploded[1] ?? '';

                if ($kind === 'plugin') {
                    $search = $this->findPlugin('id', $id);
                    if (empty($search)) {
                        $this->log('Plugin : ' . $name . ' can\'t be installed, plugin ' . $id . ' is missing !');

                        return false;
                    }
                    $pluginCfg = $this->getPluginConfig($search->slug);
                    $versionToCompare = is_object($pluginCfg) ? (string)$pluginCfg->version : '';
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
                $neededVersion = $versionParser->parse((string)$version);
            } catch (Exception $e) {
                $this->log('Plugin: Version exception: ' . $e->getMessage());

                return false;
            }

            if (!$neededVersion->complies(new Version((string)$versionToCompare))) {
                $this->log('Plugin : ' . $name . ' can\'t be installed, requirements not fulfilled (' . $type . ' ' . $version . ') !');

                return false;
            }
        }

        return true;
    }

    public function findPlugin(string $key, mixed $value): mixed
    {
        foreach ((array)$this->pluginsLoaded as $data) {
            if (isset($data->$key) && $data->$key == $value) {
                return $data;
            }
        }

        return null;
    }

    private function __findThemeVersion(string $id): string|false
    {
        $themeFolder = ROOT . DIRECTORY_SEPARATOR . 'View' . DIRECTORY_SEPARATOR . 'Themed';
        $themeFolderContent = @scandir($themeFolder);
        if ($themeFolderContent === false) {
            return false;
        }

        $bypassedFiles = ['.', '..', '.DS_Store', '__MACOSX'];

        foreach ($themeFolderContent as $value) {
            if (in_array($value, $bypassedFiles, true)) {
                continue;
            }

            $configPath = $themeFolder . $value;
            $themeConfig = json_decode((string)@file_get_contents($configPath), true);
            if (!$themeConfig) {
                continue;
            }

            $themeId = ($themeConfig['author'] ?? '') . '.' . $value;
            if ($themeId === $id) {
                return (string)($themeConfig['version'] ?? '');
            }
        }

        return false;
    }

    public function isInstalled(string $id): bool
    {
        $find = $this->findPlugin('id', $id);

        return !empty($find) && !empty($find->loaded);
    }

    public function findPluginsLinks(): array
    {
        $plugins = [];
        foreach ((array)$this->pluginsLoaded as $data) {
            if (isset($data->navbar_routes)) {
                $plugins[$data->slug] = (object)['name' => $data->name, 'routes' => $data->navbar_routes];
            }
        }

        return $plugins;
    }

    public function enable(int $dbID): bool
    {
        $entity = $this->models->Plugin->get($dbID);
        $entity->set('state', 1);
        $this->models->Plugin->save($entity);

        $pluginName = (string)$entity->get('name');

        $mainPath = $this->pluginsFolder . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component' . DIRECTORY_SEPARATOR . 'MainComponent.php';
        if (file_exists($mainPath)) {
            App::uses('MainComponent', $this->pluginsFolder . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component');
            if (class_exists('MainComponent')) {
                $this->Main = new MainComponent();
                $this->Main->onEnable();
            }
        }

        Plugin::load([$pluginName => ['routes' => true, 'bootstrap' => true]]);

        return true;
    }

    public function disable(int $dbID): bool
    {
        $entity = $this->models->Plugin->get($dbID);
        $entity->set('state', 0);
        $this->models->Plugin->save($entity);

        $pluginName = (string)$entity->get('name');

        $mainPath = $this->pluginsFolder . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component' . DIRECTORY_SEPARATOR . 'MainComponent.php';
        if (file_exists($mainPath)) {
            App::uses('MainComponent', $this->pluginsFolder . DIRECTORY_SEPARATOR . $pluginName . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component');
            if (class_exists('MainComponent')) {
                $this->Main = new MainComponent();
                $this->Main->onDisable();
            }
        }

        Plugin::unload($pluginName);
        $this->clearCakeCache();

        return true;
    }

    public function getPluginLastVersion(string $slug): bool
    {
        $plugin = $this->getPluginFromAPI($slug);
        if (!$plugin) {
            return false;
        }

        return $plugin->version;
    }
}
