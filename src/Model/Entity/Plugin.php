<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property \Cake\I18n\DateTime $created
 * @property string $name
 * @property string|null $author
 * @property string|null $version
 * @property string|null $state
 */
class Plugin extends Entity
{
    protected array $_accessible = [
        'created' => true,
        'name' => true,
        'author' => true,
        'version' => true,
        'state' => true,
    ];
}
