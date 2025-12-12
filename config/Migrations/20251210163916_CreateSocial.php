<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSocial extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('social_buttons', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('order', 'integer', ['null' => false, 'limit' => 2, 'signed' => false])
            ->addColumn('title', 'string', ['null' => true, 'default' => null, 'limit' => 20])
            ->addColumn('extra', 'string', ['null' => true, 'default' => null, 'limit' => 120])
            ->addColumn('color', 'string', ['null' => true, 'default' => null, 'limit' => 30])
            ->addColumn('url', 'string', ['null' => true, 'default' => null, 'limit' => 120])
            ->addTimestamps()
            ->create();
    }
}
