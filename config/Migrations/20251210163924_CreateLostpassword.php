<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLostpassword extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('lostpasswords', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('email', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('key', 'string', ['null' => false, 'limit' => 10])
            ->addTimestamps()
            ->create();
    }
}
