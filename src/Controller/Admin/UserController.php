<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Utility\LangService;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

class UserController extends AppController
{
    public function index(): ?Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_USERS'))) {
            return $this->redirect('/');
        }

        $this->set('title_for_layout', __('USER__TITLE'));
        $this->set('type', $this->Configuration->getKey('member_page_type'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/User')
            ->setTemplate('index');

        return null;
    }

    public function liveSearch(?string $query = null): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->isConnected && $this->Permissions->can('MANAGE_USERS'))) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        if ($query === null || $query === '') {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        $result = $this->User
            ->find('all', ['conditions' => ['pseudo LIKE' => $query . '%']])
            ->all();

        $users = [];
        foreach ($result as $value) {
            $users[] = [
                'pseudo' => $value['pseudo'],
                'id' => $value['id'],
            ];
        }

        $response = empty($result)
            ? ['status' => false]
            : ['status' => true, 'data' => $users];

        return $this->response->withStringBody(json_encode($response));
    }

    public function getUsers(): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_USERS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->request->is('ajax')) {
            return $this->response->withStringBody(json_encode([]));
        }

        $availableRanks = [
            0 => ['label' => 'success', 'name' => __('USER__RANK_MEMBER')],
            2 => ['label' => 'warning', 'name' => __('USER__RANK_MODERATOR')],
            3 => ['label' => 'danger', 'name' => __('USER__RANK_ADMINISTRATOR')],
            4 => ['label' => 'danger', 'name' => __('USER__RANK_ADMINISTRATOR')],
        ];

        $rankTable = TableRegistry::getTableLocator()->get('Rank');
        $customRanks = $rankTable->find()->all();

        foreach ($customRanks as $value) {
            $availableRanks[$value['rank_id']] = [
                'label' => 'info',
                'name' => $value['name'],
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
            $date = 'Le ' . LangService::date($value['created']);

            $rankLabel = $availableRanks[$value['rank']]['label'] ?? $availableRanks[0]['label'];
            $rankName = $availableRanks[$value['rank']]['name'] ?? $availableRanks[0]['name'];

            $rankHtml = '<span class="label label-' . $rankLabel . '">' . $rankName . '</span>';

            $editUrl = Router::url([
                '_name' => 'admin_user_edit',
                $value['id'],
            ]);

            $deleteUrl = Router::url([
                '_name' => 'admin_user_delete',
                $value['id'],
            ]);

            $btns = '<a href="' . $editUrl . '" class="btn btn-info">' . __('GLOBAL__EDIT') . '</a>';
            $btns .= '&nbsp;<a onClick="confirmDel(\'' . $deleteUrl . '\')" class="btn btn-danger">' . __('GLOBAL__DELETE') . '</a>';

            $data[] = [
                'User' => [
                    'pseudo' => $username,
                    'email' => $value['email'],
                    'created' => $date,
                    'rank' => $rankHtml,
                ],
                'actions' => $btns,
            ];
        }

        $response['aaData'] = $data;

        return $this->response->withStringBody(json_encode($response));
    }

    public function edit(string $search = null): ?Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_USERS'))) {
            return $this->redirect('/');
        }

        if ($search === null || $search === '') {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('USER__EDIT_TITLE'));

        $searchUser = $this->User
            ->find('all', ['conditions' => $this->User->__makeCondition($search)])
            ->first();

        if ($searchUser === null) {
            throw new NotFoundException();
        }

        $historyTable = TableRegistry::getTableLocator()->get('History');
        $lastHistory = $historyTable->getLastFromUser($searchUser['id']);
        $searchUser['History'] = $historyTable->format($lastHistory);

        $optionsRanks = [
            0 => __('USER__RANK_MEMBER'),
            2 => __('USER__RANK_MODERATOR'),
            3 => __('USER__RANK_ADMINISTRATOR'),
            4 => __('USER__RANK_SUPER_ADMINISTRATOR'),
        ];

        $rankTable = TableRegistry::getTableLocator()->get('Rank');
        $customRanks = $rankTable->find()->all();

        foreach ($customRanks as $value) {
            $optionsRanks[$value['rank_id']] = $value['name'];
        }

        if (
            $this->Configuration->getKey('confirm_mail_signup')
            && !empty($searchUser['confirmed'])
            && date('Y-m-d H:i:s', strtotime($searchUser['confirmed'])) !== $searchUser['confirmed']
        ) {
            $searchUser['confirmed'] = false;
        } else {
            $searchUser['confirmed'] = true;
        }

        $this->set(compact('optionsRanks', 'searchUser'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/User')
            ->setTemplate('edit');

        return null;
    }

    public function confirm(int|string|null $user_id = null): Response
    {
        $this->disableAutoRender();

        if (!($user_id !== null && $this->isConnected && $this->Permissions->can('MANAGE_USERS'))) {
            throw new NotFoundException();
        }

        $find = $this->User
            ->find('all', ['conditions' => ['id' => $user_id]])
            ->first();

        if (empty($find)) {
            throw new NotFoundException();
        }

        $event = new Event('beforeConfirmAccount', $this, [
            'user_id' => $find['id'],
            'manual' => true,
        ]);
        $this->getEventManager()->dispatch($event);

        if ($event->isStopped()) {
            $result = $event->getResult();
            return $result instanceof Response ? $result : $this->response;
        }

        $user = $this->User->get($find['id']);
        $user->set(['confirmed' => date('Y-m-d H:i:s')]);
        $this->User->save($user);

        return $this->redirect([
            '_name' => 'admin_user_edit',
            $user_id,
        ]);
    }

    public function editAjax(): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_USERS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        $request = $this->getRequest();

        if (!$request->is('post')) {
            throw new NotFoundException();
        }

        $id = $request->getData('id');
        $email = $request->getData('email');
        $pseudo = $request->getData('pseudo');
        $rank = $request->getData('rank');

        if (
            empty($id)
            || empty($email)
            || empty($pseudo)
            || ($rank === null && $rank !== 0 && $rank !== '0')
        ) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $findUser = $this->User
            ->find('all', ['conditions' => ['id' => (int)$id]])
            ->first();

        if (empty($findUser)) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__EDIT_ERROR_UNKNOWN'),
            ]));
        }

        if (
            $findUser['id'] === $this->User->getKey('id')
            && (string)$rank !== (string)$this->User->getKey('rank')
        ) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__EDIT_ERROR_YOURSELF'),
            ]));
        }

        $data = [
            'email' => $email,
            'rank' => $rank,
            'pseudo' => $pseudo,
            'uuid' => $request->getData('uuid'),
        ];

        $passwordUpdated = false;

        $newPassword = $request->getData('password');
        if (!empty($newPassword)) {
            $data['password'] = $this->Util->password($newPassword, $findUser['pseudo']);
            $passwordUpdated = true;
        }

        if ($this->EyPlugin->isInstalled('eywek.shop')) {
            $data['money'] = $request->getData('money');
        }

        $event = new Event('beforeEditUser', $this, [
            'user_id' => $findUser['id'],
            'data' => $data,
            'password_updated' => $passwordUpdated,
        ]);
        $this->getEventManager()->dispatch($event);

        if ($event->isStopped()) {
            $result = $event->getResult();
            return $result instanceof Response ? $result : $this->response;
        }

        $user = $this->User->get($findUser['id']);
        $user->set($data);
        $this->User->save($user);

        $this->History->set('EDIT_USER', 'user');
        $this->Flash->success(__('USER__EDIT_SUCCESS'));

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('USER__EDIT_SUCCESS'),
        ]));
    }

    public function delete(int|string|null $id = null): Response
    {
        $this->disableAutoRender();

        if (!($this->isConnected && $this->Permissions->can('MANAGE_USERS'))) {
            return $this->redirect('/');
        }

        if ($id !== null) {
            $find = $this->User
                ->find('all', ['conditions' => ['id' => $id]])
                ->first();

            if (!empty($find)) {
                $event = new Event('beforeDeleteUser', $this, ['user' => $find]);
                $this->getEventManager()->dispatch($event);

                if ($event->isStopped()) {
                    $result = $event->getResult();
                    return $result instanceof Response ? $result : $this->response;
                }

                $this->User->delete($this->User->get($id));
                $this->History->set('DELETE_USER', 'user');
                $this->Flash->success(__('USER__DELETE_SUCCESS'));
            } else {
                $this->Flash->error(__('UNKNONW_ID'));
            }
        }

        return $this->redirect([
            '_name' => 'admin_user_index',
        ]);
    }
}
