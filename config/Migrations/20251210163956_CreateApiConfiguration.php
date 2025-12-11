<?php

use Phinx\Migration\AbstractMigration;

class CreateApiConfiguration extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('api_configurations', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('skins', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('skin_filename', 'string', ['null' => false, 'length' => 150])
            ->addColumn('skin_free', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('skin_width', 'integer', ['null' => true, 'default' => 64, 'signed' => false])
            ->addColumn('skin_height', 'integer', ['null' => true, 'default' => 32, 'signed' => false])
            ->addColumn('capes', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('cape_filename', 'string', ['null' => false, 'length' => 150])
            ->addColumn('cape_free', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('cape_width', 'integer', ['null' => true, 'default' => 64, 'signed' => false])
            ->addColumn('cape_height', 'integer', ['null' => true, 'default' => 32, 'signed' => false])
            ->addColumn('get_premium_skins', 'integer', ['null' => false, 'default' => 1, 'length' => 1, 'signed' => false])
            ->addColumn('use_skin_restorer', 'integer', ['null' => false, 'default' => 0, 'length' => 1, 'signed' => false])
            ->addColumn('skin_restorer_server_id', 'integer', ['null' => true, 'default' => null, 'length' => 8, 'signed' => false])
            ->addIndex(['skin_restorer_server_id'])
            ->create();

        $table
            ->addForeignKey('skin_restorer_server_id', 'servers', 'id', ['delete' => 'SET_NULL', 'update' => 'NO_ACTION'])
            ->update();
    }
}
