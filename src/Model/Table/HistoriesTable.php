<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Utility\LangService;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Query;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class HistoriesTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('histories');
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
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('action')
            ->requirePresence('action', 'create')
            ->notEmptyString('action');

        $validator
            ->scalar('category')
            ->maxLength('category', 50)
            ->requirePresence('category', 'create')
            ->notEmptyString('category');

        $validator
            ->dateTime('created')
            ->notEmptyDateTime('created');

        $validator
            ->integer('user_id')
            ->requirePresence('user_id', 'create')
            ->notEmptyString('user_id');

        $validator
            ->scalar('other')
            ->allowEmptyString('other');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));

        return $rules;
    }

    public function findWithUser(Query $query, array $options): Query
    {
        return $query->contain(['Users']);
    }

    public function getLastFromUser(int|string $userId): ResultSetInterface
    {
        return $this
            ->find('withUser')
            ->where(['Histories.user_id' => $userId])
            ->orderByDesc('Histories.id')
            ->limit(50)
            ->all();
    }

    public function format(ResultSetInterface|array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $return = [];

        foreach ($data as $value) {
            $categoryRaw = (string)($value['category'] ?? '');
            $actionRaw = (string)($value['action'] ?? '');
            $otherRaw = (string)($value['other'] ?? '');
            $createdRaw = $value['created'] ?? null;

            $categoryKey = 'HISTORY__CATEGORY_' . strtoupper($categoryRaw);
            $category = __($categoryKey) !== $categoryKey ? __($categoryKey) : $categoryRaw;

            $actionKey = 'HISTORY__ACTION_' . strtoupper($actionRaw);
            $action = __($actionKey) !== $actionKey ? __($actionKey) : $actionRaw;

            $string = '(' . $category . ') ';
            $string .= 'Le ' . LangService::date($createdRaw);
            $string .= ' : ' . $action;

            switch ($actionRaw) {
                case 'SEND_MONEY':
                    $other = explode('|', $otherRaw);
                    if (isset($other[0], $other[1])) {
                        $targetPseudo = $this->getAssociation('Users')->getTarget()->getUsernameByID($other[0]);
                        $string .= ' pour un montant de ' . $other[1] . ' à ' . $targetPseudo;
                    }
                    break;

                case 'BUY_MONEY':
                    $other = explode('|', $otherRaw);
                    if (!isset($other[1])) {
                        break;
                    }
                    $string .= ' pour un montant de ' . $other[1];
                    if (isset($other[3])) {
                        $string .= ' (Money : ' . $other[3] . ')';
                    }
                    if (isset($other[0])) {
                        $string .= ' avec ' . $other[0];
                    }
                    break;

                case 'BUY_ITEM':
                    if ($otherRaw !== '') {
                        $string .= ' l\'article "' . $otherRaw . '"';
                    }
                    break;

                default:
                    break;
            }

            $author = (string)($value['author'] ?? 'N/A');
            $string .= ' par ' . $author . '.';

            $return[(int)$value['id']] = $string;
        }

        return $return;
    }
}
