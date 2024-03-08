<?php

use Migrations\AbstractMigration;

class CreateSeo extends AbstractMigration {
    public function change()
    {
        $table = $this->table('seo', ['encoding' => 'latin1', 'collation' => 'latin1_swedish_ci', 'engine' => 'InnoDB']);
        $table->addColumn('title', 'string', ['null' => true, 'default' => null, 'length' => 255]);
        $table->addColumn('description', 'text', ['null' => true, 'default' => null]);
        $table->addColumn('favicon_url', 'string', ['null' => true, 'default' => null, 'length' => 255]);
        $table->addColumn('img_url', 'string', ['null' => true, 'default' => null, 'length' => 255]);
        $table->addColumn('theme_color', 'string', ['null' => true, 'default' => null, 'length' => 255]);
        $table->addColumn('twitter_site', 'string', ['null' => true, 'default' => null, 'length' => 255]);
        $table->addColumn('page', 'string', ['null' => true, 'default' => null, 'length' => 255]);

        $table->create();
    }
}
