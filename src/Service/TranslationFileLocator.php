<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;

final class TranslationFileLocator
{
    private ?string $theme = null;

    public function locate(string $locale, string $domain): array
    {
        $locale = $this->normalizeLocale($locale);
        $domain = $this->normalizeDomain($domain);

        $files = [];

        $files = array_merge($files, $this->appFiles($locale, $domain));
        $files = array_merge($files, $this->addonFiles($locale, $domain));
        $files = array_merge($files, $this->themeFiles($locale, $domain));

        return array_values(array_unique(array_filter($files, static fn($p) => is_string($p) && $p !== '' && is_file($p))));
    }

    public function contextKey(): string
    {
        $addons = $this->enabledAddons();
        sort($addons);

        $theme = $this->activeTheme();

        return sha1($theme . '|' . implode(',', $addons));
    }

    public function activeTheme(): string
    {
        if ($this->theme !== null) {
            return $this->theme;
        }

        $runtimeTheme = Configure::read('Runtime.theme');
        if (is_string($runtimeTheme) && $runtimeTheme !== '') {
            return $this->theme = $runtimeTheme;
        }

        $theme = 'default';

        if (InstallState::isInstalled()) {
            $cfg = new ConfigurationService();
            $t = $cfg->getThemeName();
            if ($t !== '') {
                $theme = $t;
            }
        }

        $themesFolder = rtrim((string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes'), DS);
        if ($theme !== 'default' && !is_dir($themesFolder . DS . $theme)) {
            $theme = 'default';
        }

        Configure::write('Runtime.theme', $theme);

        return $this->theme = $theme;
    }

    private function enabledAddons(): array
    {
        $runtime = Configure::read('RuntimePlugins.addons', []);
        if (!is_array($runtime)) {
            return [];
        }

        $addonsFolder = rtrim((string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons'), DS);

        $out = [];
        foreach ($runtime as $slug) {
            $slug = trim((string)$slug);
            if ($slug === '' || !preg_match('/^[A-Za-z0-9_]+$/', $slug)) {
                continue;
            }
            if (!is_dir($addonsFolder . DS . $slug)) {
                continue;
            }
            $out[] = $slug;
        }

        return array_values(array_unique($out));
    }

    private function appFiles(string $locale, string $domain): array
    {
        $base = ROOT . DS . 'resources' . DS . 'locales' . DS . $locale;

        $files = [
            $base . DS . $domain . '.json',
        ];

        if ($domain !== 'default') {
            $files[] = $base . DS . 'default.json';
        }

        return $files;
    }

    private function addonFiles(string $locale, string $domain): array
    {
        $addonsFolder = rtrim((string)Configure::read('Update.addons.folder', ROOT . DS . 'plugins' . DS . 'Addons'), DS);

        $files = [];

        foreach ($this->enabledAddons() as $slug) {
            $base = $addonsFolder . DS . $slug . DS . 'resources' . DS . 'locales' . DS . $locale;

            $files[] = $base . DS . $domain . '.json';
            if ($domain !== 'default') {
                $files[] = $base . DS . 'default.json';
            }
        }

        return $files;
    }

    private function themeFiles(string $locale, string $domain): array
    {
        $theme = $this->activeTheme();
        if ($theme === 'default') {
            return [];
        }

        $themesFolder = rtrim((string)Configure::read('Update.themes.folder', ROOT . DS . 'plugins' . DS . 'Themes'), DS);

        $base = $themesFolder . DS . $theme . DS . 'resources' . DS . 'locales' . DS . $locale;

        $files = [
            $base . DS . $domain . '.json',
        ];

        if ($domain !== 'default') {
            $files[] = $base . DS . 'default.json';
        }

        return $files;
    }

    private function normalizeLocale(string $locale): string
    {
        $locale = str_replace('-', '_', trim($locale));

        return $locale !== '' ? $locale : 'fr_FR';
    }

    private function normalizeDomain(string $domain): string
    {
        $domain = trim($domain);

        return $domain !== '' ? $domain : 'default';
    }
}
