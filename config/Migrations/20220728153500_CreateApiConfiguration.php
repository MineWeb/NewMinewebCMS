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

        $table->insert([
            'skins' => 0,
            'skin_filename' => 'skins/{PLAYER}_skin',
            'skin_free' => 0,
            'skin_width' => 64,
            'skin_height' => 32,
            'capes' => 0,
            'cape_filename' => 'skins/capes/{PLAYER}_cape',
            'cape_free' => 0,
            'cape_width' => '64',
            'cape_height' => '32',
            'get_premium_skins' => 1,
            'use_skin_restorer' => 0,
            'skin_restorer_server_id' => 0,
        ]);
        $table->saveData();
    }
}
