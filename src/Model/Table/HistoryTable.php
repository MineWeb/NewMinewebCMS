<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Utility\LangService;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Table;

class HistoryTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('histories');
        $this->belongsTo('User');
    }

    public function getLastFromUser(int|string $userId): ResultSetInterface
    {
        return $this->find('all', [
            'conditions' => ['user_id' => $userId],
            'limit' => 50,
            'order' => ['id' => 'DESC'],
        ])->all();
    }

    public function format(ResultSetInterface|array $data): array
    {
        if (empty($data)) {
            return [];
        }

        $return = [];

        foreach ($data as $value) {
            $categoryKey = 'HISTORY__CATEGORY_' . strtoupper((string)$value['category']);
            $category = (__($categoryKey) !== $categoryKey) ? __($categoryKey) : $value['category'];
            $string = '(' . $category . ') ';

            $string .= 'Le ' . LangService::date($value['created']);

            $actionKey = 'HISTORY__ACTION_' . strtoupper((string)$value['action']);
            $action = (__($actionKey) !== $actionKey) ? __($actionKey) : $value['action'];
            $string .= ' : ' . $action;

            switch ($value['action']) {
                case 'SEND_MONEY':
                    $other = explode('|', (string)$value['other']);
                    if (isset($other[0], $other[1])) {
                        $string .= ' pour un montant de ' . $other[1] . ' à ' . $this->User->getUsernameByID($other[0]);
                    }
                    break;

                case 'BUY_MONEY':
                    $other = explode('|', (string)$value['other']);
                    if (empty($other) || !isset($other[1])) {
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
                    $string .= ' l\'article "' . $value['other'] . '"';
                    break;

                default:
                    break;
            }

            $string .= ' par ' . $value['author'] . '.';

            $return[$value['id']] = $string;
        }

        return $return;
    }
}
