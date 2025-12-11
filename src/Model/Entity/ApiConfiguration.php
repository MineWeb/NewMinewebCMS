<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

class ApiConfiguration extends Entity
{
    protected array $_accessible = [
        'skins' => true,
        'skin_filename' => true,
        'skin_free' => true,
        'skin_width' => true,
        'skin_height' => true,
        'capes' => true,
        'cape_filename' => true,
        'cape_free' => true,
        'cape_width' => true,
        'cape_height' => true,
        'get_premium_skins' => true,
        'use_skin_restorer' => true,
        'skin_restorer_server_id' => true,
        'server' => true,
    ];
}
