<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class VisitsTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('visits');
        $this->setPrimaryKey('id');

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
            'joinType' => 'LEFT',
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
            ->scalar('ip')
            ->maxLength('ip', 50)
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip');

        $validator
            ->scalar('referer')
            ->allowEmptyString('referer');

        $validator
            ->scalar('lang')
            ->maxLength('lang', 4)
            ->allowEmptyString('lang');

        $validator
            ->scalar('navigator')
            ->allowEmptyString('navigator');

        $validator
            ->scalar('page')
            ->allowEmptyString('page');

        return $validator;
    }

    public function getVisits(int|false $limit = false, string $order = 'DESC'): array
    {
        $query = $this
            ->find()
            ->contain(['Users'])
            ->orderBy(['id' => $order]);

        if ($limit !== false) {
            $query = $query->limit($limit);
        }

        $data = $query->toArray();
        $data['count'] = count($data);

        return $data;
    }

    public function getVisitsCount(int|false $limit = false, string $order = 'DESC'): int
    {
        $query = $this
            ->find()
            ->orderBy(['id' => $order]);

        if ($limit !== false) {
            $query = $query->limit($limit);
        }

        return $query->count();
    }

    public function getVisitRange(int $limit): array
    {
        $data = [];

        $search = $this
            ->find()
            ->select(['created_at' => 'DATE(created_at)', 'count' => 'COUNT(*)'])
            ->groupBy('DATE(created_at)')
            ->orderBy(['id' => 'DESC'])
            ->limit($limit)
            ->all();

        foreach ($search as $value) {
            $data[$value['created_at']] = (int)$value['count'];
        }

        return $data;
    }

    public function getVisitsByDay(string $day): array
    {
        $data = $this
            ->find(conditions: ['created_at LIKE' => $day . '%'])
            ->toArray();

        $data['count'] = count($data);

        return $data;
    }

    public function getGrouped(string $groupBy, int|false $limit = false, string $order = 'DESC'): array
    {
        $data = [];

        $search = $this
            ->find()
            ->select([$groupBy, 'count' => 'COUNT(*)'])
            ->groupBy($groupBy)
            ->orderBy(['COUNT(*)' => $order])
            ->limit($limit)
            ->all();

        foreach ($search as $value) {
            $count = (int)$value['count'];
            if ($count >= 5) {
                $data[$value[$groupBy]] = $count;
            }
        }

        return $data;
    }
}
