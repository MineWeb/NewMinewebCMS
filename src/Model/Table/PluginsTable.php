<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class PluginsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('plugins');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');

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
            ->scalar('name')
            ->maxLength('name', 50)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('author')
            ->maxLength('author', 50)
            ->requirePresence('author', 'create')
            ->notEmptyString('author');

        $validator
            ->scalar('version')
            ->maxLength('version', 20)
            ->requirePresence('version', 'create')
            ->notEmptyString('version');

        $validator
            ->integer('state')
            ->notEmptyString('state');

        return $validator;
    }
}
