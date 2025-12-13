<?php
declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class CreateRoles extends AbstractMigration
{
    public function change(): void
    {
        $table = $this->table('roles');

        $table
            ->addColumn('slug', 'string', ['null' => false, 'limit' => 50])
            ->addColumn('name', 'string', ['null' => false, 'limit' => 60])
            ->addColumn('permissions', 'text', ['null' => false, 'default' => '[]'])
            ->addColumn('is_default', 'boolean', ['null' => false, 'default' => 0])
            ->addColumn('is_system', 'boolean', ['null' => false, 'default' => 0])
            ->addColumn('sort', 'integer', ['null' => false, 'default' => 100, 'signed' => false])
            ->addTimestamps()
            ->addIndex(['slug'], ['unique' => true, 'name' => 'roles_slug_uq'])
            ->addIndex(['is_default'], ['name' => 'roles_is_default_idx'])
            ->addIndex(['is_system'], ['name' => 'roles_is_system_idx'])
            ->create();
    }
}
