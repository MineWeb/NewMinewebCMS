<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;

class MaintenanceController extends AppController {
    function index()
    {
        if (!$this->isConnected and !$this->Permissions->can('MANAGE_MAINTENANCE'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('MAINTENANCE__TITLE'));

        $pagesInMaintenance = $this->Maintenance->find()->all();
        $this->set("pages", $pagesInMaintenance);
    }

    function add()
    {
        if (!$this->isConnected and !$this->Permissions->can('MANAGE_MAINTENANCE'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('MAINTENANCE__TITLE'));

        if ($this->request->is("post")) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');

            if ($this->getRequest()->getData('reason') == null)
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('MAINTENANCE__ADD_REASON_EMPTY')]));

            $maintenance = $this->Maintenance->newEntity($this->getRequest()->getData());
            $this->Maintenance->save($maintenance);

            return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('MAINTENANCE__ADD_SUCCESS')]));
        }
    }

    function edit($id = false)
    {
        if (!$this->isConnected and !$this->Permissions->can('MANAGE_MAINTENANCE') | !$id)
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('MAINTENANCE__TITLE'));

        $page = $this->Maintenance->find("all", ["conditions" => ["id" => $id]])->first();
        $this->set("page", $page);

        if ($this->request->is("post")) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');

            if ($this->getRequest()->getData('reason') == null)
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('MAINTENANCE__ADD_REASON_EMPTY')]));

            $maintenance = $this->Maintenance->get($id);
            $maintenance->set($this->getRequest()->getData());
            $this->Maintenance->save($maintenance);

            return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('MAINTENANCE__EDIT_SUCCESS')]));
        }
    }

    function disable($id = false)
    {
        $this->autoRender = false;
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_MAINTENANCE') || !$id)
            throw new ForbiddenException();

        $maintenance = $this->Maintenance->get($id);
        $maintenance->set(["active" => "0"]);
        $this->Maintenance->save($maintenance);

        $this->Flash->success($this->Lang->get('MAINTENANCE__DISABLED_PAGE', [
            '{PAGE}' => $maintenance['url'],
        ]));
        $this->redirect(['controller' => 'maintenance', 'action' => 'index', 'admin' => true]);
    }

    function enable($id = false)
    {
        $this->autoRender = false;
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_MAINTENANCE') || !$id)
            throw new ForbiddenException();

        $maintenance = $this->Maintenance->get($id);
        $maintenance->set(["active" => "1"]);
        $this->Maintenance->save($maintenance);

        $this->Flash->success($this->Lang->get('MAINTENANCE__ENABLED_PAGE', [
            '{PAGE}' => $maintenance['url'],
        ]));
        $this->redirect(['controller' => 'maintenance', 'action' => 'index', 'admin' => true]);
    }

    function delete($id = false)
    {
        $this->autoRender = false;
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_MAINTENANCE') || !$id)
            throw new ForbiddenException();

        $pageUrl = $this->Maintenance->find('all', ["conditions" => ['id' => $id]])->first()["url"];
        $this->Maintenance->delete($this->Maintenance->get($id));

        $this->Flash->success($this->Lang->get('MAINTENANCE__DELETED_PAGE', [
            '{PAGE}' => $pageUrl,
        ]));
        $this->redirect(['controller' => 'maintenance', 'action' => 'index', 'admin' => true]);
    }
}
