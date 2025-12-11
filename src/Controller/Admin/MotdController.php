<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class MotdController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Permissions->can('MANAGE_MOTD')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('MOTD__TITLE'));

        $serverTable = $this->fetchTable('Server');
        $this->ServerComponent = $this->loadComponent('Server');

        $servers = $serverTable->findSelectableServers(false);
        $result = [];

        foreach ($servers as $id => $name) {
            if (!$this->ServerComponent->online($id)) {
                continue;
            }

            $call = $this->ServerComponent->call(['GET_MOTD' => []], $id);
            $motd = explode("\n", array_values($call)[0] ?? '');

            $result[$id] = [
                'name' => $name,
                'motd_line1' => $motd[0] ?? '',
                'motd_line2' => $motd[1] ?? '',
            ];
        }

        $this->set('get_servers', $result);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Motd')
            ->setTemplate('index');

        return null;
    }

    public function edit(int|string|null $server_id = null): ?Response
    {
        if (!$this->Permissions->can('MANAGE_MOTD')) {
            throw new ForbiddenException();
        }

        if (!$server_id) {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('MOTD__EDIT_TITLE'));

        $serverTable = $this->fetchTable('Server');
        $this->ServerComponent = $this->loadComponent('Server');

        $servers = $serverTable->findSelectableServers(false);

        if (!isset($servers[$server_id])) {
            throw new NotFoundException();
        }

        $call = $this->ServerComponent->call(['GET_MOTD' => []], $server_id);
        $motd = explode("\n", array_values($call)[0] ?? '');

        $data = [
            'id' => $server_id,
            'name' => $servers[$server_id],
            'motd_line1' => $motd[0] ?? '',
            'motd_line2' => $motd[1] ?? '',
        ];

        $this->set('get', $data);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Motd')
            ->setTemplate('edit');

        return null;
    }

    public function editAjax(int|string $server_id): Response
    {
        if (!$this->Permissions->can('MANAGE_MOTD')) {
            throw new ForbiddenException();
        }

        if (!$this->getRequest()->is('ajax')) {
            throw new NotFoundException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $line1 = $this->getRequest()->getData('motd_line1') ?? '';
        $line2 = $this->getRequest()->getData('motd_line2') ?? '';

        $motd = trim($line1 . "\n" . $line2);

        $this->Server->call(['SET_MOTD' => $motd], $server_id);
        $this->History->set('EDIT_MOTD', 'motd');

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('MOTD__EDIT_SUCCESS'),
        ]));
    }

    public function reset(int|string|null $server_id = null): Response
    {
        $this->disableAutoRender();

        if (!$this->Permissions->can('MANAGE_MOTD')) {
            throw new ForbiddenException();
        }

        if (!$server_id) {
            throw new NotFoundException();
        }

        $this->Server->call(['SET_MOTD' => ''], $server_id);
        $this->History->set('RESET_MOTD', 'motd');
        $this->Flash->success(__('MOTD__RESET_SUCCESS'));

        return $this->redirect('/admin/motd');
    }
}
