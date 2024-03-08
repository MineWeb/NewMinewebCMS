<?php

use Migrations\AbstractMigration;

class CreateSlider extends AbstractMigration {
    public function change()
    {
        $table = $this->table('sliders', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('title', 'string', ['null' => false, 'default' => null, 'length' => 50]);
        $table->addColumn('subtitle', 'text', ['null' => false, 'default' => null]);
        $table->addColumn('url_img', 'string', ['null' => false, 'default' => null]);

        $table->create();
    }
}
