<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ServerCmdsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('server_cmds');
        $this->setPrimaryKey('id');

        $this->belongsTo('Servers', [
            'foreignKey' => 'server_id',
        ]);
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
            ->maxLength('name', 255)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->integer('server_id')
            ->requirePresence('server_id', 'create')
            ->notEmptyString('server_id');

        $validator
            ->scalar('cmd')
            ->maxLength('cmd', 255)
            ->requirePresence('cmd', 'create')
            ->notEmptyString('cmd');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['server_id'], 'Servers'));

        return $rules;
    }
}
