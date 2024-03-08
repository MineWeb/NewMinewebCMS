<?php

use Migrations\AbstractMigration;

class CreateSocial extends AbstractMigration {
    public function change()
    {
        $table = $this->table('social_buttons', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('order', 'integer', ['null' => false, 'default' => null, 'length' => 2, 'signed' => false]);
        $table->addColumn('title', 'string', ['null' => true, 'default' => null, 'length' => 20]);
        $table->addColumn('extra', 'string', ['null' => true, 'default' => null, 'length' => 120]);
        $table->addColumn('color', 'string', ['null' => true, 'default' => null, 'length' => 30]);
        $table->addColumn('url', 'string', ['null' => true, 'default' => null, 'length' => 120]);

        $table->create();
    }
}
