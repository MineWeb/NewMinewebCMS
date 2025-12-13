<?php
declare(strict_types=1);

use Phinx\Seed\AbstractSeed;

class PermissionsSeed extends AbstractSeed
{
    public function run(): void
    {
        $exists = $this->fetchRow(
            'SELECT id FROM permissions WHERE rank = 0 LIMIT 1'
        );

        if ($exists !== false) {
            return;
        }

        $this->table('permissions')->insert([
            [
                'rank' => 0,
                'permissions' => serialize([
                    'COMMENT_NEWS',
                    'LIKE_NEWS',
                    'DELETE_HIS_COMMENT',
                    'EDIT_HIS_EMAIL',
                ]),
            ],
            [
                'rank' => 2,
                'permissions' => serialize([
                    'COMMENT_NEWS',
                    'LIKE_NEWS',
                    'DELETE_HIS_COMMENT',
                    'EDIT_HIS_EMAIL',
                ]),
            ],
        ])->saveData();
    }
}
