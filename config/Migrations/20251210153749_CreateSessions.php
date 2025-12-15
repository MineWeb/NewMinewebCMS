<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateSessions extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('sessions', [
            'id' => false,
            'primary_key' => ['id'],
            'engine' => 'InnoDB',
            'encoding' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
        ]);

        $table
            ->addColumn('id', 'string', [
                'limit' => 40,
                'null' => false,
                'encoding' => 'ascii',
                'collation' => 'ascii_bin',
            ])
            ->addColumn('created', 'datetime', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('modified', 'datetime', [
                'null' => true,
                'default' => 'CURRENT_TIMESTAMP',
                'update' => 'CURRENT_TIMESTAMP',
            ])
            ->addColumn('data', 'blob', [
                'null' => true,
                'default' => null,
            ])
            ->addColumn('expires', 'integer', [
                'null' => true,
                'signed' => false,
                'limit' => 10,
            ])
            ->addIndex(['expires'])
            ->create();
    }
}
