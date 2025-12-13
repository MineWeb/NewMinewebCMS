<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class RolesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('roles');
        $this->setPrimaryKey('id');
        $this->setDisplayField('display_name');

        $this->hasMany('Users', [
            'foreignKey' => 'role_id',
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
            ->scalar('slug')
            ->maxLength('slug', 50)
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug')
            ->regex('slug', '/^[a-z0-9_]+$/');

        $validator
            ->scalar('name')
            ->maxLength('name', 60)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('permissions')
            ->requirePresence('permissions', 'create')
            ->notEmptyString('permissions');

        $validator
            ->boolean('is_default')
            ->requirePresence('is_default', 'create');

        $validator
            ->boolean('is_system')
            ->requirePresence('is_system', 'create');

        $validator
            ->integer('sort')
            ->requirePresence('sort', 'create');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['slug']));

        return $rules;
    }

    public function beforeSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        if ($entity->get('is_default')) {
            $id = (int)($entity->get('id') ?? 0);
            if ($id > 0) {
                $this->updateAll(['is_default' => 0], ['id !=' => $id]);
            }
        }
    }
}
