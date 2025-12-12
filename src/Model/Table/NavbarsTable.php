<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class NavbarsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('navbars');
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
            ->integer('order_by')
            ->requirePresence('order_by', 'create')
            ->notEmptyString('order_by');

        $validator
            ->scalar('name')
            ->maxLength('name', 50)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('icon')
            ->maxLength('icon', 50)
            ->allowEmptyString('icon');

        $validator
            ->integer('type')
            ->notEmptyString('type');

        $validator
            ->scalar('url')
            ->maxLength('url', 250)
            ->requirePresence('url', 'create')
            ->notEmptyString('url');

        $validator
            ->scalar('submenu')
            ->allowEmptyString('submenu');

        $validator
            ->integer('open_new_tab')
            ->allowEmptyString('open_new_tab');

        return $validator;
    }
}
