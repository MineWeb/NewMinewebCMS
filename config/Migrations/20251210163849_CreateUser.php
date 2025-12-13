<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateUser extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('users');

        $table
            ->addColumn('username', 'string', ['null' => false])
            ->addColumn('uuid', 'string', ['null' => true, 'default' => null])
            ->addColumn('password', 'string', ['null' => false])
            ->addColumn('password_hash', 'string', ['null' => true, 'default' => null])
            ->addColumn('email', 'string', ['null' => false])
            ->addColumn('role_id', 'integer', ['null' => false, 'default' => 1])
            ->addColumn('money', 'float', ['null' => false, 'default' => 0, 'signed' => false])
            ->addColumn('ip', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('skin', 'integer', ['null' => false, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('cape', 'integer', ['null' => false, 'default' => 0, 'limit' => 1, 'signed' => false])
            ->addColumn('confirmed', 'string', ['limit' => 25, 'null' => true, 'default' => null])
            ->addTimestamps()
            ->addIndex(['username'], ['unique' => true, 'name' => 'users_username_uq'])
            ->addIndex(['email'], ['unique' => true, 'name' => 'users_email_uq'])
            ->addIndex(['uuid'], ['unique' => true, 'name' => 'users_uuid_uq'])
            ->addIndex(['role_id'], ['name' => 'users_role_id_idx'])
            ->addForeignKey('role_id', 'roles', 'id', [
                'delete' => 'RESTRICT',
                'update' => 'CASCADE',
                'constraint' => 'users_role_id_fk',
            ])
            ->create();
    }
}
