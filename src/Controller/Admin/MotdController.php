<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

class MotdController extends AppController
{
    public function index()
    {
        if (!$this->Permissions->can('MANAGE_MOTD'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('MOTD__TITLE'));

        $this->ServerComponent = $this->loadComponent('Server');
        $this->Server = TableRegistry::getTableLocator()->get('Server');

        $get_all_servers = $this->Server->findSelectableServers(false);
        $get_servers = [];
        $calls = ['GET_MOTD' => []];

        foreach ($get_all_servers as $key => $value) {
            if (!$this->ServerComponent->online($key))
                continue;
            $call = array_values($this->ServerComponent->call($calls, $key));
            $get_servers[$key]['name'] = $value;
            $motd = explode("\n", $call[0]);
            $get_servers[$key]['motd_line1'] = $motd[0];
            if (count($motd) > 1)
                $get_servers[$key]['motd_line2'] = $motd[1];
        }
        $this->set(compact('get_servers'));
    }


    public function edit($server_id = false)
    {
        if (!$this->Permissions->can('MANAGE_MOTD'))
            throw new ForbiddenException();
        if (!$server_id)
            throw new NotFoundException();

        $this->set('title_for_layout', $this->Lang->get('MOTD__EDIT_TITLE'));

        $this->ServerComponent = $this->loadComponent('Server');
        $this->Server = TableRegistry::getTableLocator()->get('Server');

        $calls = ['GET_MOTD' => []];
        $call = array_values($this->ServerComponent->call($calls, $server_id));

        $get_all_servers = $this->Server->findSelectableServers(false);
        $get['id'] = $server_id;
        $get['name'] = $get_all_servers[$server_id];

        $motd = explode("\n", $call[0]);
        $get['motd_line1'] = $motd[0];
        if (count($motd) > 1)
            $get['motd_line2'] = $motd[1];

        $this->set(compact('get'));
    }

    public function editAjax($server_id)
    {
        if (!$this->Permissions->can('MANAGE_MOTD'))
            throw new ForbiddenException();
        if (!$this->request->is('ajax'))
            throw new NotFoundException();

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $data = "";
        if (!empty($this->request->getData('motd_line1')) || !empty($this->request->getData('motd_line2')))
            $data = implode("\n", [$this->request->getData('motd_line1'), $this->request->getData('motd_line2')]);

        $this->Server->call(['SET_MOTD' => $data], $server_id);
        $this->History->set('EDIT_MOTD', 'motd');

        $this->Flash->success($this->Lang->get('MOTD__EDIT_SUCCESS'));
        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('MOTD__EDIT_SUCCESS')]));
    }

    public function reset($server_id = false)
    {
        $this->disableAutoRender();
        if (!$this->Permissions->can('MANAGE_MOTD'))
            throw new ForbiddenException();
        if (!$server_id)
            throw new NotFoundException();

        $this->Server->call(['SET_MOTD' => ""], $server_id);

        $this->Flash->success($this->Lang->get('MOTD__RESET_SUCCESS'));
        $this->redirect('/admin/motd');
    }

}
