<?php

use Migrations\AbstractMigration;

class CreateNavbar extends AbstractMigration {
    public function change()
    {
        $table = $this->table('navbars', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('order_by', 'integer', ['null' => false, 'default' => null, 'length' => 2, 'signed' => false]);
        $table->addColumn('name', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('icon', 'string', ['null' => true, 'default' => null, 'length' => 50]);
        $table->addColumn('type', 'integer', ['null' => false, 'default' => '1', 'length' => 1, 'signed' => false]);
        $table->addColumn('url', 'string', ['null' => false, 'default' => null, 'length' => 250]);
        $table->addColumn('submenu', 'text', ['null' => true, 'default' => null]);
        $table->addColumn('open_new_tab', 'integer', ['null' => true, 'default' => '0', 'length' => 1, 'signed' => false]);

        $table->create();
    }
}
