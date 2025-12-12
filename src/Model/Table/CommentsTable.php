<?php
declare(strict_types=1);

namespace App\Model\Table;

use ArrayObject;
use Cake\Cache\Cache;
use Cake\Datasource\EntityInterface;
use Cake\Event\EventInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class CommentsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('comments');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
        ]);

        $this->belongsTo('News', [
            'foreignKey' => 'news_id',
            'joinType' => 'INNER',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content');

        $validator
            ->dateTime('created')
            ->notEmptyDateTime('created');

        $validator
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->integer('news_id')
            ->requirePresence('news_id', 'create')
            ->notEmptyString('news_id');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));
        $rules->add($rules->existsIn(['news_id'], 'News'));

        return $rules;
    }

    public function findWithRelations(Query $query, array $options): Query
    {
        return $query->contain(['Users', 'News']);
    }

    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        Cache::delete('news', 'data');
    }

    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options): void
    {
        Cache::delete('news', 'data');
    }
}
