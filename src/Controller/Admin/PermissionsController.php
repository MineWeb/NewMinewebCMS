<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

class PermissionsController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Permissions->can('MANAGE_PERMISSIONS')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('PERMISSIONS__LABEL'));

        $rankTable = $this->fetchTable('Ranks');
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

        $customRanks = $rankTable->find()->toArray();
        $all_ranks = array_merge($all_ranks, $customRanks);

        $this->set('all_ranks', $all_ranks);

        $request = $this->getRequest();

        if ($request->is('post')) {
            $permissionsByRank = [];

            foreach ($all_ranks as $rank) {
                $permissionsByRank[$rank['rank_id']] = [];
            }

            foreach ($request->getData() as $key => $checked) {
                if (is_array($checked)) {
                    continue;
                }
                [$permission, $rankId] = explode('-', (string)$key);
                $permissionsByRank[$rankId][] = $permission;
            }

            $permissionTable = $this->fetchTable('Permissions');

            foreach ($permissionsByRank as $rankId => $permissions) {
                $row = $permissionTable
                    ->find()
                    ->where(['rank' => $rankId])
                    ->first();

                if ($row) {
                    $entity = $permissionTable->get($row['id']);
                } else {
                    $entity = $permissionTable->newEmptyEntity();
                }

                $entity->set([
                    'permissions' => serialize($permissions),
                    'rank' => $rankId,
                ]);

                $permissionTable->save($entity);
            }

            $this->Flash->success(__('PERMISSIONS__SUCCESS_SAVE'));
        }

        $this->Permissions->ranks = [];
        $this->set('permissions', $this->Permissions->get_all());

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Permissions')
            ->setTemplate('index');

        return null;
    }

    public function addRank(): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_PERMISSIONS'))) {
            return $this->redirect('/');
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        $request = $this->getRequest();

        if (!$request->is('ajax')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $name = (string)$request->getData('name', '');

        if ($name === '') {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $rankTable = $this->fetchTable('Ranks');

        $lastRank = $rankTable
            ->find()
            ->order(['rank_id' => 'DESC'])
            ->limit(1)
            ->first();

        if ($lastRank) {
            $rank_id = (int)$lastRank['rank_id'] + 1;
        } else {
            $rank_id = 10;
        }

        $entity = $rankTable->newEntity([
            'name' => $name,
            'rank_id' => $rank_id,
        ]);

        $rankTable->save($entity);

        $this->History->set('ADD_RANK', 'permissions');
        $this->Flash->success(__('USER__RANK_ADD_SUCCESS'));

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('USER__RANK_ADD_SUCCESS'),
        ]));
    }

    public function deleteRank(int|string|null $id = null): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_PERMISSIONS'))) {
            return $this->redirect('/');
        }

        $this->disableAutoRender();

        if ($id !== null) {
            $rankTable = $this->fetchTable('Ranks');
            $permissionTable = $this->fetchTable('Permissions');

            $rank = $rankTable
                ->find()
                ->where(['rank_id' => $id])
                ->first();

            if ($rank) {
                $rankTable->delete($rank);

                $perm = $permissionTable
                    ->find()
                    ->where(['rank' => $id])
                    ->first();

                if ($perm) {
                    $permissionTable->delete($perm);
                }

                $this->Flash->success(__('USER__RANK_DELETE_SUCCESS'));
            }
        }

        return $this->redirect(['_name' => 'admin_permissions_index']);
    }
}
