<?php
declare(strict_types=1);

namespace App\Model\Entity;

use Cake\ORM\Entity;

/**
 * @property int $id
 * @property int $user_id
 * @property string $reason
 * @property string|null $ip
 * @property \Cake\I18n\FrozenTime|null $created_at
 * @property \Cake\I18n\FrozenTime|null $updated_at
 *
 * @property \App\Model\Entity\User|null $user
 * @property string $username
 */
class Ban extends Entity
{
    protected array $_accessible = [
        'user_id' => true,
        'reason' => true,
        'ip' => true,
        'user' => true,
        'created_at' => false,
        'updated_at' => false,
    ];

    protected array $_virtual = [
        'username',
    ];

    protected function _getUsername(): string
    {
        $user = $this->user ?? null;

        if ($user && isset($user->username)) {
            return $user->username;
        }

        return 'N/A';
    }
}
