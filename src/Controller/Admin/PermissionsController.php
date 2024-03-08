<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;

class PermissionsController extends AppController
{
    function index()
    {
        if (!$this->Permissions->can('MANAGE_PERMISSIONS'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('PERMISSIONS__LABEL'));

        $this->Rank = TableRegistry::getTableLocator()->get('Rank');
        $all_ranks = [
            [
                'rank_id' => 0,
                'name' => $this->Lang->get('GLOBAL__TYPE_NORMAL'),
            ],
            [
                'rank_id' => 2,
                'name' => $this->Lang->get('USER__RANK_MODERATOR'),
            ],
        ];

        $all_ranks = array_merge($all_ranks, $this->Rank->find()->toArray());
        $this->set(compact('all_ranks'));

        if ($this->request->is('post')) {
            $permissions = [];

            foreach ($all_ranks as $rank) {
                $permissions[$rank['rank_id']] = [];
            }

            foreach ($this->request->getData() as $permission => $checked) {
                if (is_array($checked))
                    continue;
                list($permission, $rank) = explode('-', $permission);
                $permissions[$rank][] = $permission;
            }

            $this->Permission = TableRegistry::getTableLocator()->get('Permission');
            foreach ($permissions as $rank => $permission) {
                if (!empty($row = $this->Permission->find('all', conditions: ['rank' => $rank])->first()))
                    $perm = $this->Permission->get($row['id']);
                else
                    $perm = $this->Permission->newEmptyEntity();

                $perm->set([
                    'permissions' => serialize($permission),
                    'rank' => $rank
                ]);
                $this->Permission->save($perm);
            }

            $this->Flash->success($this->Lang->get('PERMISSIONS__SUCCESS_SAVE'));
        }

        $this->Permissions->ranks = [];
        $this->set('permissions', $this->Permissions->get_all());
    }

    function addRank()
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_PERMISSIONS')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');
            if ($this->request->is('ajax')) {
                if (!empty($this->getRequest()->getData('name'))) {
                    $this->Rank = TableRegistry::getTableLocator()->get('Rank');

                    // Le rank_id | L'id du rank utilisé dans le composant des permissions & dans la colonne rank des utilisateurs
                    // Le rank_id de base pour les rangs personnalisés commence à partir de 10
                    $rank_id = $this->Rank->find('all', limit: '1', order: 'rank_id desc')->first();
                    if (!empty($rank_id)) {
                        $rank_id = $rank_id['rank_id'] + 1;
                    } else {
                        $rank_id = 10;
                    }

                    // on save
                    $rank = $this->Rank->newEntity(['name' => $this->getRequest()->getData('name'), 'rank_id' => $rank_id]);
                    $this->Rank->save($rank);

                    $this->History->set('ADD_RANK', 'permissions');

                    $this->Flash->success($this->Lang->get('USER__RANK_ADD_SUCCESS'));
                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('USER__RANK_ADD_SUCCESS')]));

                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
                }

            } else {
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')]));
            }
        } else {
            $this->redirect('/');
        }
    }

    function deleteRank($id = false)
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_PERMISSIONS')) {
            $this->disableAutoRender();

            $this->Rank = TableRegistry::getTableLocator()->get('Rank');
            $search = $this->Rank->find('all', conditions: ['rank_id' => $id])->first();
            if (!empty($search)) {
                $this->Rank->delete($search);

                $this->Permission = TableRegistry::getTableLocator()->get('Permission');
                $search_perm = $this->Permission->find('all', conditions: ['rank' => $id])->first();
                if (!empty($search_perm)) {
                    $this->Permission->delete($search_perm);
                }

                $this->Flash->success($this->Lang->get('USER__RANK_DELETE_SUCCESS'));

            }
            $this->redirect(['controller' => 'permissions', 'action' => 'index', 'admin' => true]);
        } else {
            $this->redirect('/');
        }
    }
}
