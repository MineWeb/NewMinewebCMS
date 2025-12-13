<?php
declare(strict_types=1);

namespace App\Controller;

use App\Model\Table\UsersTable;
use App\Service\ConfigurationService;
use App\Service\UserAuthService;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\BadRequestException;
use Cake\Http\Response;
use Cake\I18n\FrozenTime;

class AuthController extends AppController
{
    private UserAuthService $userAuth;

    private UsersTable $User;

    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Captcha');
        $this->loadComponent('Util');

        $this->userAuth = new UserAuthService();

        $this->User = $this->fetchTable('Users');
    }

    private function json(array $payload, int $status = 200): Response
    {
        return $this->response
            ->withStatus($status)
            ->withType('application/json')
            ->withStringBody(json_encode($payload));
    }

    private function clearAuthContext(): void
    {
        $this->getRequest()->getSession()->delete('auth_context');
    }

    public function register(): Response
    {
        $this->disableAutoRender();

        if (!$this->getRequest()->is('post')) {
            throw new BadRequestException();
        }

        $data = (array)$this->getRequest()->getData();

        $conditionsRequired = (bool)$this->config->get('condition');
        if (
            empty($data['username']) ||
            empty($data['password']) ||
            empty($data['password_confirmation']) ||
            empty($data['email']) ||
            ($conditionsRequired && empty($data['condition']))
        ) {
            return $this->json(['statut' => false, 'msg' => __('ERROR__FILL_ALL_FIELDS')], 400);
        }

        if ($data['password'] !== $data['password_confirmation']) {
            return $this->json(['statut' => false, 'msg' => __('USER__ERROR_PASSWORDS_NOT_SAME')], 400);
        }

        if ($this->config->get('check_uuid')) {
            $username = (string)$data['username'];
            $res = @file_get_contents('https://api.mojang.com/users/profiles/minecraft/' . rawurlencode($username));
            if (!$res) {
                return $this->json(['statut' => false, 'msg' => __('USER__ERROR_UUID')], 400);
            }

            $decoded = json_decode($res, true);
            if (!is_array($decoded) || empty($decoded['id'])) {
                return $this->json(['statut' => false, 'msg' => __('USER__ERROR_UUID')], 400);
            }

            $data['uuid'] = (string)$decoded['id'];
        }

        $captchaType = (int)$this->config->get('captcha_type');
        if ($captchaType === 2 || $captchaType === 3) {
            $validCaptcha = $this->Util->isValidReCaptcha(
                (string)($data['recaptcha'] ?? ''),
                $this->Util->getIP(),
                (string)$this->config->get('captcha_secret'),
                $captchaType
            );
        } else {
            $captcha = $this->getRequest()->getSession()->read('captcha_code');
            $validCaptcha = (string)$captcha !== '' && (string)$captcha === (string)($data['captcha'] ?? '');
        }

        if (!$validCaptcha) {
            return $this->json(['statut' => false, 'msg' => __('FORM__INVALID_CAPTCHA')], 400);
        }

        $userId = $this->userAuth->createUser($data, $this->Util->getIP());

        if ((bool)$this->config->get('confirm_mail_signup')) {
            $confirmCode = substr(md5(uniqid('', true)), 0, 12);

            $mail = __('EMAIL__CONTENT_CONFIRM_MAIL', [
                'LINK' => $this->config->get('website_url') . '/auth/confirm/' . $confirmCode,
                'IP' => $this->Util->getIP(),
                'USERNAME' => (string)$data['username'],
                'DATE' => FrozenTime::now()->i18nFormat('dd/MM/yyyy HH:mm'),
            ]);

            if ($this->Util->prepareMail((string)$data['email'], __('EMAIL__TITLE_CONFIRM_MAIL'), $mail)->sendMail()) {
                $user = $this->User->get($userId);
                $user->set(['confirmed' => $confirmCode]);
                $this->User->save($user);
            }
        }

        if (!(bool)$this->config->get('confirm_mail_signup_block')) {
            $this->getRequest()->getSession()->write('user', $userId);
            $this->clearAuthContext();
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

        if (empty($data['username']) || empty($data['password'])) {
            return $this->json(['statut' => false, 'msg' => __('ERROR__FILL_ALL_FIELDS')], 400);
        }

        $user = $this->User->find()->where(['username' => (string)$data['username']])->first();
        if (!$user) {
            return $this->json(['statut' => false, 'msg' => __('USER__ERROR_INVALID_CREDENTIALS')], 400);
        }

        $login = $this->userAuth->attemptLogin(
            $user,
            (string)$data['password'],
            $this->Util->getIP(),
            (bool)$this->config->get('confirm_mail_signup_block'),
            (bool)$this->config->get('check_uuid')
        );

        if (!is_array($login) || empty($login['status'])) {
            return $this->json(['statut' => false, 'msg' => __((string)$login)], 400);
        }

        $this->getRequest()->getSession()->write('user', (int)$login['session']);
        $this->clearAuthContext();

        return $this->json(['statut' => true, 'msg' => __('USER__REGISTER_LOGIN')]);
    }

    public function logout(): Response
    {
        $this->disableAutoRender();

        $this->getRequest()->getSession()->delete('user');
        $this->getRequest()->getSession()->delete('user_id_two_factor_auth');
        $this->clearAuthContext();

        return $this->redirect($this->referer())
            ->withExpiredCookie(new Cookie('remember_me'))
            ->withExpiredCookie(new Cookie('microsoft_user_id'))
            ->withHeader('Cache-Control', 'no-store');
    }
}
