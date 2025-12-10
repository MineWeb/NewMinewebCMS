<?php

use Migrations\AbstractMigration;

class CreateNavbar extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('navbars', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('order_by', 'integer', ['null' => false, 'length' => 2, 'signed' => false])
            ->addColumn('name', 'string', ['null' => false, 'length' => 50])
            ->addColumn('icon', 'string', ['null' => true, 'default' => null, 'length' => 50])
            ->addColumn('type', 'integer', ['null' => false, 'default' => 1, 'length' => 1, 'signed' => false])
            ->addColumn('url', 'string', ['null' => false, 'length' => 250])
            ->addColumn('submenu', 'text', ['null' => true, 'default' => null])
            ->addColumn('open_new_tab', 'integer', ['null' => true, 'default' => 0, 'length' => 1, 'signed' => false])
            ->create();
    }
}
