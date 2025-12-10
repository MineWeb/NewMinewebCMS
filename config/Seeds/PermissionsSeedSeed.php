<?php
declare(strict_types=1);

use Migrations\AbstractSeed;

class PermissionsSeed extends AbstractSeed
{
    public function run(): void
    {
        $data = [
            [
                'rank' => 0,
                'permissions' => serialize([
                    'COMMENT_NEWS',
                    'LIKE_NEWS',
                    'DELETE_HIS_COMMENT',
                    'EDIT_HIS_EMAIL'
                ])
            ],
            [
                'rank' => 2,
                'permissions' => serialize([
                    'COMMENT_NEWS',
                    'LIKE_NEWS',
                    'DELETE_HIS_COMMENT',
                    'EDIT_HIS_EMAIL'
                ])
            ],
        ];

        $table = $this->table('permissions');
        $table->insert($data)->save();
    }
}
