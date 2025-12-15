<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\UsersTable;
use App\Service\UserAuthService;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\Routing\Router;

final class UserController extends AppController
{
    private UsersTable $Users;
    private UserAuthService $userAuth;

    public function initialize(): void
    {
        parent::initialize();

        $this->Users = $this->fetchTable('Users');
        $this->userAuth = new UserAuthService();

        $this->loadComponent('API');
    }

    private function json(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload));
    }

    private function clearAuthContext(): void
    {
        $this->getRequest()->getSession()->delete('auth_context');
    }

    public function profile(): Response
    {
        if (!$this->Auth->isConnected()) {
            return $this->redirect(['_name' => 'home']);
        }

        $identity = $this->Auth->identity();
        $userId = null;
        $username = '';

        if (is_object($identity) && method_exists($identity, 'get')) {
            $id = $identity->get('id');
            if (is_numeric($id)) {
                $userId = (int)$id;
            }

            $u = $identity->get('username');
            if (is_string($u)) {
                $username = $u;
            }
        }

        if ($userId === null) {
            return $this->redirect(['_name' => 'home']);
        }

        $authTable = $this->fetchTable('UsersTwofactorauth');
        $infos = $authTable
            ->find()
            ->where([
                'user_id' => $userId,
                'enabled' => true,
            ])
            ->first();

        $this->set('twoFactorAuthStatus', !empty($infos));
        $this->set('title_for_layout', $username);

        $this->viewBuilder()->setLayout((string)$this->config->get('layout'));

        if ($this->addons->isInstalled('eywek.shop')) {
            $itemsHistoryTable = $this->fetchTable('Shop.ItemsBuyHistory');
            $histories = $itemsHistoryTable
                ->find()
                ->where(['user_id' => $userId])
                ->orderBy(['ItemsBuyHistory.created_at' => 'DESC'])
                ->all();

            $this->set(compact('histories'));
            $this->set('shop_active', true);
        } else {
            $this->set('shop_active', false);
        }

        $available_ranks = [
            0 => __('USER__RANK_MEMBER'),
            2 => __('USER__RANK_MODERATOR'),
            3 => __('USER__RANK_ADMINISTRATOR'),
            4 => __('USER__RANK_ADMINISTRATOR'),
        ];

        $rankTable = $this->fetchTable('Ranks');
        foreach ($rankTable->find()->all() as $value) {
            $available_ranks[(int)$value['rank_id']] = (string)$value['name'];
        }
        $this->set(compact('available_ranks'));

        $this->set('can_cape', (bool)$this->API->can_cape());
        $this->set('can_skin', (bool)$this->API->can_skin());

        $apiConfigTable = $this->fetchTable('ApiConfigurations');
        $configAPI = $apiConfigTable->find()->first();

        if ($configAPI !== null) {
            $skin_width_max = (int)$configAPI['skin_width'];
            $skin_height_max = (int)$configAPI['skin_height'];
            $cape_width_max = (int)$configAPI['cape_width'];
            $cape_height_max = (int)$configAPI['cape_height'];

            $this->set(compact('skin_width_max', 'skin_height_max', 'cape_width_max', 'cape_height_max'));
        }

        $confirmed = '';
        if (is_object($identity) && method_exists($identity, 'get')) {
            $c = $identity->get('confirmed');
            if (is_string($c)) {
                $confirmed = $c;
            }
        }

        if (
            (bool)$this->config->get('confirm_mail_signup') &&
            $confirmed !== '' &&
            date('Y-m-d H:i:s', strtotime($confirmed)) !== $confirmed
        ) {
            $this->Flash->warning(__('USER__MSG_NOT_CONFIRMED_EMAIL', [
                '{URL_RESEND_EMAIL}' => Router::url(['_name' => 'auth_resend_confirmation']),
            ]));
        }

        $connected_by_microsoft = $this->getRequest()->getCookie('microsoft_user_id') !== null;
        $this->set(compact('connected_by_microsoft'));

        $this->viewBuilder()
            ->setTemplatePath('User')
            ->setTemplate('profile');

        return $this->render();
    }

    public function changePw(): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected()) {
            return $this->json([
                'status' => false,
                'messages' => __('USER__ERROR_MUST_BE_LOGGED'),
            ], 403);
        }

        if (!$this->getRequest()->is('ajax')) {
            return $this->json([
                'status' => false,
                'messages' => __('ERROR__BAD_REQUEST'),
            ], 400);
        }

        $data = (array)$this->getRequest()->getData();

        $passwordRaw = (string)($data['password'] ?? '');
        $passwordConfirmRaw = (string)($data['password_confirmation'] ?? '');

        if ($passwordRaw === '' || $passwordConfirmRaw === '') {
            return $this->json([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ], 400);
        }

        if ($passwordRaw !== $passwordConfirmRaw) {
            return $this->json([
                'status' => false,
                'messages' => __('USER__ERROR_PASSWORDS_NOT_SAME'),
            ], 400);
        }

        $identity = $this->Auth->identity();
        $userId = null;

        if (is_object($identity) && method_exists($identity, 'get')) {
            $id = $identity->get('id');
            if (is_numeric($id)) {
                $userId = (int)$id;
            }
        }

        if ($userId === null) {
            return $this->json([
                'status' => false,
                'messages' => __('USER__ERROR_MUST_BE_LOGGED'),
            ], 403);
        }

        $hashedPassword = $this->userAuth->hashPassword($passwordRaw);

        $event = new Event('beforeUpdatePassword', $this, [
            'user' => $identity,
            'new_password' => $hashedPassword,
        ]);
        $this->getEventManager()->dispatch($event);

        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->json((array)$result, 400);
        }

        $userEntity = $this->Users->get($userId);
        $userEntity->patch([
            'password' => $hashedPassword,
            'password_hash' => $this->userAuth->getPasswordHashType(),
        ]);
        $this->Users->save($userEntity);

        $this->clearAuthContext();

        return $this->json([
            'status' => true,
            'messages' => __('USER__PASSWORD_UPDATE_SUCCESS'),
        ]);
    }

    public function changeEmail(): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected() || !$this->Auth->can('EDIT_HIS_EMAIL')) {
            throw new ForbiddenException();
        }

        if (!$this->getRequest()->is('ajax')) {
            return $this->json([
                'status' => false,
                'messages' => __('ERROR__BAD_REQUEST'),
            ], 400);
        }

        $data = (array)$this->getRequest()->getData();

        $email = (string)($data['email'] ?? '');
        $emailConfirmation = (string)($data['email_confirmation'] ?? '');

        if ($email === '' || $emailConfirmation === '') {
            return $this->json([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ], 400);
        }

        if ($email !== $emailConfirmation) {
            return $this->json([
                'status' => false,
                'messages' => __('USER__ERROR_EMAIL_NOT_SAME'),
            ], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'status' => false,
                'messages' => __('USER__ERROR_EMAIL_NOT_VALID'),
            ], 400);
        }

        $identity = $this->Auth->identity();
        $userId = null;

        if (is_object($identity) && method_exists($identity, 'get')) {
            $id = $identity->get('id');
            if (is_numeric($id)) {
                $userId = (int)$id;
            }
        }

        if ($userId === null) {
            throw new ForbiddenException();
        }

        $event = new Event('beforeUpdateEmail', $this, [
            'user' => $identity,
            'new_email' => $emailConfirmation,
        ]);
        $this->getEventManager()->dispatch($event);

        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->json((array)$result, 400);
        }

        $newEmail = htmlentities($email, ENT_QUOTES, 'UTF-8');

        $userEntity = $this->Users->get($userId);
        $userEntity->patch(['email' => $newEmail]);
        $this->Users->save($userEntity);

        $this->clearAuthContext();

        return $this->json([
            'status' => true,
            'messages' => __('USER__EMAIL_UPDATE_SUCCESS'),
        ]);
    }
}
