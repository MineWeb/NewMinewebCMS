<?php
declare(strict_types=1);

namespace App;

use App\Middleware\AuthContextMiddleware;
use App\Middleware\BanMiddleware;
use App\Middleware\InstallMiddleware;
use App\Middleware\MaintenanceMiddleware;
use Cake\Cache\Cache;
use Cake\Core\Configure;
use Cake\Core\ContainerInterface;
use Cake\Datasource\FactoryLocator;
use Cake\Error\Middleware\ErrorHandlerMiddleware;
use Cake\Http\BaseApplication;
use Cake\Http\Middleware\BodyParserMiddleware;
use Cake\Http\Middleware\CsrfProtectionMiddleware;
use Cake\Http\MiddlewareQueue;
use Cake\ORM\Locator\TableLocator;
use Cake\Routing\Middleware\AssetMiddleware;
use Cake\Routing\Middleware\RoutingMiddleware;
use Composer\Autoload\ClassLoader;
use Throwable;

final class Application extends BaseApplication
{
    public function bootstrap(): void
    {
        parent::bootstrap();

        if (PHP_SAPI === 'cli') {
            $this->bootstrapCli();
        } else {
            FactoryLocator::add('Table', (new TableLocator())->allowFallbackClass(false));
        }

        if (Configure::read('debug')) {
            $this->addPlugin('DebugKit');
        }

        $addons = $this->loadEnabledAddons();
        $themes = $this->loadInstalledThemes();

        Configure::write('RuntimePlugins.addons', $addons);
        Configure::write('RuntimePlugins.themes', $themes);
    }

    public function middleware(MiddlewareQueue $middlewareQueue): MiddlewareQueue
    {
        $middlewareQueue
            ->add(new InstallMiddleware())
            ->add(new ErrorHandlerMiddleware(Configure::read('Error')))
            ->add(new AssetMiddleware([
                'cacheTime' => Configure::read('Asset.cacheTime'),
            ]))
            ->add(new AuthContextMiddleware())
            ->add(new RoutingMiddleware($this))
            ->add(new CsrfProtectionMiddleware([
                'httponly' => true,
            ]))
            ->add(new BanMiddleware())
            ->add(new MaintenanceMiddleware())
            ->add(new BodyParserMiddleware());

        return $middlewareQueue;
    }

    public function services(ContainerInterface $container): void
    {
    }

    protected function bootstrapCli(): void
    {
        $this->addOptionalPlugin('Cake/Repl');
        $this->addOptionalPlugin('Bake');
        $this->addPlugin('Migrations');

        $addons = $this->loadEnabledAddons();
        $themes = $this->loadInstalledThemes();

        Configure::write('RuntimePlugins.addons', $addons);
        Configure::write('RuntimePlugins.themes', $themes);
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
                    $this->addAddonPlugin($slug);
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

                $this->addAddonPlugin($slug);
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
        $dir = ROOT . DS . 'plugins' . DS . 'Themes';
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

    private function addAddonPlugin(string $slug): void
    {
        $path = ROOT . DS . 'plugins' . DS . 'Addons' . DS . $slug;

        $this->registerPluginAutoload($slug, $path);

        if (!$this->getPlugins()->has($slug)) {
            $this->addPlugin($slug, [
                'path' => $path,
                'bootstrap' => true,
                'routes' => true,
            ]);
        }
    }

    private function addThemePlugin(string $slug): void
    {
        $path = ROOT . DS . 'plugins' . DS . 'Themes' . DS . $slug;

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
        return is_dir(ROOT . DS . 'plugins' . DS . 'Addons' . DS . $slug);
    }

    private function themeExists(string $slug): bool
    {
        return is_dir(ROOT . DS . 'plugins' . DS . 'Themes' . DS . $slug);
    }
}
