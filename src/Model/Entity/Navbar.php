<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

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

    protected function _getUrlData(string $url): array
    {
        if ($this->url == '#') {
            return ['type' => 'submenu'];
        } else {
            return json_decode($this->url, true);
        }
    }
}
