<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Model\Table\HistoriesTable;
use App\Model\Table\UsersTable;
use App\Service\ConfigurationService;
use App\Service\UserAuthService;
use App\Utility\LangService;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;

/**
 * @property \App\Controller\Component\AuthComponent $Auth
 * @property \Cake\Controller\Component\FlashComponent $Flash
 * @property \App\Controller\Component\EyPluginComponent $EyPlugin
 * @property \App\Controller\Component\HistoryComponent $History
 * @property \App\Controller\Component\DataTableComponent $DataTable
 */
class UserController extends AppController
{
    private UserAuthService $userAuth;

    private UsersTable $Users;
    private HistoriesTable $Histories;

    public function initialize(): void
    {
        parent::initialize();

        $this->Users = $this->fetchTable('Users');
        $this->Histories = $this->fetchTable('Histories');

        $this->userAuth = new UserAuthService();

        $this->loadComponent('DataTable');
    }

    public function index(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_USERS'))) {
            return $this->redirect('/');
        }

        $config = new ConfigurationService();
        $this->set('title_for_layout', __('USER__TITLE'));
        $this->set('type', (string)$config->get('member_page_type'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/User')
            ->setTemplate('index');

        return null;
    }

    public function getUsers(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_USERS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->getRequest()->is('ajax')) {
            return $this->response->withStringBody(json_encode([]));
        }

        $availableRanks = [
            0 => ['label' => 'success', 'name' => __('USER__RANK_MEMBER')],
            2 => ['label' => 'warning', 'name' => __('USER__RANK_MODERATOR')],
            3 => ['label' => 'danger', 'name' => __('USER__RANK_ADMINISTRATOR')],
            4 => ['label' => 'danger', 'name' => __('USER__RANK_ADMINISTRATOR')],
        ];

        $rankTable = $this->fetchTable('Ranks');
        $customRanks = $rankTable->find()->all();

        foreach ($customRanks as $value) {
            $availableRanks[(int)$value->get('rank_id')] = [
                'label' => 'info',
                'name' => (string)$value->get('name'),
            ];
        }

        $this->DataTable->setTable($this->Users);

        $this->paginate = [
            'fields' => ['Users.id', 'Users.username', 'Users.email', 'Users.created_at', 'Users.rank'],
        ];

        $this->DataTable->mDataProp = true;
        $response = $this->DataTable->getResponse();

        $users = $response['aaData'] ?? [];
        $data = [];

        foreach ($users as $value) {
            $username = (string)$value['username'];
            $date = 'Le ' . LangService::date($value['created_at']);

            $rankId = (int)($value['rank'] ?? 0);
            $rankLabel = $availableRanks[$rankId]['label'] ?? $availableRanks[0]['label'];
            $rankName = $availableRanks[$rankId]['name'] ?? $availableRanks[0]['name'];

            $rankHtml = '<span class="label label-' . $rankLabel . '">' . $rankName . '</span>';

            $editUrl = Router::url([
                '_name' => 'admin_user_edit',
                (int)$value['id'],
            ]);

            $deleteUrl = Router::url([
                '_name' => 'admin_user_delete',
                (int)$value['id'],
            ]);

            $btns = '<a href="' . $editUrl . '" class="btn btn-info">' . __('GLOBAL__EDIT') . '</a>';
            $btns .= '&nbsp;<a onClick="confirmDel(\'' . $deleteUrl . '\')" class="btn btn-danger">' . __('GLOBAL__DELETE') . '</a>';

            $data[] = [
                'Users' => [
                    'username' => $username,
                    'email' => (string)$value['email'],
                    'created_at' => $date,
                    'rank' => $rankHtml,
                ],
                'actions' => $btns,
            ];
        }

        $response['aaData'] = $data;

        return $this->response->withStringBody(json_encode($response));
    }

