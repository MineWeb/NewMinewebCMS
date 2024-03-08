<?php

use Migrations\AbstractMigration;

class CreateMaintenance extends AbstractMigration {
    public function change()
    {
        $table = $this->table('maintenances', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('sub_url', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false]);
        $table->addColumn('url', 'string', ['null' => false, 'default' => null]);
        $table->addColumn('reason', 'text', ['null' => false, 'default' => null]);
        $table->addColumn('active', 'integer', ['null' => false, 'default' => 1, 'length' => 1, 'signed' => false]);

        $table->create();
    }
}
