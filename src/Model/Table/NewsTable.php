<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class NewsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('news');
        $this->setPrimaryKey('id');
        $this->setDisplayField('title');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                    'updated' => 'always',
                ],
            ],
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
        ]);

        $this->hasMany('Comments', [
            'foreignKey' => 'news_id',
            'dependent' => true,
        ]);

        $this->hasMany('Likes', [
            'foreignKey' => 'news_id',
            'dependent' => true,
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('title')
            ->maxLength('title', 50)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content');

        $validator
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->dateTime('created')
            ->notEmptyDateTime('created');

        $validator
            ->dateTime('updated')
            ->notEmptyDateTime('updated');

        $validator
            ->scalar('img')
            ->requirePresence('img', 'create')
            ->notEmptyString('img');

        $validator
            ->scalar('slug')
            ->maxLength('slug', 150)
            ->requirePresence('slug', 'create')
            ->notEmptyString('slug');

        $validator
            ->boolean('published')
            ->notEmptyString('published');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    public function findWithRelations(Query $query, array $options): Query
    {
        return $query->contain(['Users', 'Comments', 'Likes']);
    }
}
