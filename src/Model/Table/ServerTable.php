<?php
namespace App\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;

class ServerTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable("servers");
    }

    public function findSelectableServers($rcon = true)
    {
        $types = [['type' => 0]];
        if ($rcon)
            $types[] = ['type' => 2];
        $search_servers = $this->find()->where($types)->all();
        if (empty($search_servers))
            return [];

        $servers = [];
        foreach ($search_servers as $server)
            $servers[$server['id']] = $server['name'];
        return $servers;
    }
}
