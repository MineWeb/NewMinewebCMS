<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

/**
 * @property \App\Controller\Component\AuthComponent $Auth
 * @property \App\Controller\Component\DataTableComponent $DataTable
 * @property \App\Model\Table\BansTable $Bans
 * @property \App\Model\Table\UsersTable $Users
 * @property \App\Model\Table\RanksTable $Ranks
 */
class BanController extends AppController
{
    private PermissionService $permissions;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('DataTable');

        $this->Bans = $this->fetchTable('Bans');
        $this->Users = $this->fetchTable('Users');
        $this->Ranks = $this->fetchTable('Ranks');

        $this->permissions = new PermissionService();
    }

    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_BAN')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('BAN__HOME'));

        $banned_users = $this->Bans->find()->all();

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Ban')
            ->setTemplate('index');

        $this->set(compact('banned_users'));

        return null;
    }

    public function add(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_BAN')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('BAN__HOME'));
        $this->set('type', (string)$this->config->get('member_page_type'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Ban')
            ->setTemplate('add');

        $request = $this->getRequest();

        if ($request->is('post')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');

            $reason = (string)$request->getData('reason', '');
            if ($reason === '') {
                return $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('ERROR__FILL_ALL_FIELDS'),
                ]));
            }

            foreach ((array)$request->getData() as $key => $value) {
                if ($value !== 'on' || $key === 'name' || str_contains((string)$key, '-ip')) {
                    continue;
                }

                $userId = (int)$key;
                if ($userId <= 0) {
                    continue;
                }

                $ban = $this->Bans->newEntity([
                    'user_id' => $userId,
                    'reason' => $reason,
                ]);

                $ipFieldName = $userId . '-ip';
                if ($request->getData($ipFieldName) === 'on') {
                    $user = $this->Users->find()
                        ->select(['id', 'ip'])
                        ->where(['id' => $userId])
                        ->first();

                    if ($user !== null && isset($user->ip) && is_string($user->ip)) {
                        $ban->ip = $user->ip;
                    }
                }

                $this->Bans->save($ban);
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
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_BAN')) {
            throw new ForbiddenException();
        }

        $ban = $this->Bans->get((int)$id);
        $this->Bans->delete($ban);

        $this->Flash->success(__('BAN__UNBAN_SUCCESS'));

        return $this->redirect(['_name' => 'admin_ban_index']);
    }

    public function getUsersNotBan(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_BAN')) {
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

        foreach ($this->Ranks->find()->all() as $rank) {
            $rid = (int)($rank->rank_id ?? 0);
            if ($rid <= 0) {
                continue;
            }

            $available_ranks[$rid] = [
                'label' => 'info',
                'name' => (string)($rank->name ?? ''),
            ];
        }

        $this->DataTable->setTable($this->Users);
        $this->paginate = [
            'fields' => ['Users.id', 'Users.username', 'Users.rank', 'Users.ip'],
        ];
        $this->DataTable->mDataProp = true;

        $response = $this->DataTable->getResponse();
        $users = $response['aaData'] ?? [];

        $data = [];

        foreach ($users as $value) {
            $userId = (int)($value['id'] ?? 0);
            $rankId = (int)($value['rank'] ?? 0);

            if ($userId <= 0) {
                continue;
            }

            if ($this->Bans->exists(['user_id' => $userId])) {
                continue;
            }

            if ($this->permissions->have($rankId, 'BYPASS_BAN')) {
                continue;
            }

            $rankConfig = $available_ranks[$rankId] ?? $available_ranks[0];

            $data[] = [
                'Users' => [
                    'username' => (string)($value['username'] ?? ''),
                    'ban' => "<input type='checkbox' name='{$userId}'>",
                    'banIp' => "<input type='checkbox' name='{$userId}-ip'>",
                    'rank' => "<span class=\"label label-{$rankConfig['label']}\">{$rankConfig['name']}</span>",
                    'ip' => (string)($value['ip'] ?? ''),
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

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_BAN'))) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        if (!$query) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        $banTable = $this->fetchTable('Bans');

        $result = $this->Users
            ->find('all', conditions: ['username LIKE' => $query . '%'])
            ->all();

        $users = [];
        foreach ($result as $value) {
            $checkIsBan = $banTable
                ->find('all', conditions: ['user_id' => $value['id']])
                ->first();

            if ($checkIsBan !== null) {
                continue;
            }

            if ($this->permissions->have($value['rank'], 'BYPASS_BAN')) {
                continue;
            }

            $users[] = [
                'username' => $value['username'],
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
