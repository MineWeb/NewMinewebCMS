<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SessionsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('sessions');
        $this->setPrimaryKey('id');
        $this->setDisplayField('id');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('id')
            ->maxLength('id', 40)
            ->requirePresence('id', 'create')
            ->notEmptyString('id');

        $validator
            ->allowEmptyString('data');

        $validator
            ->integer('expires')
            ->allowEmptyString('expires');

        return $validator;
    }
}
