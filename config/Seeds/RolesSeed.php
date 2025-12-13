<?php
declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

final class RolesSeed extends AbstractSeed
{
    public function run(): void
    {
        $exists = $this->fetchRow('SELECT id FROM roles LIMIT 1');
        if ($exists !== false) {
            return;
        }

        $this->table('roles')->insert([
            [
                'slug' => 'user',
                'name' => 'User',
                'permissions' => json_encode([
                    'COMMENT_NEWS',
                    'LIKE_NEWS',
                    'DELETE_HIS_COMMENT',
                    'EDIT_HIS_EMAIL',
                ]),
                'is_default' => 1,
                'is_system' => 1,
                'sort' => 100,
            ],
            [
                'slug' => 'moderator',
                'name' => 'Moderator',
                'permissions' => json_encode([
                    'COMMENT_NEWS',
                    'LIKE_NEWS',
                    'DELETE_HIS_COMMENT',
                    'EDIT_HIS_EMAIL',
                ]),
                'is_default' => 0,
                'is_system' => 1,
                'sort' => 50,
            ],
            [
                'slug' => 'admin',
                'name' => 'Admin',
                'permissions' => json_encode(['*']),
                'is_default' => 0,
                'is_system' => 1,
                'sort' => 0,
            ],
        ])->saveData();
    }
}
