<?php

use Phinx\Migration\AbstractMigration;

class CreateSlider extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sliders', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table
            ->addColumn('title', 'string', ['null' => false, 'length' => 50])
            ->addColumn('subtitle', 'text', ['null' => false])
            ->addColumn('url_img', 'string', ['null' => false])
            ->create();
    }
}
