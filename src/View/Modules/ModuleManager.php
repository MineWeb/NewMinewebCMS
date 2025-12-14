<?php
declare(strict_types=1);

namespace App\View\Modules;

use Cake\Core\Configure;
use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

final class ModuleManager
{
    use LocatorAwareTrait;

    public function listModules(): array
    {
        $modules = [];
        $addonsFolder = rtrim((string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons'), DS);

        foreach ($this->activeAddonSlugs() as $slug) {
            $slug = trim((string)$slug);
            if ($slug === '' || !preg_match('/^[A-Za-z0-9_]+$/', $slug)) {
                continue;
            }

            $root = $addonsFolder . DS . $slug;

            $dirs = [
                $root . DS . 'templates' . DS . 'Modules',
                $root . DS . 'Modules',
            ];

            foreach ($dirs as $dir) {
                if (!is_dir($dir)) {
                    continue;
                }

                $files = scandir($dir);
                if ($files === false) {
                    continue;
                }

                foreach ($files as $filename) {
                    if (!is_string($filename) || $filename === '.' || $filename === '..' || $filename === '.DS_Store') {
                        continue;
                    }

                    $ext = strtolower((string)pathinfo($filename, PATHINFO_EXTENSION));
                    if ($ext !== 'php' && $ext !== 'ctp') {
                        continue;
                    }

                    $moduleName = (string)pathinfo($filename, PATHINFO_FILENAME);
                    if ($moduleName === '' || !preg_match('/^[a-zA-Z0-9_-]+$/', $moduleName)) {
                        continue;
                    }

                    $modules[$moduleName] ??= [];
                    if (!in_array($slug, $modules[$moduleName], true)) {
                        $modules[$moduleName][] = $slug;
                    }
                }
            }
        }

        return $modules;
    }

    public function resolveModuleFile(string $theme, string $pluginSlug, string $moduleName): ?string
    {
        $theme = trim($theme);
        $pluginSlug = trim($pluginSlug);
        $moduleName = trim($moduleName);

        if (
            $pluginSlug === ''
            || $moduleName === ''
            || !preg_match('/^[A-Za-z0-9_]+$/', $pluginSlug)
            || !preg_match('/^[a-zA-Z0-9_-]+$/', $moduleName)
        ) {
            return null;
        }

        $addonsFolder = rtrim((string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons'), DS);
        $themesFolder = rtrim((string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes'), DS);

        $candidates = [];

        if ($theme !== '' && $theme !== 'default') {
            $themeRoot = $themesFolder . DS . $theme;

            $candidates[] = $themeRoot . DS . 'templates' . DS . 'Plugin' . DS . $pluginSlug . DS . 'Modules' . DS . $moduleName . '.php';
            $candidates[] = $themeRoot . DS . 'templates' . DS . 'Plugin' . DS . $pluginSlug . DS . 'Modules' . DS . $moduleName . '.ctp';

            $candidates[] = $themeRoot . DS . 'templates' . DS . 'Modules' . DS . $pluginSlug . DS . $moduleName . '.php';
            $candidates[] = $themeRoot . DS . 'templates' . DS . 'Modules' . DS . $pluginSlug . DS . $moduleName . '.ctp';
        }

        $addonRoot = $addonsFolder . DS . $pluginSlug;

        $candidates[] = $addonRoot . DS . 'templates' . DS . 'Modules' . DS . $moduleName . '.php';
        $candidates[] = $addonRoot . DS . 'templates' . DS . 'Modules' . DS . $moduleName . '.ctp';

        $candidates[] = $addonRoot . DS . 'Modules' . DS . $moduleName . '.php';
        $candidates[] = $addonRoot . DS . 'Modules' . DS . $moduleName . '.ctp';

        foreach ($candidates as $file) {
            if (is_file($file)) {
                return $file;
            }
        }

        return null;
    }

    private function activeAddonSlugs(): array
    {
        $runtime = Configure::read('RuntimePlugins.addons');
        if (is_array($runtime)) {
            return array_values(array_unique(array_filter(array_map('strval', $runtime), static fn($v) => $v !== '')));
        }

        if (!Configure::read('Install.dbConfigured') || !Configure::read('Install.installed')) {
            return [];
        }

        $addonsFolder = rtrim((string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons'), DS);

        try {
            $Plugins = $this->fetchTable('Plugins');

            $rows = $Plugins
                ->find()
                ->select(['name'])
                ->where(['state' => 1])
                ->enableHydration(false)
                ->all()
                ->toList();

            $slugs = [];
            foreach ($rows as $row) {
                $slug = trim((string)($row['name'] ?? ''));
                if ($slug === '') {
                    continue;
                }

                if (!is_dir($addonsFolder . DS . $slug)) {
                    continue;
                }

                $slugs[] = $slug;
            }

            return array_values(array_unique($slugs));
        } catch (Throwable) {
            return [];
        }
    }
}
