<?php

use Phinx\Migration\AbstractMigration;

class CreateMaintenance extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('maintenances', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('sub_url', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('url', 'string', ['null' => false])
            ->addColumn('reason', 'text', ['null' => false])
            ->addColumn('active', 'integer', ['null' => false, 'default' => 1, 'length' => 1, 'signed' => false])
            ->create();
    }
}
