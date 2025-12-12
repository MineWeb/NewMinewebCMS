<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\Routing\Router;

/**
 * @property \App\Controller\Component\AuthComponent $Auth
 * @property \App\Controller\Component\APIComponent $API
 * @property \App\Controller\Component\UtilComponent $Util
 * @property \App\Controller\Component\EyPluginComponent $EyPlugin
 *
 * @property \App\Model\Table\UsersTable $User
 * @property \App\Model\Table\ServersTable $Server
 * @property \App\Model\Table\ConfigurationsTable $Configuration
 */
class UserController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

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

            $p = $identity->get('username');
            if (is_string($p)) {
                $username = $p;
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

        $this->viewBuilder()->setLayout($this->config->get('layout'));

        if ($this->EyPlugin->isInstalled('eywek.shop')) {
            $itemsHistoryTable = $this->fetchTable('Shop.ItemsBuyHistory');
            $histories = $itemsHistoryTable
                ->find()
                ->where(['user_id' => $userId])
                ->orderBy(['ItemsBuyHistory.created' => 'DESC'])
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

        $this->set('can_cape', $this->API->can_cape());
        $this->set('can_skin', $this->API->can_skin());

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
                'statut' => false,
                'msg' => __('USER__ERROR_MUST_BE_LOGGED'),
            ], 403);
        }

        if (!$this->getRequest()->is('ajax')) {
            return $this->json([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ], 400);
        }

        $data = (array)$this->getRequest()->getData();

        if (empty($data['password']) || empty($data['password_confirmation'])) {
            return $this->json([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ], 400);
        }

        $identity = $this->Auth->identity();
        $username = '';
        $userId = null;

        if (is_object($identity) && method_exists($identity, 'get')) {
            $p = $identity->get('username');
            if (is_string($p)) {
                $username = $p;
            }

            $id = $identity->get('id');
            if (is_numeric($id)) {
                $userId = (int)$id;
            }
        }

        if ($username === '' || $userId === null) {
            return $this->json([
                'statut' => false,
                'msg' => __('USER__ERROR_MUST_BE_LOGGED'),
            ], 403);
        }

        $password = $this->Util->password((string)$data['password'], $username);
        $password_confirmation = $this->Util->password((string)$data['password_confirmation'], $username, $password);

        if ($password !== $password_confirmation) {
            return $this->json([
                'statut' => false,
                'msg' => __('USER__ERROR_PASSWORDS_NOT_SAME'),
            ], 400);
        }

        $event = new Event('beforeUpdatePassword', $this, [
            'user' => $identity,
            'new_password' => $password,
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->json((array)$result, 400);
        }

        $userEntity = $this->User->get($userId);
        $userEntity->set([
            'password' => $password,
            'password_hash' => $this->Util->getPasswordHashType(),
        ]);
        $this->User->save($userEntity);

        $this->clearAuthContext();

        return $this->json([
            'statut' => true,
            'msg' => __('USER__PASSWORD_UPDATE_SUCCESS'),
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
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ], 400);
        }

        $data = (array)$this->getRequest()->getData();

        if (empty($data['email']) || empty($data['email_confirmation'])) {
            return $this->json([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ], 400);
        }

        if ((string)$data['email'] !== (string)$data['email_confirmation']) {
            return $this->json([
                'statut' => false,
                'msg' => __('USER__ERROR_EMAIL_NOT_SAME'),
            ], 400);
        }

        if (!filter_var((string)$data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->json([
                'statut' => false,
                'msg' => __('USER__ERROR_EMAIL_NOT_VALID'),
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
            'new_email' => (string)$data['email_confirmation'],
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->json((array)$result, 400);
        }

        $newEmail = htmlentities((string)$data['email']);

        $userEntity = $this->User->get($userId);
        $userEntity->set(['email' => $newEmail]);
        $this->User->save($userEntity);

        $this->clearAuthContext();

        return $this->json([
            'statut' => true,
            'msg' => __('USER__EMAIL_UPDATE_SUCCESS'),
        ]);
    }
}
