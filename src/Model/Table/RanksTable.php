<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class RanksTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('ranks');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('rank_id')
            ->requirePresence('rank_id', 'create')
            ->notEmptyString('rank_id');

        $validator
            ->scalar('name')
            ->maxLength('name', 20)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        return $validator;
    }
}
