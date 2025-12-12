<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\UserAuthService;
use Cake\Event\Event;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;
use Cake\Routing\Router;
use DateTime;
use RobThree\Auth\TwoFactorAuth;

class AuthController extends AppController
{
    private UserAuthService $userAuth;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Captcha');
        $this->loadComponent('Util');

        $this->userAuth = new UserAuthService();
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

    public function register(): Response
    {
        $this->disableAutoRender();

        if (!$this->getRequest()->is('post')) {
            return $this->json(['statut' => false, 'msg' => __('ERROR__BAD_REQUEST')], 400);
        }

        $data = (array)$this->getRequest()->getData();

        $conditionsChecked = !empty($data['condition']) || !(bool)$this->Configuration->getKey('condition');

        if (
            empty($data['pseudo']) ||
            empty($data['password']) ||
            empty($data['password_confirmation']) ||
            empty($data['email']) ||
            !$conditionsChecked
        ) {
            return $this->json(['statut' => false, 'msg' => __('ERROR__FILL_ALL_FIELDS')], 400);
        }

        if ((bool)$this->Configuration->getKey('check_uuid')) {
            $pseudo = (string)$data['pseudo'];
            $pseudoToUUID = @file_get_contents('https://api.mojang.com/users/profiles/minecraft/' . rawurlencode($pseudo));
            if (!$pseudoToUUID) {
                return $this->json(['statut' => false, 'msg' => __('USER__ERROR_UUID')], 400);
            }

            $decoded = json_decode($pseudoToUUID, true);
            if (!is_array($decoded) || empty($decoded['id'])) {
                return $this->json(['statut' => false, 'msg' => __('USER__ERROR_UUID')], 400);
            }

            $data['uuid'] = (string)$decoded['id'];
        }

        $captchaType = (int)($this->Configuration->getKey('captcha_type') ?? 0);
        if ($captchaType === 2 || $captchaType === 3) {
            $validCaptcha = $this->Util->isValidReCaptcha(
                (string)($data['recaptcha'] ?? ''),
                $this->Util->getIP(),
                (string)$this->Configuration->getKey('captcha_secret'),
                $captchaType
            );
        } else {
            $captcha = $this->getRequest()->getSession()->read('captcha_code');
            $validCaptcha = (!empty($captcha) && (string)$captcha === (string)($data['captcha'] ?? ''));
        }

        if (!$validCaptcha) {
            return $this->json(['statut' => false, 'msg' => __('FORM__INVALID_CAPTCHA')], 400);
        }

        $isValid = $this->User->validRegister($data, $this->Util);
        if ($isValid !== true) {
            return $this->json(['statut' => false, 'msg' => __((string)$isValid)], 400);
        }

        $eventData = $data;
        $eventData['password'] = $this->userAuth->hashPassword((string)$eventData['password']);

        $event = new Event('beforeRegister', $this, ['data' => $eventData]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->json((array)$result, 400);
        }

        $data['microsoft_user_id'] = null;
        $data['registered_by_microsoft'] = false;
        $data['rank'] = 0;

        $userId = $this->userAuth->createUser($data, $this->Util->getIP());

        if ((bool)$this->Configuration->getKey('confirm_mail_signup')) {
            $confirmCode = substr(md5(uniqid('', true)), 0, 12);

            $emailMsg = __('EMAIL__CONTENT_CONFIRM_MAIL', [
                '{LINK}' => (string)$this->Configuration->getKey('website_url') . '/auth/confirm/' . $confirmCode,
                '{IP}' => $this->Util->getIP(),
                '{USERNAME}' => (string)$data['pseudo'],
                '{DATE}' => FrozenTime::now()->i18nFormat('dd/MM/yyyy HH:mm'),
            ]);

            $sent = $this->Util
                ->prepareMail((string)$data['email'], __('EMAIL__TITLE_CONFIRM_MAIL'), $emailMsg)
                ->sendMail();

            if ($sent) {
                $userEntity = $this->User->get((int)$userId);
                $userEntity->set(['confirmed' => $confirmCode]);
                $this->User->save($userEntity);
            }
        }

        if (!(bool)$this->Configuration->getKey('confirm_mail_signup_block')) {
            $this->getRequest()->getSession()->write('user', (int)$userId);
            $this->clearAuthContext();

            $event = new Event('onLogin', $this, [
                'user' => $this->Auth->identity(),
                'register' => true,
            ]);
            $this->getEventManager()->dispatch($event);
            if ($event->isStopped()) {
                $result = $event->getResult();
                if ($result instanceof Response) {
                    return $result;
                }

                return $this->json((array)$result, 400);
            }
        }

        return $this->json(['statut' => true, 'msg' => __('USER__REGISTER_SUCCESS')]);
    }

