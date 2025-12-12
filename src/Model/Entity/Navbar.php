<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int|null $order_by
 * @property string $name
 * @property string|null $icon
 * @property string $type
 * @property string $url
 * @property string|null $submenu
 * @property bool|null $open_new_tab
 *
 * @property array $url_data
 */
class Navbar extends Entity
{
    protected array $_accessible = [
        'order_by' => true,
        'name' => true,
        'icon' => true,
        'type' => true,
        'url' => true,
        'submenu' => true,
        'open_new_tab' => true,
    ];

    protected array $_virtual = [
        'url_data',
    ];

    protected function _getUrlData(?string $url): array
    {
        if ($this->url === '#') {
            return ['type' => 'submenu'];
        }

        $decoded = json_decode($this->url, true);

        return is_array($decoded) ? $decoded : [];
    }
}
