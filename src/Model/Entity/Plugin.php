<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property string $name
 * @property string|null $author
 * @property string|null $version
 * @property string|null $state
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 */
class Plugin extends Entity
{
    protected array $_accessible = [
        'name' => true,
        'author' => true,
        'version' => true,
        'state' => true,
        'created_at' => false,
        'updated_at' => false,
    ];
}
