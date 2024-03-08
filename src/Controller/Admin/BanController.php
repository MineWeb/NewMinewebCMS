<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\ORM\TableRegistry;

class BanController extends AppController
{
    function index()
    {
        if (!$this->isConnected || !$this->Permissions->can("MANAGE_BAN"))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get("BAN__HOME"));
        $banned_users = $this->Ban->find()->all();

        $this->set(compact("banned_users"));
    }

    function add()
    {
        if (!$this->isConnected || !$this->Permissions->can("MANAGE_BAN"))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get("BAN__HOME"));
        $this->set('type', $this->Configuration->getKey('member_page_type'));

        if ($this->request->is("post")) {
            $this->autoRender = false;
            $this->response = $this->response->withType('application/json');

            if (empty($this->getRequest()->getData("reason")))
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));

            foreach ($this->request->getData() as $key => $v) {
                if ($v != "on" || $key == "name" || strpos($key, "-ip"))
                    continue;

                $ban = $this->Ban->newEntity([
                    "user_id" => $key,
                    "reason" => $this->request->getData("reason")
                ]);
                if ($this->request->getData($key . "-ip") == "on")
                    $ban->set([
                        "ip" => $this->User->find("all", ["conditions" => ['id' => $key]])->first()['ip']
                    ]);

                $this->Ban->save($ban);
            }

            return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('BAN__SUCCESS')]));
        }
    }

    function unban($id = false)
    {
        if (!$this->isConnected || !$this->Permissions->can("MANAGE_BAN"))
            throw new ForbiddenException();

        $this->Ban->delete($this->Ban->get($id));
        $this->Flash->success($this->Lang->get('BAN__UNBAN_SUCCESS'));
        $this->redirect(['controller' => 'ban', 'action' => 'index', 'admin' => true]);
        return $this->response;
    }

    public function getUsersNotBan()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_BAN')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');
            if ($this->request->is('ajax')) {
                $available_ranks = [
                    0 => ['label' => 'success', 'name' => $this->Lang->get('USER__RANK_MEMBER')],
                    2 => ['label' => 'warning', 'name' => $this->Lang->get('USER__RANK_MODERATOR')],
                    3 => ['label' => 'danger', 'name' => $this->Lang->get('USER__RANK_ADMINISTRATOR')],
                    4 => ['label' => 'danger', 'name' => $this->Lang->get('USER__RANK_ADMINISTRATOR')]
                ];
                $this->Rank = TableRegistry::getTableLocator()->get('Rank');
                $custom_ranks = $this->Rank->find()->all();
                foreach ($custom_ranks as $value) {
                    $available_ranks[$value['rank_id']] = [
                        'label' => 'info',
                        'name' => $value['name']
                    ];
                }
                $this->DataTable = $this->loadComponent('DataTable');
                $this->DataTable->setTable($this->User);
                $this->paginate = [
                    'fields' => ['User.id', 'User.pseudo', 'User.rank', 'User.ip'],
                ];
                $this->DataTable->mDataProp = true;
                $response = $this->DataTable->getResponse();
                $users = $response['aaData'];
                $data = [];
                foreach ($users as $value) {
                    $checkIsBan = $this->Ban->find('all', ["conditions" => ['user_id' => $value['id']]])->first();

                    if ($checkIsBan != null)
                        continue;

                    if ($this->Permissions->have($value['rank'], "BYPASS_BAN"))
                        continue;

                    $username = $value['pseudo'];
                    $rank_label = (isset($available_ranks[$value['rank']])) ? $available_ranks[$value['rank']]['label'] : $available_ranks[0]['label'];
                    $rank_name = (isset($available_ranks[$value['rank']])) ? $available_ranks[$value['rank']]['name'] : $available_ranks[0]['name'];
                    $rank = '<span class="label label-' . $rank_label . '">' . $rank_name . '</span>';
                    $checkbox = "<input type='checkbox' name=" . $value['id'] . ">";
                    $banIpCheckbox = "<input type='checkbox' name=" . $value['id'] . "-ip>";
                    $data[] = [
                        'User' => [
                            'pseudo' => $username,
                            'ban' => $checkbox,
                            'banIp' => $banIpCheckbox,
                            'rank' => $rank,
                            'ip' => $value['ip']
                        ]
                    ];
                }
                $response['aaData'] = $data;
                return $this->response->withStringBody(json_encode($response));
            }
        }
    }

    function liveSearch($query = false)
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_BAN')) {
            if ($query) {
                $result = $this->User->find('all', ['conditions' => ['pseudo LIKE' => $query . '%']])->all();
                foreach ($result as $value) {
                    $checkIsBan = $this->Ban->find('all', ["conditions" => ['user_id' => $value['id']]])->first();

                    if ($checkIsBan != null)
                        continue;

                    if ($this->Permissions->have($value['rank'], "BYPASS_BAN"))
                        continue;

                    $users[] = ['pseudo' => $value['pseudo'], 'id' => $value['id']];
                }
                $response = (empty($result)) ? ['status' => false] : ['status' => true, 'data' => $users];
                return $this->response->withStringBody(json_encode($response));
            } else {
                return $this->response->withStringBody(json_encode(['status' => false]));
            }
        } else {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }
    }
}