    public function edit(?string $search = null): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_USERS'))) {
            return $this->redirect('/');
        }

        if ($search === null || $search === '') {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('USER__EDIT_TITLE'));

        $searchUser = $this->Users
            ->find()
            ->where($this->Users->makeCondition($search))
            ->first();

        if ($searchUser === null) {
            throw new NotFoundException();
        }

        $lastHistory = $this->Histories->getLastFromUser((int)$searchUser->get('id'));
        $searchUser->set('History', $this->Histories->format($lastHistory));

        $optionsRanks = [
            0 => __('USER__RANK_MEMBER'),
            2 => __('USER__RANK_MODERATOR'),
            3 => __('USER__RANK_ADMINISTRATOR'),
            4 => __('USER__RANK_SUPER_ADMINISTRATOR'),
        ];

        $rankTable = $this->fetchTable('Ranks');
        $customRanks = $rankTable->find()->all();

        foreach ($customRanks as $value) {
            $optionsRanks[(int)$value->get('rank_id')] = (string)$value->get('name');
        }

        if (
            $this->config->get('confirm_mail_signup')
            && !empty($searchUser->get('confirmed'))
            && date('Y-m-d H:i:s', strtotime((string)$searchUser->get('confirmed'))) !== (string)$searchUser->get('confirmed')
        ) {
            $searchUser->set('confirmed', false);
        } else {
            $searchUser->set('confirmed', true);
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

        if (!($user_id !== null && $this->Auth->isConnected() && $this->Auth->can('MANAGE_USERS'))) {
            throw new NotFoundException();
        }

        $find = $this->Users
            ->find()
            ->select(['id'])
            ->where(['Users.id' => (int)$user_id])
            ->first();

        if ($find === null) {
            throw new NotFoundException();
        }

        $event = new Event('beforeConfirmAccount', $this, [
            'user_id' => (int)$find->get('id'),
            'manual' => true,
        ]);
        $this->getEventManager()->dispatch($event);

        if ($event->isStopped()) {
            $result = $event->getResult();

            return $result instanceof Response ? $result : $this->response;
        }

        $user = $this->Users->get((int)$find->get('id'));
        $user->set(['confirmed' => date('Y-m-d H:i:s')]);
        $this->Users->save($user);

        return $this->redirect([
            '_name' => 'admin_user_edit',
            (int)$user_id,
        ]);
    }

    public function editAjax(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_USERS'))) {
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
        $username = $request->getData('username');
        $rank = $request->getData('rank');

        if (
            empty($id)
            || empty($email)
            || empty($username)
            || ($rank === null && $rank !== 0 && $rank !== '0')
        ) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $findUser = $this->Users
            ->find()
            ->where(['Users.id' => (int)$id])
            ->first();

        if ($findUser === null) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__EDIT_ERROR_UNKNOWN'),
            ]));
        }

        $current = $this->Auth->user();
        $currentId = null;
        $currentRank = null;

        if (is_object($current)) {
            $currentId = $current->get('id');
            $currentRank = $current->get('rank');
        }

        if (
            (int)$findUser->get('id') === (int)$currentId
            && (string)$rank !== (string)$currentRank
        ) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__EDIT_ERROR_YOURSELF'),
            ]));
        }

        $data = [
            'email' => $email,
            'rank' => $rank,
            'username' => $username,
            'uuid' => $request->getData('uuid'),
        ];

        $passwordUpdated = false;

        $newPassword = (string)$request->getData('password');
        if ($newPassword !== '') {
            $data['password'] = $this->userAuth->hashPassword($newPassword);
            $data['password_hash'] = $this->userAuth->getPasswordHashType();
            $passwordUpdated = true;
        }

        if ($this->EyPlugin->isInstalled('eywek.shop')) {
            $data['money'] = $request->getData('money');
        }

        $event = new Event('beforeEditUser', $this, [
            'user_id' => (int)$findUser->get('id'),
            'data' => $data,
            'password_updated' => $passwordUpdated,
        ]);
        $this->getEventManager()->dispatch($event);

        if ($event->isStopped()) {
            $result = $event->getResult();

            return $result instanceof Response ? $result : $this->response;
        }

        $user = $this->Users->get((int)$findUser->get('id'));
        $user->set($data);
        $this->Users->save($user);

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

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_USERS'))) {
            return $this->redirect('/');
        }

        if ($id !== null) {
            $find = $this->Users
                ->find()
                ->where(['Users.id' => (int)$id])
                ->first();

            if ($find !== null) {
                $event = new Event('beforeDeleteUser', $this, ['user' => $find]);
                $this->getEventManager()->dispatch($event);

                if ($event->isStopped()) {
                    $result = $event->getResult();

                    return $result instanceof Response ? $result : $this->response;
                }

                $this->Users->delete($this->Users->get((int)$id));
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
