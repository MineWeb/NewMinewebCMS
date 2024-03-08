<?php
namespace App\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class ConfigurationTable extends Table
{
    public $dataConfig;

    public function initialize(array $config): void
    {
        $this->setTable("configurations");
    }

    public function getAll()
    {
        return $this->getData();
    }

    private function getData()
    {
        if (empty($this->dataConfig)) {
            $config = $this->find()->first();
            if ($config == null)
                return [];

            $this->dataConfig = $config;
        }
        return $this->dataConfig;
    }

    public function getMoneyName($plural = true)
    {
        return ($plural) ? $this->getData()['money_name_plural'] : $this->getData()['money_name_singular'];
    }

    public function getKey($key)
    {
        return (isset($this->getData()[$key])) ? $this->getData()[$key] : false;
    }

    public function setKey($key, $value)
    {
        $config = $this->get(1);
        $config->set([$key => $value]);
        return $this->save($config);
    }

    public function getFirstAdministrator()
    {
        return TableRegistry::getTableLocator()->get('User')->find('all', conditions: ['rank' => '4'])->first()['pseudo'];
    }

    public function getInstalledDate()
    {
        return TableRegistry::getTableLocator()->get('User')->find('all', conditions: ['rank' => '4'])->first()['created'];
    }
}
