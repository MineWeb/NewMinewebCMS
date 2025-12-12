<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateLoginRetry extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('login_retries', [
            'encoding' => 'latin1',
            'collation' => 'latin1_swedish_ci',
            'engine' => 'InnoDB',
        ]);

        $table
            ->addColumn('ip', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('count', 'integer', ['null' => false, 'limit' => 11, 'signed' => false])
            ->addTimestamps()
            ->create();
    }
}
