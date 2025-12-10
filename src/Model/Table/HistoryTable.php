<?php
namespace App\Model\Table;

use App\Utility\LangService;
use Cake\Datasource\ResultSetInterface;
use Cake\ORM\Table;

class HistoryTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('histories');

        $this->belongsTo("User");
    }
    public function getLastFromUser($user_id): ResultSetInterface
    {
        return $this->find('all', conditions: ['user_id' => $user_id], limit: '50', order: 'id DESC')->all();
    }

    public function format($data)
    {

        if (empty($data)) {
            return [];
        }

        $return = [];

        foreach ($data as $key => $value) {

            $category = 'HISTORY__CATEGORY_' . strtoupper($value['category']);
            $category = (__($category) != $category) ? __($category) : $value['category'];
            $string = '(' . $category . ') ';

            $string .= 'Le ' . LangService::date($value['created']);

            $action = 'HISTORY__ACTION_' . strtoupper($value['action']);
            $action = (__($action) != $action) ? __($action) : $value['action'];
            $string .= ' : ' . $action;

            // Autres
            switch ($value['action']) {
                case 'SEND_MONEY':
                    $other = explode('|', $value['other']);
                    $string .= ' pour un montant de ' . $other[1] . ' à ' . $this->User->getUsernameByID($other[0]);
                    break;
                case 'BUY_MONEY':
                    $other = explode('|', $value['other']);
                    if (empty($other) || !isset($other[1])) break;
                    $string .= ' pour un montant de ' . $other[1];
                    if (isset($other[3])) {
                        $string .= ' (Money : ' . $other[3] . ')';
                    }
                    $string .= ' avec ' . $other[0];
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