    public function login(): Response
    {
        $this->disableAutoRender();

        if (!$this->getRequest()->is('post')) {
            throw new BadRequestException();
        }

        $data = (array)$this->getRequest()->getData();

        if (empty($data['pseudo']) || empty($data['password'])) {
            return $this->json(['statut' => false, 'msg' => __('ERROR__FILL_ALL_FIELDS')], 400);
        }

        $user = $this->User->find()
            ->where(['pseudo' => (string)$data['pseudo']])
            ->first();

        if ($user === null) {
            return $this->json(['statut' => false, 'msg' => __('USER__ERROR_INVALID_CREDENTIALS')], 400);
        }

        $confirmEmailIsNeeded = (
            (bool)$this->Configuration->getKey('confirm_mail_signup') &&
            (bool)$this->Configuration->getKey('confirm_mail_signup_block')
        );

        $login = $this->userAuth->attemptLogin(
            $user,
            (string)($data['password'] ?? ''),
            $this->Util->getIP(),
            $confirmEmailIsNeeded,
            (bool)$this->Configuration->getKey('check_uuid')
        );

        if (is_array($login) && ($login['status'] ?? null) === false && ($login['code'] ?? '') === 'USER__MSG_NOT_CONFIRMED_EMAIL') {
            $userId = (int)($login['email_confirm_user_id'] ?? 0);
            if ($userId > 0) {
                $this->getRequest()->getSession()->write('email.confirm.user.id', $userId);
            }

            return $this->json([
                'statut' => false,
                'msg' => __('USER__MSG_NOT_CONFIRMED_EMAIL', [
                    '{URL_RESEND_EMAIL}' => Router::url(['_name' => 'auth_resend_confirmation']),
                ]),
            ], 400);
        }

        if (!is_array($login) || ($login['status'] ?? null) !== true) {
            return $this->json(['statut' => false, 'msg' => __((string)$login)], 400);
        }

        $authTable = $this->fetchTable('UsersTwofactorauth');
        $infos = $authTable->find()
            ->where([
                'user_id' => (int)($user->get('id') ?? 0),
                'enabled' => true,
            ])
            ->first();

        $event = new Event('onLogin', $this, ['user' => $user]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->json((array)$result, 400);
        }

        if ($infos) {
            $this->getRequest()->getSession()->write('user_id_two_factor_auth', (int)($user->get('id') ?? 0));

            return $this->json([
                'statut' => true,
                'msg' => __('USER__REGISTER_LOGIN'),
                'two-factor-auth' => true,
            ]);
        }

        if (!empty($data['remember_me'])) {
            $cookie = new Cookie(
                'remember_me',
                [
                    'pseudo' => (string)($data['pseudo'] ?? ''),
                    'password' => (string)$this->User->getFromUser('password', (string)($data['pseudo'] ?? '')),
                ],
                new DateTime('+1 week')
            );

            $this->response = $this->response
                ->withCookie($cookie)
                ->withHeader('Cache-Control', 'no-store');
        }

        $this->getRequest()->getSession()->write('user', (int)($login['session'] ?? 0));
        $this->clearAuthContext();

        return $this->json(['statut' => true, 'msg' => __('USER__REGISTER_LOGIN')]);
    }

