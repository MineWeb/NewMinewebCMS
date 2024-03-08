<?php
namespace App\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\Log\Log;
use Cake\ORM\Table;

class VisitTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable("visits");
    }

    function getVisits($limit = false, $order = 'DESC')
    {
        $query = $this->find('all')
            ->orderBy(['id' => $order]);

        if ($limit)
            $query = $query->limit($limit);

        $data = $query->toArray();
        $data['count'] = count($data);
        return $data;
    }

    function getVisitsCount($limit = false, $order = 'DESC')
    {
        $query = $this->find('all')
            ->orderBy(['id' => $order]);

        if ($limit)
            $query = $query->limit($limit);

        return $query->count();
    }

    function getVisitRange($limit)
    {
        $data = [];

        $search = $this->find()
            ->select(['created' => 'DATE(created)', 'count' => 'COUNT(*)'])
            ->group('DATE(created)')
            ->orderBy('id DESC')
            ->limit($limit)
            ->all();

        foreach ($search as $value) {
            $data[$value['created']] = $value['count'];
        }

        return $data;
    }

    function getVisitsByDay($day)
    { // $day au format : date('Y-m-d')
        $data = $this->find('all', conditions: ['created LIKE' => $day . '%'])->toArray();
        $data['count'] = count($data);
        return $data;
    }

    function getGrouped($groupBy, $limit = false, $order = 'DESC')
    {
        $data = [];

        $search = $this->find()
            ->select([$groupBy, 'count' => 'COUNT(*)'])
            ->group($groupBy)
            ->orderBy('COUNT(*) ' . $order)
            ->limit($limit)
            ->all();

        foreach ($search as $value) {
            if ($value['count'] >= 5) {
                $data[$value[$groupBy]] = $value['count'];
            }

        }

        return $data;
    }
}
