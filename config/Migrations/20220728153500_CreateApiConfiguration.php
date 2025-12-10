<?php

use Migrations\AbstractMigration;

class CreateApiConfiguration extends AbstractMigration {
    public function change()
    {
        $table = $this->table('api_configurations', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('skins', 'integer', ['null' => false, 'default' => '0', 'length' => 1, 'signed' => false]);
        $table->addColumn('skin_filename', 'string', ['null' => false, 'default' => null, 'length' => 150]);
        $table->addColumn('skin_free', 'integer', ['null' => false, 'default' => '0', 'length' => 1, 'signed' => false]);
        $table->addColumn('skin_width', 'integer', ['null' => true, 'default' => '64', 'signed' => false]);
        $table->addColumn('skin_height', 'integer', ['null' => true, 'default' => '32', 'signed' => false]);
        $table->addColumn('capes', 'integer', ['null' => false, 'default' => '0', 'length' => 1, 'signed' => false]);
        $table->addColumn('cape_filename', 'string', ['null' => false, 'default' => null, 'length' => 150]);
        $table->addColumn('cape_free', 'integer', ['null' => false, 'default' => '0', 'length' => 1, 'signed' => false]);
        $table->addColumn('cape_width', 'integer', ['null' => true, 'default' => '64', 'signed' => false]);
        $table->addColumn('cape_height', 'integer', ['null' => true, 'default' => '32', 'signed' => false]);
        $table->addColumn('get_premium_skins', 'integer', ['null' => false, 'default' => '1', 'length' => 1, 'signed' => false]);
        $table->addColumn('use_skin_restorer', 'integer', ['null' => false, 'default' => '0', 'length' => 1, 'signed' => false]);
        $table->addColumn('skin_restorer_server_id', 'integer', ['null' => false, 'default' => '0', 'length' => 8, 'signed' => false]);

        $table->create();
    }
}
