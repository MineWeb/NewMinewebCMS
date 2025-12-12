<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class ServersTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('servers');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');

        $this->hasMany('ServerCmds', [
            'foreignKey' => 'server_id',
        ]);

        $this->hasMany('ApiConfigurations', [
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
            ->scalar('name')
            ->maxLength('name', 20)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('ip')
            ->maxLength('ip', 120)
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip');

        $validator
            ->integer('port')
            ->requirePresence('port', 'create')
            ->notEmptyString('port');

        $validator
            ->integer('type')
            ->notEmptyString('type');

        $validator
            ->scalar('data')
            ->maxLength('data', 120)
            ->requirePresence('data', 'create')
            ->notEmptyString('data');

        return $validator;
    }

    public function findSelectableServers(bool $rcon = true): array
    {
        $types = [['type' => 0]];
        if ($rcon) {
            $types[] = ['type' => 2];
        }
        $search_servers = $this->find()->where($types)->all();
        if (empty($search_servers)) {
            return [];
        }

        $servers = [];
        foreach ($search_servers as $server) {
            $servers[$server['id']] = $server['name'];
        }

        return $servers;
    }
}
