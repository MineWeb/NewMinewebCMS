<?php

use Migrations\AbstractMigration;

class CreatePermission extends AbstractMigration {
    public function change()
    {
        $table = $this->table('permissions', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('rank', 'integer', ['null' => false, 'default' => null, 'length' => 1, 'signed' => false]);
        $table->addColumn('permissions', 'text', ['null' => false, 'default' => null]);

        $table->create();

        $table->insert([
            [
                'rank' => '0',
                'permissions' => serialize(['COMMENT_NEWS', 'LIKE_NEWS', 'DELETE_HIS_COMMENT', 'EDIT_HIS_EMAIL'])
            ],
            [
                'rank' => '2',
                'permissions' => serialize(['COMMENT_NEWS', 'LIKE_NEWS', 'DELETE_HIS_COMMENT', 'EDIT_HIS_EMAIL'])
            ]
        ]);
        $table->saveData();
    }
}
