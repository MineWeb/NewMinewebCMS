<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;

class HistoryController extends AppController
{
    public function index()
    {
        if (!$this->Permissions->can('VIEW_WEBSITE_HISTORY'))
            throw new ForbiddenException();
        $this->set('title_for_layout', $this->Lang->get('HISTORY__VIEW_GLOBAL'));
    }

    public function getAll()
    {
        if (!$this->Permissions->can('VIEW_WEBSITE_HISTORY'))
            throw new ForbiddenException();
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $this->History = TableRegistry::getTableLocator()->get('History');

        $this->DataTable = $this->loadComponent('DataTable');
        $this->DataTable->setTable($this->History);
        $this->paginate = [
            'contain' => ['User'],
            'fields' => ['History.id', 'User.pseudo', 'History.action', 'History.user_id', 'History.category', 'History.created'],
            'order' => 'History.id DESC',
            'recursive' => 1
        ];
        $this->DataTable->mDataProp = true;
        $response = $this->DataTable->getResponse();

        $data = [];
        foreach ($response["aaData"] as $history) {
            $data[] = [
                "History" => [
                    "action" => $this->Lang->history($history["action"]),
                    "category" => $history["category"],
                    "created" => $this->Lang->date($history["created"])
                ],
                "User" => $history["user"]
            ];
        }

        $response["aaData"] = $data;
        return $this->response->withStringBody(json_encode($response));
    }
}
