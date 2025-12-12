<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSlider extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sliders');

        $table
            ->addColumn('title', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('subtitle', 'text', ['null' => false])
            ->addColumn('url_img', 'string', ['null' => false])
            ->addTimestamps()
            ->create();
    }
}
