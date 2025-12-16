<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;

final class AdminNavbarService
{
    public function getNav(): array
    {
        $nav = $this->baseNav();

        $nav = $this->injectThemeMenus($nav);

        $addons = new AddonService();
        $pluginMenus = $addons->findPluginsAdminMenus();

        if ($pluginMenus === []) {
            return $nav;
        }

        $key = 'GLOBAL__ADMIN_PLUGINS';

        if (!isset($nav[$key]) || !is_array($nav[$key])) {
            $nav[$key] = ['icon' => 'puzzle-piece', 'menu' => []];
        }

        if (!isset($nav[$key]['menu']) || !is_array($nav[$key]['menu'])) {
            $nav[$key]['menu'] = [];
        }

        $nav[$key]['menu'] = $this->mergeMenusAtIndexes((array)$nav[$key]['menu'], $pluginMenus);

        return $nav;
    }

    private function injectThemeMenus(array $nav): array
    {
        $slug = (string)Configure::read('theme', 'default');

        $theme = new ThemeService();
        [, $config] = $theme->getCustomData($slug);

        $sliderEnabled = !empty($config['slider']);
        if (!$sliderEnabled) {
            return $nav;
        }

        $groupKey = 'GLOBAL__CUSTOMIZE';

        if (!isset($nav[$groupKey]) || !is_array($nav[$groupKey])) {
            $nav[$groupKey] = [
                'icon' => 'fas fa-paint-brush',
                'menu' => [],
            ];
        }

        if (!isset($nav[$groupKey]['menu']) || !is_array($nav[$groupKey]['menu'])) {
            $nav[$groupKey]['menu'] = [];
        }

        if (isset($nav[$groupKey]['menu']['SLIDER__TITLE'])) {
            return $nav;
        }

        $nav[$groupKey]['menu']['SLIDER__TITLE'] = [
            'icon' => 'far fa-image',
            'permission' => 'MANAGE_SLIDER',
            'route' => [
                'controller' => 'Slider',
                'action' => 'index',
                'prefix' => 'Admin',
                'plugin' => null,
            ],
        ];

        return $nav;
    }

    private function baseNav(): array
    {
        $nav = Configure::read('AdminNav');
        if (is_array($nav)) {
            return $nav;
        }

        return [];
    }

    private function mergeMenusAtIndexes(array $existingMenu, array $pluginMenus): array
    {
        $list = [];
        foreach ($existingMenu as $label => $node) {
            if (!is_array($node)) {
                continue;
            }
            $list[] = ['label' => (string)$label, 'node' => $node];
        }

        foreach ($pluginMenus as $pm) {
            $label = trim((string)($pm['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $node = [
                'icon' => (string)($pm['icon'] ?? ''),
                'menu' => $this->toMenuAssoc((array)($pm['menu'] ?? [])),
            ];

            if (isset($pm['permission']) && is_string($pm['permission']) && trim($pm['permission']) !== '') {
                $node['permission'] = trim($pm['permission']);
            }

            $idx = isset($pm['index']) && is_int($pm['index']) ? $pm['index'] : null;

            if ($this->labelExists($list, $label)) {
                $label = $label . ' (' . sha1($label) . ')';
            }

            if ($idx === null) {
                $list[] = ['label' => $label, 'node' => $node];
                continue;
            }

            $idx = max(0, min($idx, count($list)));
            array_splice($list, $idx, 0, [[ 'label' => $label, 'node' => $node ]]);
        }

        $out = [];
        foreach ($list as $row) {
            $out[(string)$row['label']] = (array)$row['node'];
        }

        return $out;
    }

    private function toMenuAssoc(array $items): array
    {
        $out = [];

        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $label = trim((string)($item['label'] ?? ''));
            if ($label === '') {
                continue;
            }

            $node = [];

            if (isset($item['icon'])) {
                $node['icon'] = (string)$item['icon'];
            }

            if (isset($item['permission']) && is_string($item['permission']) && trim($item['permission']) !== '') {
                $node['permission'] = trim($item['permission']);
            }

            if (isset($item['route']) && is_array($item['route'])) {
                if (isset($item['route']['_path']) && is_string($item['route']['_path']) && $item['route']['_path'] !== '') {
                    $node['route'] = (string)$item['route']['_path'];
                } else {
                    $node['route'] = $item['route'];
                }
            }

            if (isset($item['menu']) && is_array($item['menu']) && $item['menu'] !== []) {
                $node['menu'] = $this->toMenuAssoc($item['menu']);
            }

            $out[$label] = $node;
        }

        return $out;
    }

    private function labelExists(array $list, string $label): bool
    {
        foreach ($list as $row) {
            if (($row['label'] ?? null) === $label) {
                return true;
            }
        }

        return false;
    }
}
