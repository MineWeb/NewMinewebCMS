<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class LostpasswordsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('lostpasswords');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                    'updated_at' => 'always',
                ],
            ],
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('email')
            ->maxLength('email', 50)
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('key')
            ->maxLength('key', 10)
            ->requirePresence('key', 'create')
            ->notEmptyString('key');


        return $validator;
    }
}
