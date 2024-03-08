<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

class UserController extends AppController
{
    function index()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_USERS')) {
            $this->set('title_for_layout', $this->Lang->get('USER__TITLE'));
            $this->set('type', $this->Configuration->getKey('member_page_type'));
        } else {
            $this->redirect('/');
        }
    }

    function liveSearch($query = false)
    {
        $this->disableAutoRender();
        $this->response->withType('json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_USERS')) {
            if ($query) {
                $result = $this->User->find('all', ['conditions' => ['pseudo LIKE' => $query . '%']])->all();
                $users = [];
                foreach ($result as $value) {
                    $users[] = ['pseudo' => $value['pseudo'], 'id' => $value['id']];
                }
                $response = (empty($result)) ? ['status' => false] : ['status' => true, 'data' => $users];
                $this->response->withStringBody(json_encode($response));
            } else {
                $this->response->withStringBody(json_encode(['status' => false]));
            }
        } else {
            $this->response->withStringBody(json_encode(['status' => false]));
        }
    }

    public function getUsers()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_USERS')) {
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
                    'fields' => ['User.id', 'User.pseudo', 'User.email', 'User.created', 'User.rank'],
                ];
                $this->DataTable->mDataProp = true;
                $response = $this->DataTable->getResponse();
                $users = $response['aaData'];
                $data = [];
                foreach ($users as $value) {
                    $username = $value['pseudo'];
                    $date = 'Le ' . $this->Lang->date($value['created']);
                    $rank_label = (isset($available_ranks[$value['rank']])) ? $available_ranks[$value['rank']]['label'] : $available_ranks[0]['label'];
                    $rank_name = (isset($available_ranks[$value['rank']])) ? $available_ranks[$value['rank']]['name'] : $available_ranks[0]['name'];
                    $rank = '<span class="label label-' . $rank_label . '">' . $rank_name . '</span>';
                    $btns = '<a href="' . Router::url([
                            'controller' => 'user',
                            'action' => 'edit/' . $value["id"],
                            'admin' => true
                        ]) . '" class="btn btn-info">' . $this->Lang->get('GLOBAL__EDIT') . '</a>';
                    $btns .= '&nbsp;<a onClick="confirmDel(\'' . Router::url([
                            'controller' => 'user',
                            'action' => 'delete/' . $value["id"],
                            'admin' => true
                        ]) . '\')" class="btn btn-danger">' . $this->Lang->get('GLOBAL__DELETE') . '</button>';
                    $data[] = [
                        'User' => [
                            'pseudo' => $username,
                            'email' => $value['email'],
                            'created' => $date,
                            'rank' => $rank
                        ],
                        'actions' => $btns
                    ];
                }
                $response['aaData'] = $data;
                return $this->response->withStringBody(json_encode($response));
            } else {
                return $this->response->withStringBody(json_encode([]));
            }
        } else {
            throw new ForbiddenException();
        }
    }

    function edit($search = false)
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_USERS')) {
            if ($search) {
                $this->set('title_for_layout', $this->Lang->get('USER__EDIT_TITLE'));
                $search_user = $this->User->find('all', ['conditions' => $this->User->__makeCondition($search)])->first();
                if ($search_user != null) {
                    $this->History = TableRegistry::getTableLocator()->get('History');
                    $findHistory = $this->History->getLastFromUser($search_user['id']);
                    $search_user['History'] = $this->History->format($findHistory, $this->Lang);
                    $options_ranks = [
                        0 => $this->Lang->get('USER__RANK_MEMBER'),
                        2 => $this->Lang->get('USER__RANK_MODERATOR'),
                        3 => $this->Lang->get('USER__RANK_ADMINISTRATOR'),
                        4 => $this->Lang->get('USER__RANK_SUPER_ADMINISTRATOR')
                    ];
                    $this->Rank = TableRegistry::getTableLocator()->get('Rank');
                    $custom_ranks = $this->Rank->find()->all();
                    foreach ($custom_ranks as $value) {
                        $options_ranks[$value['rank_id']] = $value['name'];
                    }
                    if ($this->Configuration->getKey('confirm_mail_signup') && !empty($search_user['confirmed']) && date('Y-m-d H:i:s', strtotime($search_user['confirmed'])) != $search_user['confirmed']) {
                        $search_user['confirmed'] = false;
                    } else {
                        $search_user['confirmed'] = true;
                    }
                    $this->set(compact('options_ranks'));
                    $this->set(compact('search_user'));
                } else {
                    throw new NotFoundException();
                }
            } else {
                throw new NotFoundException();
            }
        } else {
            $this->redirect('/');
        }
    }

    function confirm($user_id = false)
    {
        $this->autoRender = false;
        if (isset($user_id) && $this->isConnected and $this->Permissions->can('MANAGE_USERS')) {
            $find = $this->User->find('all', ['conditions' => ['id' => $user_id]])->first();
            if (!empty($find)) {
                $event = new Event('beforeConfirmAccount', $this, ['user_id' => $find['id'], 'manual' => true]);
                $this->getEventManager()->dispatch($event);
                if ($event->isStopped()) {
                    return $event->getResult();
                }
                $user = $this->User->get($find['id']);
                $user->set(['confirmed' => date('Y-m-d H:i:s')]);
                $this->User->save($user);
                $this->redirect(['action' => 'edit', $user_id]);
                return $this->response;
            } else {
                throw new NotFoundException();
            }
        } else {
            throw new NotFoundException();
        }
    }

    function editAjax()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected && $this->Permissions->can('MANAGE_USERS')) {
            if ($this->request->is('post')) {
                if (!empty($this->getRequest()->getData('id')) && !empty($this->getRequest()->getData('email')) && !empty($this->getRequest()->getData('pseudo')) && (!empty($this->getRequest()->getData('rank')) || $this->getRequest()->getData('rank') == 0)) {
                    $this->request = $this->getRequest()->withData('', $this->request->getData('xss'));
                    $findUser = $this->User->find('all',
                        ['conditions' => ['id' => intval($this->getRequest()->getData('id'))]])->first();
                    if (empty($findUser)) {
                        return $this->response->withStringBody(json_encode([
                            'statut' => false,
                            'msg' => $this->Lang->get('USER__EDIT_ERROR_UNKNOWN')
                        ]));
                    }
                    if ($findUser['id'] == $this->User->getKey('id') && $this->getRequest()->getData('rank') != $this->User->getKey('rank')) {
                        return $this->response->withStringBody(json_encode([
                            'statut' => false,
                            'msg' => $this->Lang->get('USER__EDIT_ERROR_YOURSELF')
                        ]));
                    }
                    $data = [
                        'email' => $this->getRequest()->getData('email'),
                        'rank' => $this->getRequest()->getData('rank'),
                        'pseudo' => $this->getRequest()->getData('pseudo'),
                        'uuid' => $this->getRequest()->getData('uuid')
                    ];

                    if (!empty($this->getRequest()->getData('password'))) {
                        $data['password'] = $this->Util->password($this->getRequest()->getData('password'), $findUser['pseudo']);
                        $password_updated = true;
                    } else {
                        $password_updated = false;
                    }
                    if ($this->EyPlugin->isInstalled('eywek.shop')) {
                        $data['money'] = $this->getRequest()->getData('money');
                    }
                    $event = new Event('beforeEditUser', $this, [
                        'user_id' => $findUser['id'],
                        'data' => $data,
                        'password_updated' => $password_updated
                    ]);
                    $this->getEventManager()->dispatch($event);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }
                    $user = $this->User->get($findUser['id']);
                    $user->set($data);
                    $this->User->save($user);
                    $this->History->set('EDIT_USER', 'user');
                    $this->Flash->success($this->Lang->get('USER__EDIT_SUCCESS'));
                    return $this->response->withStringBody(json_encode([
                        'statut' => true,
                        'msg' => $this->Lang->get('USER__EDIT_SUCCESS')
                    ]));
                } else {
                    return $this->response->withStringBody(json_encode([
                        'statut' => false,
                        'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')
                    ]));
                }
            } else {
                throw new NotFoundException();
            }
        } else {
            throw new ForbiddenException();
        }
    }

    function delete($id = false)
    {
        $this->disableAutoRender();
        if ($this->isConnected and $this->Permissions->can('MANAGE_USERS')) {
            if ($id) {
                $find = $this->User->find('all', ['conditions' => ['id' => $id]])->first();
                if (!empty($find)) {
                    $event = new Event('beforeDeleteUser', $this, ['user' => $find]);
                    $this->getEventManager()->dispatch($event);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }
                    $this->User->delete($this->User->get($id));
                    $this->History->set('DELETE_USER', 'user');
                    $this->Flash->success($this->Lang->get('USER__DELETE_SUCCESS'));
                } else {
                    $this->Flash->error($this->Lang->get('UNKNONW_ID'));
                }
            }
            $this->redirect(['controller' => 'user', 'action' => 'index', 'admin' => true]);
            return $this->response;
        } else {
            $this->redirect('/');
            return $this->response;
        }
    }
}
