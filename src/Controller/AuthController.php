<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\UsersTable;
use App\Service\OtpService;
use App\Service\UserAuthService;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;

class AuthController extends AppController
{
    private UserAuthService $userAuth;
    private UsersTable $User;
    private OtpService $otp;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Captcha');
        $this->loadComponent('Util');

        $this->userAuth = new UserAuthService();
        $this->User = $this->fetchTable('Users');
        $this->otp = new OtpService();
    }

    private function json(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody((string)json_encode($payload, JSON_UNESCAPED_UNICODE));
    }

    private function clearAuthContext(): void
    {
        $this->getRequest()->getSession()->delete('auth_context');
    }

    public function register(): Response
    {
        $this->disableAutoRender();

        $request = $this->getRequest();

        if (!$request->is('post')) {
            throw new BadRequestException();
        }

        $data = (array)$request->getData();
        $ip = $request->clientIp();

        $conditionsRequired = (bool)$this->config->get('condition');
        if (
            empty($data['username']) ||
            empty($data['password']) ||
            empty($data['password_confirmation']) ||
            empty($data['email']) ||
            ($conditionsRequired && empty($data['condition']))
        ) {
            return $this->json(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')], 400);
        }

        if ((string)$data['password'] !== (string)$data['password_confirmation']) {
            return $this->json(['status' => false, 'messages' => __('USER__ERROR_PASSWORDS_NOT_SAME')], 400);
        }

        if ($this->config->get('check_uuid')) {
            $username = (string)$data['username'];
            $res = @file_get_contents('https://api.mojang.com/users/profiles/minecraft/' . rawurlencode($username));
            if (!$res) {
                return $this->json(['status' => false, 'messages' => __('USER__ERROR_UUID')], 400);
            }

            $decoded = json_decode($res, true);
            if (!is_array($decoded) || empty($decoded['id'])) {
                return $this->json(['status' => false, 'messages' => __('USER__ERROR_UUID')], 400);
            }

            $data['uuid'] = (string)$decoded['id'];
        }

        $captchaType = (int)$this->config->get('captcha_type');
        if ($captchaType === 2 || $captchaType === 3) {
            $validCaptcha = $this->Captcha->isValidReCaptcha(
                (string)($data['recaptcha'] ?? ''),
                $ip,
                (string)$this->config->get('captcha_secret'),
                $captchaType
            );
        } else {
            $captcha = $request->getSession()->read('captcha_code');
            $validCaptcha = (string)$captcha !== '' && (string)$captcha === (string)($data['captcha'] ?? '');
        }

        if (!$validCaptcha) {
            return $this->json(['status' => false, 'messages' => __('FORM__INVALID_CAPTCHA')], 400);
        }

        $userId = $this->userAuth->createUser($data, $ip);

        if ($this->config->get('confirm_mail_signup')) {
            $confirmCode = substr(md5(uniqid('', true)), 0, 12);

            $mail = __('EMAIL__CONTENT_CONFIRM_MAIL', [
                'LINK' => $this->config->get('website_url') . '/auth/confirm/' . $confirmCode,
                'IP' => $ip,
                'USERNAME' => (string)$data['username'],
                'DATE' => FrozenTime::now()->i18nFormat('dd/MM/yyyy HH:mm'),
            ]);

            if ($this->Util->prepareMail((string)$data['email'], __('EMAIL__TITLE_CONFIRM_MAIL'), $mail)->sendMail()) {
                $user = $this->User->get($userId);
                $user->set(['confirmed' => $confirmCode]);
                $this->User->save($user);
            }
        }

        if (!$this->config->get('confirm_mail_signup_block')) {
            $request->getSession()->write('user', $userId);
            $this->clearAuthContext();
        }

        return $this->json(['status' => true, 'messages' => __('USER__REGISTER_SUCCESS')]);
    }

    public function login(): Response
    {
        $this->disableAutoRender();

        $request = $this->getRequest();

        if (!$request->is('post')) {
            throw new BadRequestException();
        }

        $data = (array)$request->getData();
        $ip = $request->clientIp();

        if (empty($data['username']) || empty($data['password'])) {
            return $this->json(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')], 400);
        }

        $user = $this->User->find()->where(['username' => (string)$data['username']])->first();
        if (!$user) {
            return $this->json(['status' => false, 'messages' => __('USER__ERROR_INVALID_CREDENTIALS')], 400);
        }

        $login = $this->userAuth->attemptLogin(
            $user,
            (string)$data['password'],
            $ip,
            (bool)$this->config->get('confirm_mail_signup_block'),
            (bool)$this->config->get('check_uuid')
        );

        if (!is_array($login) || empty($login['status'])) {
            return $this->json(['status' => false, 'messages' => __((string)$login)], 400);
        }

        $userId = (int)($login['session'] ?? 0);
        if ($userId <= 0) {
            return $this->json(['status' => false, 'messages' => __('USER__ERROR_INVALID_CREDENTIALS')], 400);
        }

        $Twofa = $this->fetchTable('UsersTwofactorauth');
        $twofaRow = $Twofa->find()
            ->where(['user_id' => $userId, 'enabled' => 1])
            ->first();

        if ($twofaRow !== null && (string)($twofaRow->get('secret') ?? '') !== '') {
            $session = $request->getSession();
            $session->write('user_id_two_factor_auth', $userId);
            $session->delete('user');

            return $this->json([
                'status' => true,
                'two-factor-auth' => true,
                'messages' => __('USER__LOGIN_CODE'),
            ]);
        }

        $request->getSession()->write('user', $userId);
        $request->getSession()->delete('user_id_two_factor_auth');
        $this->clearAuthContext();

        return $this->json(['status' => true, 'messages' => __('USER__REGISTER_LOGIN')]);
    }

    public function twoFactorValidate(): Response
    {
        $this->disableAutoRender();

        if (!$this->getRequest()->is('ajax') || !$this->getRequest()->is('post')) {
            return $this->json(['status' => false, 'messages' => __('ERROR__BAD_REQUEST')], 400);
        }

        $session = $this->getRequest()->getSession();
        $pendingUserId = (int)$session->read('user_id_two_factor_auth');

        if ($pendingUserId <= 0) {
            return $this->json(['status' => false, 'messages' => __('ERROR__BAD_REQUEST')], 400);
        }

        $code = trim((string)$this->getRequest()->getData('code', ''));
        if ($code === '' || !preg_match('/^\d{6}$/', $code)) {
            return $this->json(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')], 400);
        }

        $Twofa = $this->fetchTable('UsersTwofactorauth');
        $row = $Twofa->find()
            ->where(['user_id' => $pendingUserId, 'enabled' => 1])
            ->first();

        $secret = $row ? (string)($row->get('secret') ?? '') : '';
        if ($secret === '') {
            $session->delete('user_id_two_factor_auth');
            return $this->json(['status' => false, 'messages' => __('ERROR__BAD_REQUEST')], 400);
        }

        if (!$this->otp->verifyCode($secret, $code, 1)) {
            return $this->json(['status' => false, 'messages' => __('USER__LOGIN_CODE_INVALID')], 400);
        }

        $session->write('user', $pendingUserId);
        $session->delete('user_id_two_factor_auth');
        $this->clearAuthContext();

        return $this->json(['status' => true, 'messages' => __('USER__REGISTER_LOGIN')]);
    }

    public function logout(): Response
    {
        $this->disableAutoRender();

        $request = $this->getRequest();

        $request->getSession()->delete('user');
        $request->getSession()->delete('user_id_two_factor_auth');
        $request->getSession()->delete('two_factor_secret_tmp');

        $this->clearAuthContext();

        return $this->redirect($this->referer())
            ->withExpiredCookie(new Cookie('remember_me'))
            ->withExpiredCookie(new Cookie('microsoft_user_id'))
            ->withHeader('Cache-Control', 'no-store');
    }

    public function twoFactorGenerateSecret(): Response
    {
        $this->disableAutoRender();

        if (!$this->getRequest()->is('ajax')) {
            return $this->json(['status' => false, 'messages' => __('ERROR__BAD_REQUEST')], 400);
        }

        if (!$this->Auth->isConnected()) {
            return $this->json(['status' => false, 'messages' => __('USER__ERROR_MUST_BE_LOGGED')], 403);
        }

        $identity = $this->Auth->identity();
        $userId = (int)$identity->get('id');
        if ($userId <= 0) {
            return $this->json(['status' => false, 'messages' => __('USER__ERROR_MUST_BE_LOGGED')], 403);
        }

        $issuer = (string)$this->config->getWebsiteName();
        $account = (string)$identity->get('username');

        $generated = $this->otp->generateSetup($issuer, $account);
        if (!$generated['status']) {
            return $this->json(['status' => false, 'messages' => __('ERROR__INTERNAL_ERROR')], 500);
        }

        $session = $this->getRequest()->getSession();
        $session->write('two_factor_secret_tmp', (string)$generated['secret']);

        return $this->json([
            'status' => true,
            'secret' => (string)$generated['secret'],
            'otpauth' => (string)$generated['otpauth'],
            'qrcode_svg' => (string)$generated['qrcode_data_uri'],
        ]);
    }

    public function twoFactorEnable(): Response
    {
        $this->disableAutoRender();

        if (!$this->getRequest()->is('ajax') || !$this->getRequest()->is('post')) {
            return $this->json(['status' => false, 'messages' => __('ERROR__BAD_REQUEST')], 400);
        }

        if (!$this->Auth->isConnected()) {
            return $this->json(['status' => false, 'messages' => __('USER__ERROR_MUST_BE_LOGGED')], 403);
        }

        $identity = $this->Auth->identity();
        $userId = (int)$identity->get('id');
        if ($userId <= 0) {
            return $this->json(['status' => false, 'messages' => __('USER__ERROR_MUST_BE_LOGGED')], 403);
        }

        $code = trim((string)$this->getRequest()->getData('code', ''));
        if ($code === '' || !preg_match('/^\d{6}$/', $code)) {
            return $this->json(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')], 400);
        }

        $session = $this->getRequest()->getSession();
        $secret = (string)$session->read('two_factor_secret_tmp');

        if ($secret === '') {
            return $this->json(['status' => false, 'messages' => __('ERROR__BAD_REQUEST')], 400);
        }

        if (!$this->otp->verifyCode($secret, $code, 1)) {
            return $this->json(['status' => false, 'messages' => __('USER__LOGIN_CODE_INVALID')], 400);
        }

        $Twofa = $this->fetchTable('UsersTwofactorauth');

        $row = $Twofa->find()->where(['user_id' => $userId])->first();
        if ($row === null) {
            $row = $Twofa->newEmptyEntity();
            $row->set('user_id', $userId);
        }

        $row->set('secret', $secret);
        $row->set('enabled', 1);

        if (!$Twofa->save($row)) {
            return $this->json(['status' => false, 'messages' => __('ERROR__INTERNAL_ERROR')], 500);
        }

        $session->delete('two_factor_secret_tmp');

        return $this->json(['status' => true, 'messages' => __('PERMISSIONS__SUCCESS_SAVE')]);
    }

    public function twoFactorDisable(): Response
    {
        $this->disableAutoRender();

        if (!$this->getRequest()->is('ajax')) {
            return $this->json(['status' => false, 'messages' => __('ERROR__BAD_REQUEST')], 400);
        }

        if (!$this->Auth->isConnected()) {
            return $this->json(['status' => false, 'messages' => __('USER__ERROR_MUST_BE_LOGGED')], 403);
        }

        $identity = $this->Auth->identity();
        $userId = (int)$identity->get('id');
        if ($userId <= 0) {
            return $this->json(['status' => false, 'messages' => __('USER__ERROR_MUST_BE_LOGGED')], 403);
        }

        $Twofa = $this->fetchTable('UsersTwofactorauth');
        $row = $Twofa->find()->where(['user_id' => $userId])->first();

        if ($row !== null) {
            $row->set('enabled', 0);
            $Twofa->save($row);
        }

        $this->getRequest()->getSession()->delete('two_factor_secret_tmp');

        return $this->json(['status' => true]);
    }
}
