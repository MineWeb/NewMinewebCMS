<?php

use Migrations\AbstractMigration;

class CreatePermission extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('permissions', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('rank', 'integer', ['null' => false, 'length' => 1, 'signed' => false])
            ->addColumn('permissions', 'text', ['null' => false])
            ->create();
    }
}
