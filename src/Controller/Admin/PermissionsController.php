<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Service\PermissionService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

/**
 * @property \App\Controller\Component\AuthComponent $Auth
 * @property \App\Model\Table\RanksTable $Ranks
 * @property \App\Model\Table\PermissionsTable $Permissions
 */
class PermissionsController extends AppController
{
    private PermissionService $permissionService;

    public function initialize(): void
    {
        parent::initialize();

        $this->Ranks = $this->fetchTable('Ranks');
        $this->Permissions = $this->fetchTable('Permissions');

        $this->permissionService = new PermissionService();
    }

    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_PERMISSIONS')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('PERMISSIONS__LABEL'));

        $all_ranks = [
            [
                'rank_id' => 0,
                'name' => __('GLOBAL__TYPE_NORMAL'),
            ],
            [
                'rank_id' => 2,
                'name' => __('USER__RANK_MODERATOR'),
            ],
        ];

        $customRanks = $this->Ranks->find()->toArray();
        $all_ranks = array_merge($all_ranks, $customRanks);

        $this->set('all_ranks', $all_ranks);

        $request = $this->getRequest();

        if ($request->is('post')) {
            $permissionsByRank = [];
            foreach ($all_ranks as $rank) {
                $rankId = is_array($rank) ? ($rank['rank_id'] ?? null) : ($rank->rank_id ?? null);
                if ($rankId === null) {
                    continue;
                }
                $permissionsByRank[(int)$rankId] = [];
            }

            foreach ((array)$request->getData() as $key => $checked) {
                if (is_array($checked)) {
                    continue;
                }

                $key = (string)$key;
                if (!str_contains($key, '-')) {
                    continue;
                }

                [$permission, $rankIdRaw] = explode('-', $key, 2);
                $permission = trim((string)$permission);

                if ($permission === '') {
                    continue;
                }

                $rankId = (int)$rankIdRaw;

                if (!array_key_exists($rankId, $permissionsByRank)) {
                    continue;
                }

                $permissionsByRank[$rankId][] = $permission;
            }

            foreach ($permissionsByRank as $rankId => $permissions) {
                $permissions = array_values(array_unique(array_filter(array_map('strval', $permissions), static fn(string $v): bool => $v !== '')));

                $row = $this->Permissions
                    ->find()
                    ->where(['rank' => $rankId])
                    ->first();

                $entity = $row ? $this->Permissions->get((int)$row->id) : $this->Permissions->newEmptyEntity();

                $entity->set([
                    'permissions' => serialize($permissions),
                    'rank' => $rankId,
                ]);

                $this->Permissions->save($entity);

                $this->permissionService->clearCache($rankId);
            }

            $this->Flash->success(__('PERMISSIONS__SUCCESS_SAVE'));
        }

        $rankIds = [];
        foreach ($all_ranks as $rank) {
            $rid = is_array($rank) ? ($rank['rank_id'] ?? null) : ($rank->rank_id ?? null);
            if ($rid !== null) {
                $rankIds[] = (int)$rid;
            }
        }
        $rankIds = array_values(array_unique($rankIds));
        sort($rankIds);

        $permissions = $this->permissionService->getAllPermissionsMatrix($rankIds);

        $this->set('permissions', $permissions);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Permissions')
            ->setTemplate('index');

        return null;
    }

    public function addRank(): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_PERMISSIONS')) {
            return $this->redirect('/');
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        $request = $this->getRequest();

        if (!$request->is('ajax')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'message' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $name = (string)$request->getData('name', '');
        if ($name === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'message' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $lastRank = $this->Ranks
            ->find()
            ->orderBy(['rank_id' => 'DESC'])
            ->limit(1)
            ->first();

        $rank_id = $lastRank ? ((int)$lastRank->rank_id + 1) : 10;

        $entity = $this->Ranks->newEntity([
            'name' => $name,
            'rank_id' => $rank_id,
        ]);

        $this->Ranks->save($entity);

        $this->History->set('ADD_RANK', 'permissions');
        $this->Flash->success(__('USER__RANK_ADD_SUCCESS'));

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'message' => __('USER__RANK_ADD_SUCCESS'),
        ]));
    }

    public function deleteRank(int|string|null $id = null): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_PERMISSIONS')) {
            return $this->redirect('/');
        }

        $this->disableAutoRender();

        if ($id !== null) {
            $rankId = (int)$id;

            $rank = $this->Ranks
                ->find()
                ->where(['rank_id' => $rankId])
                ->first();

            if ($rank) {
                $this->Ranks->delete($rank);

                $perm = $this->Permissions
                    ->find()
                    ->where(['rank' => $rankId])
                    ->first();

                if ($perm) {
                    $this->Permissions->delete($perm);
                }

                $this->permissionService->clearCache($rankId);

                $this->Flash->success(__('USER__RANK_DELETE_SUCCESS'));
            }
        }

        return $this->redirect(['_name' => 'admin_permissions_index']);
    }
}
