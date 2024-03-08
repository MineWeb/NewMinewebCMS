<?php
namespace App\Controller;

use Cake\ORM\TableRegistry;

class MaintenanceController extends AppController
{
    function index($url = "")
    {
        $this->set('title_for_layout', $this->Lang->get('MAINTENANCE__TITLE'));
        $this->Mainteance = TableRegistry::getTableLocator()->get('Maintenance');
        $check = $this->Maintenance->checkMaintenance("/" . $url, $this->Util);
        if ($this->Permissions->can("BYPASS_MAINTENANCE") || !$check)
            $this->redirect("/");
        $msg = $check["reason"];
        $this->set(compact('msg'));
    }
}
