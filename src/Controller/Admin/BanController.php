<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

class BanController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_BAN')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('BAN__HOME'));

        $banTable = $this->fetchTable('Ban');
        $banned_users = $banTable->find()->all();

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Ban')
            ->setTemplate('index');

        $this->set(compact('banned_users'));

        return null;
    }

    public function add(): ?Response
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_BAN')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('BAN__HOME'));
        $this->set('type', $this->Configuration->getKey('member_page_type'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Ban')
            ->setTemplate('add');

        $request = $this->getRequest();

        if ($request->is('post')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');

            $reason = $request->getData('reason');
            if (empty($reason)) {
                return $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('ERROR__FILL_ALL_FIELDS'),
                ]));
            }

            $banTable = $this->fetchTable('Ban');
            $userTable = $this->User;

            foreach ($request->getData() as $key => $value) {
                if ($value !== 'on' || $key === 'name' || strpos($key, '-ip') !== false) {
                    continue;
                }

                $ban = $banTable->newEntity([
                    'user_id' => $key,
                    'reason' => $reason,
                ]);

                $ipFieldName = $key . '-ip';
                if ($request->getData($ipFieldName) === 'on') {
                    $user = $userTable
                        ->find('all', ['conditions' => ['id' => $key]])
                        ->first();

                    if ($user !== null && array_key_exists('ip', $user)) {
                        $ban->set('ip', $user['ip']);
                    }
                }

                $banTable->save($ban);
            }

            return $this->response->withStringBody(json_encode([
                'statut' => true,
                'msg' => __('BAN__SUCCESS'),
            ]));
        }

        return null;
    }

    public function unban(int|string $id = 0): Response
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_BAN')) {
            throw new ForbiddenException();
        }

        $banTable = $this->fetchTable('Ban');
        $ban = $banTable->get($id);
        $banTable->delete($ban);

        $this->Flash->success(__('BAN__UNBAN_SUCCESS'));

        return $this->redirect(['_name' => 'admin_ban_index']);
    }

    public function getUsersNotBan(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->isConnected && $this->Permissions->can('MANAGE_BAN'))) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        if (!$this->getRequest()->is('ajax')) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        $available_ranks = [
            0 => ['label' => 'success', 'name' => __('USER__RANK_MEMBER')],
            2 => ['label' => 'warning', 'name' => __('USER__RANK_MODERATOR')],
            3 => ['label' => 'danger', 'name' => __('USER__RANK_ADMINISTRATOR')],
            4 => ['label' => 'danger', 'name' => __('USER__RANK_ADMINISTRATOR')],
        ];

        $rankTable = $this->fetchTable('Rank');
        $custom_ranks = $rankTable->find()->all();

        foreach ($custom_ranks as $value) {
            $available_ranks[$value['rank_id']] = [
                'label' => 'info',
                'name' => $value['name'],
            ];
        }

        $this->DataTable = $this->loadComponent('DataTable');
        $this->DataTable->setTable($this->User);
        $this->paginate = [
            'fields' => ['User.id', 'User.pseudo', 'User.rank', 'User.ip'],
        ];
        $this->DataTable->mDataProp = true;
        $response = $this->DataTable->getResponse();

        $banTable = $this->fetchTable('Ban');
        $users = $response['aaData'] ?? [];
        $data = [];

        foreach ($users as $value) {
            $checkIsBan = $banTable
                ->find('all', ['conditions' => ['user_id' => $value['id']]])
                ->first();

            if ($checkIsBan !== null) {
                continue;
            }

            if ($this->Permissions->have($value['rank'], 'BYPASS_BAN')) {
                continue;
            }

            $username = $value['pseudo'];
            $rankConfig = $available_ranks[$value['rank']] ?? $available_ranks[0];

            $rank = '<span class="label label-' . $rankConfig['label'] . '">' . $rankConfig['name'] . '</span>';
            $checkbox = "<input type='checkbox' name='" . $value['id'] . "'>";
            $banIpCheckbox = "<input type='checkbox' name='" . $value['id'] . "-ip'>";

            $data[] = [
                'User' => [
                    'pseudo' => $username,
                    'ban' => $checkbox,
                    'banIp' => $banIpCheckbox,
                    'rank' => $rank,
                    'ip' => $value['ip'],
                ],
            ];
        }

        $response['aaData'] = $data;

        return $this->response->withStringBody(json_encode($response));
    }

    public function liveSearch(?string $query = null): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->isConnected && $this->Permissions->can('MANAGE_BAN'))) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        if (!$query) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        $usersTable = $this->User;
        $banTable = $this->fetchTable('Ban');

        $result = $usersTable
            ->find('all', ['conditions' => ['pseudo LIKE' => $query . '%']])
            ->all();

        $users = [];
        foreach ($result as $value) {
            $checkIsBan = $banTable
                ->find('all', ['conditions' => ['user_id' => $value['id']]])
                ->first();

            if ($checkIsBan !== null) {
                continue;
            }

            if ($this->Permissions->have($value['rank'], 'BYPASS_BAN')) {
                continue;
            }

            $users[] = [
                'pseudo' => $value['pseudo'],
                'id' => $value['id'],
            ];
        }

        if (empty($users)) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'data' => $users,
        ]));
    }
}