    public function twoFactorValidate(): Response
    {
        $this->disableAutoRender();

        if (!$this->getRequest()->is('post')) {
            throw new NotFoundException();
        }

        $pendingUserId = $this->getRequest()->getSession()->read('user_id_two_factor_auth');
        if (!$pendingUserId) {
            return $this->json(['statut' => false, 'msg' => __('USER__LOGIN_INFOS_NOT_FOUND')], 400);
        }

        $code = (string)$this->getRequest()->getData('code');
        if ($code === '') {
            return $this->json(['statut' => false, 'msg' => __('USER__LOGIN_CODE_EMPTY')], 400);
        }

        $user = $this->User->find()->where(['id' => (int)$pendingUserId])->first();
        if (!$user) {
            return $this->json(['statut' => false, 'msg' => __('USER__LOGIN_INFOS_NOT_FOUND')], 400);
        }

        $authTable = $this->fetchTable('UsersTwofactorauth');
        $infos = $authTable->find()->where(['user_id' => (int)($user->get('id') ?? 0)])->first();

        if ($infos === null || !(bool)($infos['enabled'] ?? false)) {
            return $this->json(['statut' => false, 'msg' => __('USER__LOGIN_INFOS_NOT_FOUND')], 400);
        }

        $ga = new TwoFactorAuth();
        if (!$ga->verifyCode((string)$infos['secret'], $code, 2)) {
            return $this->json(['statut' => false, 'msg' => __('USER__LOGIN_CODE_INVALID')], 400);
        }

        $this->getRequest()->getSession()->delete('user_id_two_factor_auth');

        if (!empty($this->getRequest()->getData('remember_me'))) {
            $cookie = new Cookie(
                'remember_me',
                [
                    'pseudo' => (string)$user->get('pseudo'),
                    'password' => (string)$this->User->getFromUser('password', (string)$user->get('pseudo')),
                ],
                new DateTime('+1 week')
            );

            $this->response = $this->response->withCookie($cookie);
        }

        $this->getRequest()->getSession()->write('user', (int)$user->get('id'));
        $this->clearAuthContext();

        $event = new Event('onLogin', $this, [
            'user' => $this->User->getAllFromUser((string)$user->get('pseudo')),
            'twoFactor' => true,
        ]);
        $this->getEventManager()->dispatch($event);

        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }
        }

        return $this->json(['statut' => true, 'msg' => __('USER__REGISTER_LOGIN')]);
    }

    public function confirm(?string $code = null): Response
    {
        $this->disableAutoRender();

        if ($code === null || $code === '') {
            throw new NotFoundException();
        }

        $user = $this->User->find()->where(['confirmed' => $code])->first();
        if ($user === null) {
            throw new NotFoundException();
        }

        $event = new Event('beforeConfirmAccount', $this, ['user_id' => (int)$user->get('id')]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->redirect(['_name' => 'user_profile']);
        }

        $userEntity = $this->User->get((int)$user->get('id'));
        $userEntity->set(['confirmed' => date('Y-m-d H:i:s')]);
        $this->User->save($userEntity);

        $this->fetchTable('Notifications')->setToUser(__('USER__CONFIRM_NOTIFICATION'), (int)$user->get('id'));

        $this->getRequest()->getSession()->write('user', (int)$user->get('id'));
        $this->clearAuthContext();

        $event = new Event('onLogin', $this, [
            'user' => $this->Auth->identity(),
            'confirmAccount' => true,
        ]);
        $this->getEventManager()->dispatch($event);

        return $this->redirect(['_name' => 'user_profile']);
    }

    public function resendConfirmation(): Response
    {
        $session = $this->getRequest()->getSession();

        if (!$this->Auth->isConnected() && !$session->check('email.confirm.user.id')) {
            throw new ForbiddenException();
        }

        if ($this->Auth->isConnected()) {
            $user = $this->Auth->identity();
        } else {
            $userId = $session->read('email.confirm.user.id');
            $user = $userId !== null ? $this->User->get((int)$userId) : null;
        }

        $session->delete('email.confirm.user.id');

        if (!$user) {
            throw new NotFoundException();
        }

        $confirmed = $user['confirmed'] ?? null;

        if (
            !(bool)$this->Configuration->getKey('confirm_mail_signup') ||
            empty($confirmed) ||
            date('Y-m-d H:i:s', strtotime((string)$confirmed)) === (string)$confirmed
        ) {
            throw new NotFoundException();
        }

        $emailMsg = __('EMAIL__CONTENT_CONFIRM_MAIL', [
            '{LINK}' => (string)$this->Configuration->getKey('website_url') . '/auth/confirm/' . $confirmed,
            '{IP}' => $this->Util->getIP(),
            '{USERNAME}' => $user['pseudo'],
            '{DATE}' => FrozenTime::now()->i18nFormat('dd/MM/yyyy HH:mm'),
        ]);

        $sent = $this->Util
            ->prepareMail((string)$user['email'], __('EMAIL__TITLE_CONFIRM_MAIL'), $emailMsg)
            ->sendMail();

        if ($sent) {
            $this->Flash->success(__('USER__CONFIRM_EMAIL_RESEND_SUCCESS'));
        } else {
            $this->Flash->error(__('USER__CONFIRM_EMAIL_RESEND_FAIL'));
        }

        return $this->redirect(['_name' => $this->Auth->isConnected() ? 'user_profile' : 'home']);
    }

    public function lostPassword(): Response
    {
        $this->viewBuilder()->disableAutoLayout();
        $this->disableAutoRender();

        if (!$this->getRequest()->is('ajax')) {
            return $this->json(['statut' => false, 'msg' => __('ERROR__BAD_REQUEST')], 400);
        }

        $email = (string)$this->getRequest()->getData('email', '');
        if ($email === '') {
            return $this->json(['statut' => false, 'msg' => __('ERROR__FILL_ALL_FIELDS')], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return $this->json(['statut' => false, 'msg' => __('USER__ERROR_EMAIL_NOT_VALID')], 400);
        }

        $user = $this->User->find()->where(['email' => $email])->first();
        if ($user === null) {
            return $this->json(['statut' => false, 'msg' => __('USER__ERROR_NOT_FOUND')], 404);
        }

        if (!empty($user['registered_by_microsoft'])) {
            return $this->json(['statut' => false, 'msg' => __('USER__AUTH_MICROSOFT_CANNOT_RESET_PASSWORD')], 400);
        }

        $lostPasswordTable = $this->fetchTable('Lostpasswords');
        $key = substr(md5((string)rand() . date('sihYdm')), 0, 10);

        $subject = __('USER__PASSWORD_RESET_LINK');
        $message = __('USER__PASSWORD_RESET_EMAIL_CONTENT', [
            '{EMAIL}' => $email,
            '{PSEUDO}' => $user['pseudo'],
            '{LINK}' => (string)$this->Configuration->getKey('website_url') . '/?resetpasswd_' . $key,
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

            return $this->json((array)$result, 400);
        }

        $mailOk = $this->Util->prepareMail((string)$email, (string)$subject, (string)$message)->sendMail();

        if ($mailOk) {
            $lostPass = $lostPasswordTable->newEntity(['email' => $email, 'key' => $key]);
            $lostPasswordTable->save($lostPass);

            return $this->json(['statut' => true, 'msg' => __('USER__PASSWORD_FORGOT_EMAIL_SUCCESS')]);
        }

        return $this->json(['statut' => false, 'msg' => __('ERROR__INTERNAL_ERROR')], 500);
    }

    public function resetPassword(): Response
    {
        $this->disableAutoRender();

        if (!$this->getRequest()->is('ajax')) {
            return $this->json(['statut' => false, 'msg' => __('ERROR__BAD_REQUEST')], 400);
        }

        $data = (array)$this->getRequest()->getData();

        if (empty($data['password']) || empty($data['password2']) || empty($data['email']) || empty($data['key'])) {
            return $this->json(['statut' => false, 'msg' => __('ERROR__FILL_ALL_FIELDS')], 400);
        }

        $event = new Event('beforeResetPassword', $this, ['user_id' => null, 'new_password' => null]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->json((array)$result, 400);
        }

        $reset = $this->userAuth->resetPassword($data);

        if (is_array($reset) && ($reset['status'] ?? null) === true) {
            $this->getRequest()->getSession()->write('user', (int)$reset['session']);
            $this->clearAuthContext();

            $this->fetchTable('Histories')->set('RESET_PASSWORD', 'user');

            return $this->json(['statut' => true, 'msg' => __('USER__PASSWORD_RESET_SUCCESS')]);
        }

        return $this->json(['statut' => false, 'msg' => __((string)$reset)], 400);
    }

    public function twoFactorGenerateSecret(): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected()) {
            throw new ForbiddenException();
        }

        $ga = new TwoFactorAuth();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeImageAsDataUri((string)$this->Auth->username(), $secret);

        $this->getRequest()->getSession()->write('two-factor-auth-secret', $secret);

        return $this->json(['qrcode_url' => $qrCodeUrl, 'secret' => $secret]);
    }

    public function twoFactorEnable(): Response
    {
        $this->disableAutoRender();

        if (!$this->getRequest()->is('post')) {
            throw new NotFoundException();
        }

        if (!$this->Auth->isConnected()) {
            throw new ForbiddenException();
        }

        $code = (string)$this->getRequest()->getData('code');
        if ($code === '') {
            return $this->json(['statut' => false, 'msg' => __('USER__LOGIN_CODE_EMPTY')], 400);
        }

        $secret = $this->getRequest()->getSession()->read('two-factor-auth-secret');
        if (!$secret) {
            return $this->json(['statut' => false, 'msg' => __('USER__SECRET_NOT_FOUND')], 400);
        }

        $ga = new TwoFactorAuth();
        if (!$ga->verifyCode((string)$secret, $code, 2)) {
            return $this->json(['statut' => false, 'msg' => __('USER__LOGIN_CODE_INVALID')], 400);
        }

        $this->getRequest()->getSession()->delete('two-factor-auth-secret');

        $authTable = $this->fetchTable('UsersTwofactorauth');

        $userId = $this->Auth->id();
        if ($userId === null) {
            throw new ForbiddenException();
        }

        $infos = $authTable->find()->where(['user_id' => $userId])->first();
        $auth = $infos ? $authTable->get((int)$infos['id']) : $authTable->newEmptyEntity();

        $auth->set(['secret' => (string)$secret, 'enabled' => true, 'user_id' => $userId]);
        $authTable->save($auth);

        return $this->json(['statut' => true, 'msg' => __('USER__SUCCESS_ENABLED_TWO_FACTOR_AUTH')]);
    }

    public function twoFactorDisable(): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected()) {
            throw new ForbiddenException();
        }

        $authTable = $this->fetchTable('UsersTwofactorauth');

        $userId = $this->Auth->id();
        if ($userId === null) {
            throw new ForbiddenException();
        }

        $infos = $authTable->find()->where(['user_id' => $userId])->first();
        if ($infos) {
            $auth = $authTable->get((int)$infos['id']);
            $auth->set(['enabled' => false]);
            $authTable->save($auth);
        }

        return $this->json(['statut' => true, 'msg' => __('USER__SUCCESS_DISABLED_TWO_FACTOR_AUTH')]);
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

        $this->getRequest()->getSession()->delete('user');
        $this->getRequest()->getSession()->delete('user_id_two_factor_auth');
        $this->clearAuthContext();

        $response = $this->redirect($this->referer());

        $response = $response
            ->withExpiredCookie(new Cookie('microsoft_user_id'))
            ->withExpiredCookie(new Cookie('remember_me'));

        return $response->withHeader('Cache-Control', 'no-store');
    }
}
