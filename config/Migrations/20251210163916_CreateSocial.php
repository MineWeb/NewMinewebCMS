<?php

use Migrations\AbstractMigration;

class CreateSocial extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('social_buttons', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('order', 'integer', ['null' => false, 'length' => 2, 'signed' => false])
            ->addColumn('title', 'string', ['null' => true, 'default' => null, 'length' => 20])
            ->addColumn('extra', 'string', ['null' => true, 'default' => null, 'length' => 120])
            ->addColumn('color', 'string', ['null' => true, 'default' => null, 'length' => 30])
            ->addColumn('url', 'string', ['null' => true, 'default' => null, 'length' => 120])
            ->create();
    }
}
