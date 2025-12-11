<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Maintenance;
use Cake\ORM\Table;
use Cake\Validation\Validator;

class MaintenancesTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('maintenances');
        $this->setPrimaryKey('id');
        $this->setDisplayField('url');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('sub_url')
            ->notEmptyString('sub_url');

        $validator
            ->scalar('url')
            ->requirePresence('url', 'create')
            ->notEmptyString('url');

        $validator
            ->scalar('reason')
            ->requirePresence('reason', 'create')
            ->notEmptyString('reason');

        $validator
            ->integer('active')
            ->notEmptyString('active');

        return $validator;
    }

    public function checkMaintenance(string $url): Maintenance|false
    {
        $check = null;

        foreach ($this->find()->where(['active' => 1]) as $row) {
            if (!$row instanceof Maintenance) {
                continue;
            }

            $rowUrl = (string)$row->get('url');
            $subUrl = (bool)$row->get('sub_url');

            if (!str_starts_with($url, $rowUrl)) {
                continue;
            }

            if ($url === $rowUrl || ($subUrl && $url !== '/')) {
                $check = $row;
                break;
            }
        }

        if ($check instanceof Maintenance) {
            return $check;
        }

        $isFull = $this->isFullMaintenance();
        if ($isFull instanceof Maintenance) {
            return $isFull;
        }

        return false;
    }

    public function isFullMaintenance(): ?Maintenance
    {
        return $this->find()
            ->where(['url' => '', 'active' => 1])
            ->first();
    }
}
