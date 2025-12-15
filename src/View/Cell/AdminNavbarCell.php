<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Service\AdminNavbarService;
use Cake\Routing\Router;
use Cake\View\Cell;
use Throwable;

final class AdminNavbarCell extends Cell
{
    public function display(): void
    {
        $service = new AdminNavbarService();
        $raw = $service->getNav();

        $request = Router::getRequest();
        $currentPath = $request
            ? $request->getUri()->getPath()
            : '';

        $items = $this->normalizeNav($raw, $currentPath);

        $this->set(compact('items'));
    }

    private function normalizeNav(array $nav, string $currentPath): array
    {
        $items = [];

        foreach ($nav as $label => $node) {
            if (!is_array($node)) {
                continue;
            }

            $hasMenu = isset($node['menu']) && is_array($node['menu']);
            $hasRoute = isset($node['route']);

            if (!$hasMenu && !$hasRoute) {
                continue;
            }

            $url = $this->safeUrl($node['route'] ?? null);
            $urlPath = $url !== '#'
                ? (string)(parse_url($url, PHP_URL_PATH) ?? '')
                : '';

            $children = $hasMenu
                ? $this->normalizeNav($node['menu'], $currentPath)
                : [];

            $active = $urlPath !== '' && $urlPath === $currentPath;
            $open = !$active && $children !== [] && $this->hasActiveChild($children);

            $items[] = [
                'label' => __((string)$label),
                'icon' => (string)($node['icon'] ?? ''),
                'permission' => isset($node['permission']) ? (string)$node['permission'] : null,
                'url' => $url,
                'children' => $children,
                'active' => $active,
                'open' => $open,
            ];
        }

        return $items;
    }

    private function safeUrl(mixed $route): string
    {
        if ($route === null) {
            return '#';
        }

        try {
            return Router::url($route);
        } catch (Throwable) {
            return '#';
        }
    }

    private function hasActiveChild(array $children): bool
    {
        foreach ($children as $child) {
            if (!empty($child['active']) || !empty($child['open'])) {
                return true;
            }
        }

        return false;
    }
}
