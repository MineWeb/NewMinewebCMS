<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;
use DateTime;

class UserController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Captcha');
        $this->loadComponent('API');
    }

    public function getCaptcha(): Response
    {
        $this->disableAutoRender();

        $random = mt_rand(100, 99999);
        $this->getRequest()->getSession()->write('captcha_code', $random);

        $settings = [
            'characters' => $random,
            'winHeight' => 50,
            'winWidth' => 220,
            'fontSize' => 25,
            'fontPath' => WWW_ROOT . 'tahomabd.ttf',
            'noiseColor' => '#ccc',
            'bgColor' => '#fff',
            'noiseLevel' => '100',
            'textColor' => '#000',
        ];

        $img = $this->Captcha->ShowImage($settings);

        return $this->response
            ->withType('image/png')
            ->withStringBody($img);
    }

    public function ajaxRegister(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->getRequest()->is('post')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $data = $this->getRequest()->getData();

        $conditionsChecked = !empty($data['condition']) || !$this->Configuration->getKey('condition');

        if (
            empty($data['pseudo']) ||
            empty($data['password']) ||
            !$conditionsChecked ||
            empty($data['password_confirmation']) ||
            empty($data['email'])
        ) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        if ($this->Configuration->getKey('check_uuid')) {
            $pseudo = htmlentities((string)$data['pseudo']);
            $pseudoToUUID = @file_get_contents('https://api.mojang.com/users/profiles/minecraft/' . $pseudo);
            if (!$pseudoToUUID) {
                return $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('USER__ERROR_UUID'),
                ]));
            }

            $decoded = json_decode($pseudoToUUID, true);
            if (!is_array($decoded) || !isset($decoded['id'])) {
                return $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('USER__ERROR_UUID'),
                ]));
            }

            $data['uuid'] = $decoded['id'];
        }

        if ($this->Configuration->getKey('captcha_type') === '2' || $this->Configuration->getKey('captcha_type') === '3') {
            $validCaptcha = $this->Util->isValidReCaptcha(
                (string)($data['recaptcha'] ?? ''),
                $this->Util->getIP(),
                (string)$this->Configuration->getKey('captcha_secret'),
                (int)$this->Configuration->getKey('captcha_type'),
            );
        } else {
            $captcha = $this->getRequest()->getSession()->read('captcha_code');
            $validCaptcha = (!empty($captcha) && (string)$captcha === (string)($data['captcha'] ?? ''));
        }

        if (!$validCaptcha) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('FORM__INVALID_CAPTCHA'),
            ]));
        }

        $isValid = $this->User->validRegister($data, $this->Util);

        if ($isValid !== true) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __($isValid),
            ]));
        }

        $eventData = $data;
        $eventData['password'] = $this->Util->password($eventData['password'], $eventData['pseudo']);

        $event = new Event('beforeRegister', $this, ['data' => $eventData]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withStringBody(json_encode($result));
        }

        $data['microsoft_user_id'] = null;
        $data['registered_by_microsoft'] = false;

        $userSession = $this->User->register($data, $this->Util);

        if ($this->Configuration->getKey('confirm_mail_signup')) {
            $confirmCode = substr(md5(uniqid('', true)), 0, 12);

            $emailMsg = __('EMAIL__CONTENT_CONFIRM_MAIL', [
                '{LINK}' => $this->Configuration->getKey('website_url') . '/user/confirm/' . $confirmCode,
                '{IP}' => $this->Util->getIP(),
                '{USERNAME}' => $data['pseudo'],
                '{DATE}' => FrozenTime::now()->i18nFormat('dd/MM/yyyy HH:mm'),
            ]);

            $email = $this->Util
                ->prepareMail(
                    $data['email'],
                    __('EMAIL__TITLE_CONFIRM_MAIL'),
                    $emailMsg,
                )
                ->sendMail();

            if ($email) {
                $user = $this->User->get($userSession);
                $user->set(['confirmed' => $confirmCode]);
                $this->User->save($user);
            }
        }

        if (!$this->Configuration->getKey('confirm_mail_signup_block')) {
            $this->getRequest()->getSession()->write('user', $userSession);

            $event = new Event('onLogin', $this, [
                'user' => $this->User->getAllFromCurrentUser(),
                'register' => true,
            ]);
            $this->getEventManager()->dispatch($event);
            if ($event->isStopped()) {
                $result = $event->getResult();
                if ($result instanceof Response) {
                    return $result;
                }

                return $this->response->withStringBody(json_encode($result));
            }
        }

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('USER__REGISTER_SUCCESS'),
        ]));
    }

    public function ajaxLogin(): Response
    {
        if (!$this->getRequest()->is('post')) {
            throw new BadRequestException();
        }

        $data = $this->getRequest()->getData();

        if (
            empty($data['pseudo']) ||
            empty($data['password'])
        ) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $authTable = $this->fetchTable('UsersTwofactorauth');

        $user_login = $this->User->getAllFromUser($data['pseudo']);

        if (empty($user_login)) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__ERROR_INVALID_CREDENTIALS'),
            ]));
        }

        $infos = $authTable
            ->find()
            ->where([
                'user_id' => $user_login['id'],
                'enabled' => true,
            ])
            ->first();

        $confirmEmailIsNeeded = (
            $this->Configuration->getKey('confirm_mail_signup') &&
            $this->Configuration->getKey('confirm_mail_signup_block')
        );

        $login = $this->User->login(
            $user_login,
            $data,
            $confirmEmailIsNeeded,
            (bool)$this->Configuration->getKey('check_uuid'),
            $this,
        );

        if (!isset($login['status']) || $login['status'] !== true) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __($login, [
                    '{URL_RESEND_EMAIL}' => Router::url(['_name' => 'user_resend_confirmation']),
                ]),
            ]));
        }

        $event = new Event('onLogin', $this, ['user' => $user_login]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withStringBody(json_encode($result));
        }

        if ($infos) {
            $this->getRequest()->getSession()->write('user_id_two_factor_auth', $user_login['id']);

            return $this->response->withStringBody(json_encode([
                'statut' => true,
                'msg' => __('USER__REGISTER_LOGIN'),
                'two-factor-auth' => true,
            ]));
        }

        if (!empty($data['remember_me'])) {
            $cookie = new Cookie(
                'remember_me',
                [
                    'pseudo' => $data['pseudo'],
                    'password' => $this->User->getFromUser(
                        'password',
                        $data['pseudo'],
                    ),
                ],
                new DateTime('+1 weeks'),
            );
            $this->response = $this->getResponse()->withCookie($cookie);
        }

        $this->getRequest()->getSession()->write('user', $login['session']);

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('USER__REGISTER_LOGIN'),
        ]));
    }

    public function confirm(?string $code = null): Response
    {
        $this->disableAutoRender();

        if ($code === null || $code === '') {
            throw new NotFoundException();
        }

        $user = $this->User
            ->find()
            ->where(['confirmed' => $code])
            ->first();

        if ($user === null) {
            throw new NotFoundException();
        }

        $event = new Event('beforeConfirmAccount', $this, ['user_id' => $user['id']]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->redirect(['_name' => 'user_profile']);
        }

        $userEntity = $this->User->get($user['id']);
        $userEntity->set(['confirmed' => date('Y-m-d H:i:s')]);
        $this->User->save($userEntity);

        $userSession = $user['id'];

        $notificationsTable = $this->fetchTable('Notifications');
        $notificationsTable->setToUser(__('USER__CONFIRM_NOTIFICATION'), $user['id']);

        $this->getRequest()->getSession()->write('user', $userSession);

        $event = new Event('onLogin', $this, [
            'user' => $this->User->getAllFromCurrentUser(),
            'confirmAccount' => true,
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }
        }

        return $this->redirect(['_name' => 'user_profile']);
    }

    public function ajax_lostpasswd(): Response
    {
        $this->viewBuilder()->disableAutoLayout();
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->getRequest()->is('ajax')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $email = (string)$this->getRequest()->getData('email');

        if ($email === '') {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__ERROR_EMAIL_NOT_VALID'),
            ]));
        }

        $userTable = $this->fetchTable('User');
        $user = $userTable
            ->find()
            ->where(['email' => $email])
            ->first();

        if ($user === null) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__ERROR_NOT_FOUND'),
            ]));
        }

        if (!empty($user['registered_by_microsoft'])) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__AUTH_MICROSOFT_CANNOT_RESET_PASSWORD'),
            ]));
        }

        $lostPasswordTable = $this->fetchTable('Lostpasswords');
        $key = substr(md5((string)rand() . date('sihYdm')), 0, 10);

        $subject = __('USER__PASSWORD_RESET_LINK');
        $message = __('USER__PASSWORD_RESET_EMAIL_CONTENT', [
            '{EMAIL}' => $email,
            '{PSEUDO}' => $user['pseudo'],
            '{LINK}' => $this->Configuration->getKey('website_url') . '/?resetpasswd_' . $key,
        ]);

        $event = new Event('beforeSendResetPassMail', $this, [
            'user_id' => $user['id'],
            'key' => $key,
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withStringBody(json_encode($result));
        }

        $mailOk = $this->Util->prepareMail($email, $subject, $message)->sendMail();

        if ($mailOk) {
            $lostPass = $lostPasswordTable->newEntity([
                'email' => $email,
                'key' => $key,
            ]);
            $lostPasswordTable->save($lostPass);

            return $this->response->withStringBody(json_encode([
                'statut' => true,
                'msg' => __('USER__PASSWORD_FORGOT_EMAIL_SUCCESS'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'statut' => false,
            'msg' => __('ERROR__INTERNAL_ERROR'),
        ]));
    }

    public function ajax_resetpasswd(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->getRequest()->is('ajax')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $data = $this->getRequest()->getData();

        if (
            empty($data['password']) ||
            empty($data['password2']) ||
            empty($data['email']) ||
            empty($data['key'])
        ) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $reset = $this->User->resetPass($data, $this);

        if (isset($reset['status']) && $reset['status'] === true) {
            $this->getRequest()->getSession()->write('user', $reset['session']);
            $this->History->set('RESET_PASSWORD', 'user');

            return $this->response->withStringBody(json_encode([
                'statut' => true,
                'msg' => __('USER__PASSWORD_RESET_SUCCESS'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'statut' => false,
            'msg' => __($reset),
        ]));
    }

    public function logout(): Response
    {
        $this->disableAutoRender();

        $event = new Event('onLogout', $this, [
            'session' => $this->getRequest()->getSession()->read('user'),
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }
        }

        if ($this->getRequest()->getCookie('microsoft_user_id')) {
            $this->getRequest()->getCookieCollection()->remove('microsoft_user_id');
        }

        if ($this->getRequest()->getCookie('remember_me')) {
            $this->getRequest()->getCookieCollection()->remove('remember_me');
        }

        $this->getRequest()->getSession()->delete('user');

        return $this->redirect($this->referer());
    }

    public function uploadSkin(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected || !$this->API->can_skin()) {
            throw new ForbiddenException();
        }

        if (!$this->getRequest()->is('post')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $username = $this->User->getKey('pseudo');

        $apiConfigTable = $this->fetchTable('ApiConfigurations');
        $apiConfig = $apiConfigTable->find()->first();

        if ($apiConfig === null) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__INTERNAL_ERROR'),
            ]));
        }

        $useSkinRestorer = $apiConfig['use_skin_restorer'];
        $serverSkinRestorerID = $apiConfig['skin_restorer_server_id'];

        if ($useSkinRestorer && !$this->Server->userIsConnected($username, $serverSkinRestorerID)) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('API__SKIN_RESTORER_NOT_CONNECTED'),
            ]));
        }

        $skin_max_size = 10000000;

        $target_config = $apiConfig['skin_filename'];
        $filename = substr($target_config, (int)strrpos($target_config, '/') + 1);
        $filename = str_replace('{PLAYER}', $username, $filename);
        $filename = str_replace('php', '', $filename);
        $filename = str_replace('.', '', $filename);
        $filename .= '.png';

        $target = substr($target_config, 0, (int)strrpos($target_config, '/') + 1);
        $target = WWW_ROOT . '/' . $target;

        $width_max = $apiConfig['skin_width'];
        $height_max = $apiConfig['skin_height'];

        $isValidImg = $this->Util->isValidImage($this->getRequest(), ['png'], $width_max, $height_max, $skin_max_size);
        if (!$isValidImg['status']) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => $isValidImg['msg'],
            ]));
        }

        if (!$this->Util->uploadImage($this->getRequest(), $target . $filename)) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('FORM__ERROR_WHEN_UPLOAD'),
            ]));
        }

        $skinURL = Router::url(
            str_replace('{PLAYER}', $username, $apiConfig['skin_filename']) . '.png',
            true,
        );

        $skinRestorerCommand = str_replace(
            ['{PLAYER}', '{URL}'],
            [$username, $skinURL],
            'skin set {PLAYER} {URL}',
        );
        $this->Server->commands($skinRestorerCommand, $serverSkinRestorerID);

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('API__UPLOAD_SKIN_SUCCESS'),
        ]));
    }

    public function uploadCape(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected || !$this->API->can_cape()) {
            throw new ForbiddenException();
        }

        if (!$this->getRequest()->is('post')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $cape_max_size = 10000000;

        $apiConfigTable = $this->fetchTable('ApiConfigurations');
        $apiConfig = $apiConfigTable->find()->first();

        if ($apiConfig === null) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__INTERNAL_ERROR'),
            ]));
        }

        $target_config = $apiConfig['cape_filename'];
        $filename = substr($target_config, (int)strrpos($target_config, '/') + 1);
        $filename = str_replace('{PLAYER}', $this->User->getKey('pseudo'), $filename);
        $filename = str_replace('php', '', $filename);
        $filename = str_replace('.', '', $filename);
        $filename .= '.png';

        $target = substr($target_config, 0, (int)strrpos($target_config, '/') + 1);
        $target = WWW_ROOT . '/' . $target;

        $width_max = $apiConfig['cape_width'];
        $height_max = $apiConfig['cape_height'];

        $isValidImg = $this->Util->isValidImage($this->getRequest(), ['png'], $width_max, $height_max, $cape_max_size);
        if (!$isValidImg['status']) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => $isValidImg['msg'],
            ]));
        }

        if (!$this->Util->uploadImage($this->getRequest(), $target . $filename)) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('FORM__ERROR_WHEN_UPLOAD'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('API__UPLOAD_CAPE_SUCCESS'),
        ]));
    }

    public function profile(): Response
    {
        if (!$this->isConnected) {
            return $this->redirect(['_name' => 'home']);
        }

        $authTable = $this->fetchTable('UsersTwofactorauth');
        $infos = $authTable
            ->find()
            ->where([
                'user_id' => $this->User->getKey('id'),
                'enabled' => true,
            ])
            ->first();

        $twoFactorAuthStatus = !empty($infos);
        $this->set('twoFactorAuthStatus', $twoFactorAuthStatus);

        $this->set('title_for_layout', $this->User->getKey('pseudo'));

        $this->viewBuilder()->setLayout($this->Configuration->getKey('layout'));

        if ($this->EyPlugin->isInstalled('eywek.shop')) {
            $itemsHistoryTable = $this->fetchTable('Shop.ItemsBuyHistory');
            $histories = $itemsHistoryTable
                ->find()
                ->where(['user_id' => $this->User->getKey('id')])
                ->order(['ItemsBuyHistory.created' => 'DESC'])
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
        $custom_ranks = $rankTable->find()->all();
        foreach ($custom_ranks as $value) {
            $available_ranks[$value['rank_id']] = $value['name'];
        }
        $this->set(compact('available_ranks'));

        $this->set('can_cape', $this->API->can_cape());
        $this->set('can_skin', $this->API->can_skin());

        $apiConfigTable = $this->fetchTable('ApiConfigurations');
        $configAPI = $apiConfigTable->find()->first();

        if ($configAPI !== null) {
            $skin_width_max = $configAPI['skin_width'];
            $skin_height_max = $configAPI['skin_height'];
            $cape_width_max = $configAPI['cape_width'];
            $cape_height_max = $configAPI['cape_height'];

            $this->set(compact('skin_width_max', 'skin_height_max', 'cape_width_max', 'cape_height_max'));
        }

        $confirmed = $this->User->getKey('confirmed');
        if (
            $this->Configuration->getKey('confirm_mail_signup') &&
            !empty($confirmed) &&
            date('Y-m-d H:i:s', strtotime($confirmed)) !== $confirmed
        ) {
            $this->Flash->warning(__('USER__MSG_NOT_CONFIRMED_EMAIL', [
                '{URL_RESEND_EMAIL}' => Router::url(['_name' => 'user_resend_confirmation']),
            ]));
        }

        $connected_by_microsoft = false;
        $microsoft_user_id = $this->getRequest()->getCookie('microsoft_user_id');
        if ($microsoft_user_id !== null) {
            $connected_by_microsoft = true;
        }
        $this->set(compact('connected_by_microsoft'));

        $this->viewBuilder()
            ->setTemplatePath('User')
            ->setTemplate('profile');

        return $this->render();
    }

    public function resend_confirmation(): Response
    {
        if (
            !$this->isConnected &&
            !$this->getRequest()->getSession()->check('email.confirm.user.id')
        ) {
            throw new ForbiddenException();
        }

        if ($this->isConnected) {
            $user = $this->User->getAllFromCurrentUser();
        } else {
            $userId = $this->getRequest()->getSession()->read('email.confirm.user.id');
            $user = $userId !== null ? $this->User->get((int)$userId) : null;
        }

        $this->getRequest()->getSession()->delete('email.confirm.user.id');

        if (!$user || empty($user)) {
            throw new NotFoundException();
        }

        $confirmed = $user['confirmed'];

        if (
            !$this->Configuration->getKey('confirm_mail_signup') ||
            empty($confirmed) ||
            date('Y-m-d H:i:s', strtotime($confirmed)) === $confirmed
        ) {
            throw new NotFoundException();
        }

        $emailMsg = __('EMAIL__CONTENT_CONFIRM_MAIL', [
            '{LINK}' => $this->Configuration->getKey('website_url') . '/user/confirm/' . $confirmed,
            '{IP}' => $this->Util->getIP(),
            '{USERNAME}' => $user['pseudo'],
            '{DATE}' => FrozenTime::now()->i18nFormat('dd/MM/yyyy HH:mm'),
        ]);

        $email = $this->Util
            ->prepareMail(
                $user['email'],
                __('EMAIL__TITLE_CONFIRM_MAIL'),
                $emailMsg,
            )
            ->sendMail();

        if ($email) {
            $this->Flash->success(__('USER__CONFIRM_EMAIL_RESEND_SUCCESS'));
        } else {
            $this->Flash->error(__('USER__CONFIRM_EMAIL_RESEND_FAIL'));
        }

        if ($this->isConnected) {
            return $this->redirect(['_name' => 'user_profile']);
        }

        return $this->redirect(['_name' => 'home']);
    }

    public function changePw(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__ERROR_MUST_BE_LOGGED'),
            ]));
        }

        if (!$this->getRequest()->is('ajax')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $data = $this->getRequest()->getData();

        if (
            empty($data['password']) ||
            empty($data['password_confirmation'])
        ) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $password = $this->Util->password(
            $data['password'],
            $this->User->getKey('pseudo'),
        );

        $password_confirmation = $this->Util->password(
            $data['password_confirmation'],
            $this->User->getKey('pseudo'),
            $password,
        );

        if ($password !== $password_confirmation) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__ERROR_PASSWORDS_NOT_SAME'),
            ]));
        }

        $event = new Event('beforeUpdatePassword', $this, [
            'user' => $this->User->getAllFromCurrentUser(),
            'new_password' => $password,
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withStringBody(json_encode($result));
        }

        $this->User->setKey('password', $password);
        $this->User->setKey('password_hash', $this->Util->getPasswordHashType());

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('USER__PASSWORD_UPDATE_SUCCESS'),
        ]));
    }

    public function changeEmail(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected || !$this->Permissions->can('EDIT_HIS_EMAIL')) {
            throw new ForbiddenException();
        }

        if (!$this->getRequest()->is('ajax')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $data = $this->getRequest()->getData();

        if (
            empty($data['email']) ||
            empty($data['email_confirmation'])
        ) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        if ($data['email'] !== $data['email_confirmation']) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__ERROR_EMAIL_NOT_SAME'),
            ]));
        }

        if (!filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__ERROR_EMAIL_NOT_VALID'),
            ]));
        }

        $event = new Event('beforeUpdateEmail', $this, [
            'user' => $this->User->getAllFromCurrentUser(),
            'new_email' => $data['email_confirmation'],
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withStringBody(json_encode($result));
        }

        $this->User->setKey('email', htmlentities((string)$data['email']));

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('USER__EMAIL_UPDATE_SUCCESS'),
        ]));
    }
}
