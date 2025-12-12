<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property bool $skins
 * @property string|null $skin_filename
 * @property bool $skin_free
 * @property int|null $skin_width
 * @property int|null $skin_height
 * @property bool $capes
 * @property string|null $cape_filename
 * @property bool $cape_free
 * @property int|null $cape_width
 * @property int|null $cape_height
 * @property bool $get_premium_skins
 * @property bool $use_skin_restorer
 * @property int|null $skin_restorer_server_id
 *
 * @property \App\Model\Entity\Server|null $server
 */
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
