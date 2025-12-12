<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateNavbar extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('navbars', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('order_by', 'integer', ['null' => false, 'limit' => 2, 'signed' => false])
            ->addColumn('name', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('icon', 'string', ['null' => true, 'default' => null, 'limit' => 50])
            ->addColumn('type', 'integer', ['null' => false, 'default' => 1, 'limit' => 1, 'signed' => false])
            ->addColumn('url', 'string', ['null' => false, 'limit' => 250])
            ->addColumn('submenu', 'text', ['null' => true, 'default' => null])
            ->addColumn('open_new_tab', 'integer', ['null' => true, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addTimestamps()
            ->create();
    }
}
