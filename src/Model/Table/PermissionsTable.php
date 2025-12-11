<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PermissionsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('permissions');
        $this->setPrimaryKey('id');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('rank')
            ->requirePresence('rank', 'create')
            ->notEmptyString('rank');

        $validator
            ->scalar('permissions')
            ->requirePresence('permissions', 'create')
            ->notEmptyString('permissions');

        return $validator;
    }
}
