<?php
declare(strict_types=1);

namespace App;

use App\Middleware\AuthContextMiddleware;
use App\Middleware\BanMiddleware;
use App\Middleware\InstallMiddleware;
use App\Middleware\LocaleMiddleware;
use App\Middleware\MaintenanceMiddleware;
use App\Service\Package\PackageException;
use App\Service\Package\PackageManagerFactory;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\ConnectionManager;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\Log\Log;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Composer\Autoload\ClassLoader;
use Throwable;

final class Application extends BaseApplication
{
    private const SESSION_TYPES = ['php', 'cake', 'cache', 'database'];

    public function bootstrap(): void
    {
        parent::bootstrap();

        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();

            return;
        }

        FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));

        if (Configure::read('debug') && extension_loaded('pdo_sqlite')) {
            $this->addPlugin('DebugKit');
        }

        $this->syncLocalAddons();

        $addons = $this->loadEnabledAddons();
        $themes = $this->loadInstalledThemes();

        Configure::write('RuntimePlugins.addons', $addons);
        Configure::write('RuntimePlugins.themes', $themes);

        $this->applyRuntimeSessionDefaults();
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new InstallMiddleware())
            ->add(new ErrorHandlerMiddleware(Configure::read('Error')))
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))
            ->add(new LocaleMiddleware())
            ->add(new AuthContextMiddleware())
            ->add(new RoutingMiddleware($this))
            ->add(new BodyParserMiddleware())
            ->add(new CsrfProtectionMiddleware([
                'httponly' => true,
            ]))
            ->add(new BanMiddleware())
            ->add(new MaintenanceMiddleware());

        return $middlewareQueue;
    }

    public function services(ContainerInterface $container): void
    {
    }

    protected function bootstrapCli(): void
    {
        $this->syncLocalAddons();

        $addons = $this->loadEnabledAddons();
        $themes = $this->loadInstalledThemes();

        Configure::write('RuntimePlugins.addons', $addons);
        Configure::write('RuntimePlugins.themes', $themes);

        $this->applyRuntimeSessionDefaults();
    }

    private function applyRuntimeSessionDefaults(): void
    {
        $type = $this->resolveSessionType();

        if ($type === 'database' && !$this->databaseSessionsReady()) {
            $type = 'php';
            Cache::write('runtime_session_type', $type);
        }

        if ($type === 'cache' && !$this->cacheSessionsReady()) {
            $type = 'php';
            Cache::write('runtime_session_type', $type);
        }

        Configure::write('Session.defaults', $type);

        if ($type === 'cache') {
            Configure::write('Session.handler.config', 'sessions');
        }
    }

    private function cacheSessionsReady(): bool
    {
        try {
            return Cache::getConfig('sessions') !== null;
        } catch (Throwable) {
            return false;
        }
    }

    private function databaseSessionsReady(): bool
    {
        if (!Configure::read('Install.dbConfigured') || !Configure::read('Install.installed')) {
            return false;
        }

        try {
            $connection = ConnectionManager::get('default');
            $tables = $connection->getSchemaCollection()->listTables();

            return in_array('sessions', $tables, true);
        } catch (Throwable) {
            return false;
        }
    }

    private function resolveSessionType(): string
    {
        if (!Configure::read('Install.dbConfigured') || !Configure::read('Install.installed')) {
            return 'php';
        }

        $cacheKey = 'runtime_session_type';
        $cached = Cache::read($cacheKey);
        if (is_string($cached) && in_array($cached, self::SESSION_TYPES, true)) {
            return $cached;
        }

        $type = 'php';

        try {
            $locator = FactoryLocator::get('Table');
            $Configurations = $locator->get('Configurations');

            $row = $Configurations
                ->find()
                ->select(['session_type'])
                ->where(['id' => 1])
                ->enableHydration(false)
                ->first();

            $value = is_array($row) ? (string)($row['session_type'] ?? '') : '';
            if (in_array($value, self::SESSION_TYPES, true)) {
                $type = $value;
            }
        } catch (Throwable) {
            $type = 'php';
        }

        Cache::write($cacheKey, $type);

        return $type;
    }

    private function syncLocalAddons(): void
    {
        if (!Configure::read('Install.dbConfigured') || !Configure::read('Install.installed')) {
            return;
        }

        $addonsDir = rtrim((string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons'), DS);
        if (!is_dir($addonsDir)) {
            $alt = ROOT . DS . 'plugins' . DS . 'Addon';
            if (is_dir($alt)) {
                $addonsDir = $alt;
            } else {
                return;
            }
        }

        $manifestFile = (string)Configure::read('Update.addons.manifest', 'manifest.json');

        try {
            $locator = FactoryLocator::get('Table');
            $Plugins = $locator->get('Plugins');
        } catch (Throwable $e) {
            Log::error('Addon sync failed (Plugins table unavailable): ' . $e->getMessage());

            return;
        }

        $entries = scandir($addonsDir) ?: [];
        $installedAny = false;

        foreach ($entries as $slug) {
            if (!is_string($slug) || $slug === '.' || $slug === '..' || $slug === '.gitkeep') {
                continue;
            }

            $path = $addonsDir . DS . $slug;
            if (!is_dir($path)) {
                continue;
            }

            $manifestPath = $path . DS . $manifestFile;
            if (!is_file($manifestPath)) {
                Log::warning('Addon detected but invalid (missing manifest): ' . $slug);
                continue;
            }

            try {
                $exists = $Plugins->find()->select(['id'])->where(['name' => $slug])->first();
            } catch (Throwable $e) {
                Log::error('Addon sync failed (DB query error): ' . $e->getMessage());

                return;
            }

            if ($exists !== null) {
                continue;
            }

            try {
                $this->addAddonPlugin($slug, false, true);

                $packages = (new PackageManagerFactory())->create();
                $packages->registerLocalAddon($slug);

                $installedAny = true;

                try {
                    $plugin = $this->getPlugins()->get($slug);
                    $plugin->bootstrap($this);
                } catch (Throwable $e) {
                    Log::warning('Addon installed but bootstrap failed: ' . $slug . ' (' . $e->getMessage() . ')');
                }
            } catch (PackageException $e) {
                Log::warning('Addon detected but invalid: ' . $slug . ' (' . $e->messageKey . ')');
            } catch (Throwable $e) {
                Log::error('Addon sync failed: ' . $slug . ' (' . $e->getMessage() . ')');
            }
        }

        if ($installedAny) {
            Cache::clearAll();
        }
    }

    private function loadEnabledAddons(): array
    {
        if (!Configure::read('Install.dbConfigured') || !Configure::read('Install.installed')) {
            return [];
        }

        $cacheKey = 'runtime_enabled_addons';
        $cached = Cache::read($cacheKey);
        if (is_array($cached)) {
            $slugs = array_values(array_unique(array_filter(array_map('strval', $cached))));
            foreach ($slugs as $slug) {
                if ($this->addonExists($slug)) {
                    $this->addAddonPlugin($slug, true, true);
                }
            }

            return $slugs;
        }

        $slugs = [];

        try {
            $locator = FactoryLocator::get('Table');
            $Plugins = $locator->get('Plugins');

            $rows = $Plugins
                ->find()
                ->select(['name'])
                ->where(['state' => 1])
                ->enableHydration(false)
                ->all()
                ->toList();

            foreach ($rows as $row) {
                $slug = trim((string)($row['name'] ?? ''));
                if ($slug === '') {
                    continue;
                }

                if (!$this->addonExists($slug)) {
                    continue;
                }

                $this->addAddonPlugin($slug, true, true);
                $slugs[] = $slug;
            }
        } catch (Throwable) {
            return [];
        }

        $slugs = array_values(array_unique($slugs));
        Cache::write($cacheKey, $slugs);

        return $slugs;
    }

    private function loadInstalledThemes(): array
    {
        $dir = rtrim((string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes'), DS);
        if (!is_dir($dir)) {
            return [];
        }

        $entries = scandir($dir) ?: [];
        $slugs = [];

        foreach ($entries as $slug) {
            if (!is_string($slug) || $slug === '.' || $slug === '..' || $slug === '.gitkeep') {
                continue;
            }

            if (!$this->themeExists($slug)) {
                continue;
            }

            $this->addThemePlugin($slug);
            $slugs[] = $slug;
        }

        return array_values(array_unique($slugs));
    }

    private function addAddonPlugin(string $slug, bool $bootstrap = true, bool $routes = true): void
    {
        $base = rtrim((string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons'), DS);
        $path = $base . DS . $slug;

        $this->registerPluginAutoload($slug, $path);

        if (!$this->getPlugins()->has($slug)) {
            $this->addPlugin($slug, [
                'path' => $path,
                'bootstrap' => $bootstrap,
                'routes' => $routes,
            ]);
        }
    }

    private function addThemePlugin(string $slug): void
    {
        $base = rtrim((string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes'), DS);
        $path = $base . DS . $slug;

        $this->registerPluginAutoload($slug, $path);

        if (!$this->getPlugins()->has($slug)) {
            $this->addPlugin($slug, [
                'path' => $path,
                'bootstrap' => true,
                'routes' => false,
            ]);
        }
    }

    private function registerPluginAutoload(string $slug, string $pluginPath): void
    {
        $src = rtrim($pluginPath, DS) . DS . 'src' . DS;
        if (!is_dir($src)) {
            return;
        }

        $loader = $this->composerLoader();
        if ($loader === null) {
            return;
        }

        $loader->addPsr4($slug . '\\', $src);
    }

    private function composerLoader(): ?ClassLoader
    {
        if (!class_exists(ClassLoader::class)) {
            return null;
        }

        $loaders = ClassLoader::getRegisteredLoaders();
        if ($loaders === []) {
            return null;
        }

        foreach ($loaders as $loader) {
            return $loader instanceof ClassLoader ? $loader : null;
        }

        return null;
    }

    private function addonExists(string $slug): bool
    {
        $base = rtrim((string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons'), DS);

        return is_dir($base . DS . $slug);
    }

    private function themeExists(string $slug): bool
    {
        $base = rtrim((string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes'), DS);

        return is_dir($base . DS . $slug);
    }
}
