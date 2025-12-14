<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Model\Table\BansTable;
use App\Model\Table\UsersTable;
use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

/**
 * @property \App\Controller\Component\DataTableComponent $DataTable
 */
class BanController extends AppController
{
    protected PermissionService $permissions;
    private UsersTable $Users;
    private BansTable $Bans;

    public function initialize(): void
    {
        parent::initialize();

        $this->Bans = $this->fetchTable('Bans');
        $this->Users = $this->fetchTable('Users');

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
                    'status' => false,
                    'messages' => __('ERROR__FILL_ALL_FIELDS'),
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
                'status' => true,
                'messages' => __('BAN__SUCCESS'),
            ]));
        }

        return null;
    }

    public function unban(int|string $id): Response
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

        $rolesConfig = $this->buildRolesConfig();

        $this->DataTable->setTable($this->Users);
        $this->paginate = [
            'fields' => ['Users.id', 'Users.username', 'Users.role_id', 'Users.ip'],
        ];
        $this->DataTable->mDataProp = true;

        $response = $this->DataTable->getResponse();
        $users = $response['aaData'] ?? [];

        $data = [];

        foreach ($users as $value) {
            $userId = (int)($value['id'] ?? 0);
            $roleId = (int)($value['role_id'] ?? 0);

            if ($userId <= 0) {
                continue;
            }

            if ($this->Bans->exists(['user_id' => $userId])) {
                continue;
            }

            if ($this->permissions->canRole($roleId, 'BYPASS_BAN')) {
                continue;
            }

            $roleCfg = $rolesConfig[$roleId] ?? $this->fallbackRoleConfig($roleId);
            $roleHtml = '<span class="badge badge-' . $roleCfg['label'] . '">' . $roleCfg['name'] . '</span>';

            $data[] = [
                'Users' => [
                    'username' => (string)($value['username'] ?? ''),
                    'ban' => "<input type='checkbox' name='{$userId}'>",
                    'banIp' => "<input type='checkbox' name='{$userId}-ip'>",
                    'rank' => $roleHtml,
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
            ->find()
            ->select(['id', 'username', 'role_id'])
            ->where(['Users.username LIKE' => $query . '%'])
            ->all();

        $users = [];
        foreach ($result as $value) {
            $userId = (int)($value['id'] ?? 0);
            $roleId = (int)($value['role_id'] ?? 0);

            if ($userId <= 0) {
                continue;
            }

            $checkIsBan = $banTable
                ->find()
                ->where(['user_id' => $userId])
                ->first();

            if ($checkIsBan !== null) {
                continue;
            }

            if ($this->permissions->canRole($roleId, 'BYPASS_BAN')) {
                continue;
            }

            $users[] = [
                'username' => (string)($value['username'] ?? ''),
                'id' => $userId,
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

    private function buildRolesConfig(): array
    {
        $Roles = $this->fetchTable('Roles');
        $roles = $Roles->find()
            ->orderBy(['sort' => 'ASC', 'id' => 'ASC'])
            ->all();

        $out = [];
        foreach ($roles as $r) {
            $roleId = (int)($r->id ?? 0);
            if ($roleId <= 0) {
                continue;
            }

            $slug = (string)($r->slug ?? '');

            $label = 'info';
            if ($slug === PermissionService::ADMIN_SLUG) {
                $label = 'danger';
            } elseif ((int)($r->is_default ?? 0) === 1) {
                $label = 'primary';
            } elseif ((int)($r->is_system ?? 0) === 1) {
                $label = 'secondary';
            }

            $out[$roleId] = [
                'label' => $label,
                'name' => $r->display_name,
            ];
        }

        return $out;
    }

    private function fallbackRoleConfig(int $roleId): array
    {
        return [
            'label' => 'info',
            'name' => (string)$roleId,
        ];
    }
}
