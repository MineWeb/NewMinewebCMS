<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class ApiConfigurationsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('api_configurations');
        $this->setPrimaryKey('id');

        $this->belongsTo('Servers', [
            'foreignKey' => 'skin_restorer_server_id',
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
            ->integer('skins')
            ->notEmptyString('skins');

        $validator
            ->scalar('skin_filename')
            ->maxLength('skin_filename', 150)
            ->requirePresence('skin_filename', 'create')
            ->notEmptyString('skin_filename');

        $validator
            ->integer('skin_free')
            ->notEmptyString('skin_free');

        $validator
            ->integer('skin_width')
            ->allowEmptyString('skin_width');

        $validator
            ->integer('skin_height')
            ->allowEmptyString('skin_height');

        $validator
            ->integer('capes')
            ->notEmptyString('capes');

        $validator
            ->scalar('cape_filename')
            ->maxLength('cape_filename', 150)
            ->requirePresence('cape_filename', 'create')
            ->notEmptyString('cape_filename');

        $validator
            ->integer('cape_free')
            ->notEmptyString('cape_free');

        $validator
            ->integer('cape_width')
            ->allowEmptyString('cape_width');

        $validator
            ->integer('cape_height')
            ->allowEmptyString('cape_height');

        $validator
            ->integer('get_premium_skins')
            ->notEmptyString('get_premium_skins');

        $validator
            ->integer('use_skin_restorer')
            ->notEmptyString('use_skin_restorer');

        $validator
            ->integer('skin_restorer_server_id')
            ->allowEmptyString('skin_restorer_server_id');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['skin_restorer_server_id'], 'Servers'));

        return $rules;
    }
}
