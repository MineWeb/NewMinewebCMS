<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;

final class NavbarService
{
    use LocatorAwareTrait;

    public function getNavbar(): array
    {
        $Navbars = $this->fetchTable('Navbars');
        $Pages = $this->fetchTable('Pages');

        $nav = $Navbars->find()
            ->orderBy(['Navbars.order_by' => 'ASC'])
            ->toArray();

        if ($nav === []) {
            return [];
        }

        $pages = $Pages->find()
            ->select(['id', 'slug'])
            ->all();

        $pagesMap = [];
        foreach ($pages as $page) {
            $pagesMap[$page->id] = $page->slug;
        }

        foreach ($nav as &$item) {
            if (!isset($item['urlData']['type'])) {
                continue;
            }

            switch ($item['urlData']['type']) {
                case 'page':
                    $item['url'] = isset($pagesMap[$item['urlData']['id']])
                        ? Router::url(['_name' => 'pages_index', $pagesMap[$item['urlData']['id']]])
                        : '#';
                    break;

                case 'custom':
                    $item['url'] = $item['urlData']['url'];
                    break;

                case 'plugin':
                    $item['url'] = Router::url('/' . strtolower((string)$item['urlData']['id']));
                    break;

                default:
                    $item['url'] = '#';
            }
        }

        return $nav;
    }
}
