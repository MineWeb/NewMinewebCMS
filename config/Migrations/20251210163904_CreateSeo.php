<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSeo extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('seo', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('title', 'string', ['null' => true, 'default' => null, 'limit' => 255])
            ->addColumn('description', 'text', ['null' => true, 'default' => null])
            ->addColumn('favicon_url', 'string', ['null' => true, 'default' => null, 'limit' => 255])
            ->addColumn('img_url', 'string', ['null' => true, 'default' => null, 'limit' => 255])
            ->addColumn('theme_color', 'string', ['null' => true, 'default' => null, 'limit' => 255])
            ->addColumn('twitter_site', 'string', ['null' => true, 'default' => null, 'limit' => 255])
            ->addColumn('page', 'string', ['null' => true, 'default' => null, 'limit' => 255])
            ->addTimestamps()
            ->create();
    }
}
