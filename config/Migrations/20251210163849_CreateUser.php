<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUser extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('pseudo', 'string', ['null' => false])
            ->addColumn('uuid', 'string', ['null' => true, 'default' => null])
            ->addColumn('password', 'string', ['null' => false])
            ->addColumn('password_hash', 'string', ['null' => true, 'default' => null])
            ->addColumn('email', 'string', ['null' => false])
            ->addColumn('rank', 'integer', ['null' => false, 'limit' => 1, 'signed' => false])
            ->addColumn('money', 'float', ['null' => false, 'default' => 0, 'signed' => false])
            ->addColumn('ip', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('skin', 'integer', ['null' => false, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('cape', 'integer', ['null' => false, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('confirmed', 'string', ['limit' => 25, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->create();
    }
}
