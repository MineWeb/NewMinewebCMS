<?php
namespace App\Model\Table;

use Cake\Database\Schema\TableSchema;
use Cake\ORM\Table;

class MaintenanceTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable("maintenances");
    }

    function checkMaintenance($url, $utilComponent)
    {
        $use_sqlite = $utilComponent->useSqlite();

        $condition = ["'" . $url . "' LIKE CONCAT(Maintenance.url, '%')", "active" => 1];

        if ($use_sqlite)
            $condition = ["'" . $url . "' LIKE 'Maintenance.url' || '%')", "active" => 1];

        $check = $this->find("all", conditions: $condition)->first();
        if (isset($check["Maintenance"]))
            $check = $check["Maintenance"];
        if ($check && (($check["url"] == $url) || ($check["sub_url"] && $url != "/")))
            return $check;
        $is_full = $this->isFullMaintenance();
        if ($is_full)
            return $is_full;
        return false;
    }

    function isFullMaintenance()
    {
        $result = $this->find("all", conditions: ["url" => "", "active" => 1])->first();
        if (isset($result["Maintenance"]))
            $result = $result["Maintenance"];
        return $result;
    }
}
