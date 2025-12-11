<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;
use DateTime;
use Exception;
use RobThree\Auth\TwoFactorAuth;

class AuthentificationController extends AppController
{
    public function validLogin(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->request->is('post')) {
            throw new NotFoundException('Not post');
        }

        if (!$this->getRequest()->getSession()->read('user_id_two_factor_auth')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__LOGIN_INFOS_NOT_FOUND'),
            ]));
        }

        if (empty($this->request->getData('code'))) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__LOGIN_CODE_EMPTY'),
            ]));
        }

        $user = $this->User
            ->find('all', ['conditions' => ['id' => $this->getRequest()->getSession()->read('user_id_two_factor_auth')]])
            ->first();

        if (empty($user)) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__LOGIN_INFOS_NOT_FOUND'),
            ]));
        }

        $this->Authentification = TableRegistry::getTableLocator()->get('UsersTwofactorauth');
        $infos = $this->Authentification
            ->find('all', conditions: ['user_id' => $user['id']])
            ->first();

        if ($infos === null || !$infos['enabled']) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__LOGIN_INFOS_NOT_FOUND'),
            ]));
        }

        $ga = new TwoFactorAuth();
        $checkResult = $ga->verifyCode($infos['secret'], $this->request->getData('code'), 2);

        if (!$checkResult) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__LOGIN_CODE_INVALID'),
            ]));
        }

        $this->getRequest()->getSession()->delete('user_id_two_factor_auth');

        if ($this->request->getData('remember_me')) {
            $cookie = new Cookie('remember_me', [
                'pseudo' => $user['pseudo'],
                'password' => $this->User->getFromUser('password', $user['pseudo']),
            ], new DateTime('+1 weeks'));

            $this->response = $this->getResponse()->withCookie($cookie);
        }

        $this->getRequest()->getSession()->write('user', $user['id']);

        $event = new Event('afterLogin', $this, [
            'user' => $this->User->getAllFromUser($user['pseudo']),
        ]);
        $this->getEventManager()->dispatch($event);

        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }
        }

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('USER__REGISTER_LOGIN'),
        ]));
    }

    public function generateSecret(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected) {
            throw new ForbiddenException('Not logged');
        }

        $ga = new TwoFactorAuth();
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeImageAsDataUri($this->User->getKey('pseudo'), $secret);

        $this->getRequest()->getSession()->write('two-factor-auth-secret', $secret);

        return $this->response->withStringBody(json_encode([
            'qrcode_url' => $qrCodeUrl,
            'secret' => $secret,
        ]));
    }

    public function validEnable(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->request->is('post')) {
            throw new NotFoundException('Not post');
        }

        if (!$this->isConnected) {
            throw new ForbiddenException('Not logged');
        }

        if (empty($this->request->getData('code'))) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__LOGIN_CODE_EMPTY'),
            ]));
        }

        if (!$this->getRequest()->getSession()->read('two-factor-auth-secret')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__SECRET_NOT_FOUND'),
            ]));
        }

        $secret = $this->getRequest()->getSession()->read('two-factor-auth-secret');

        $ga = new TwoFactorAuth();
        $checkResult = $ga->verifyCode($secret, $this->request->getData('code'), 2);

        if (!$checkResult) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('USER__LOGIN_CODE_INVALID'),
            ]));
        }

        $this->getRequest()->getSession()->delete('two-factor-auth-secret');

        $this->Authentification = TableRegistry::getTableLocator()->get('UsersTwofactorauth');

        $infos = $this->Authentification
            ->find('all', conditions: ['user_id' => $this->User->getKey('id')])
            ->first();

        if ($infos) {
            $auth = $this->Authentification->get($infos['id']);
        } else {
            $auth = $this->Authentification->newEmptyEntity();
        }

        $auth->set([
            'secret' => $secret,
            'enabled' => true,
            'user_id' => $this->User->getKey('id'),
        ]);

        $this->Authentification->save($auth);

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('USER__SUCCESS_ENABLED_TWO_FACTOR_AUTH'),
        ]));
    }

    public function disable(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected) {
            throw new ForbiddenException('Not logged');
        }

        $this->Authentification = TableRegistry::getTableLocator()->get('UsersTwofactorauth');

        $infos = $this->Authentification
            ->find('all', conditions: ['user_id' => $this->User->getKey('id')])
            ->first();

        if ($infos) {
            $auth = $this->Authentification->get($infos['id']);
            $auth->set(['enabled' => false]);
            $this->Authentification->save($auth);
        }

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('USER__SUCCESS_DISABLED_TWO_FACTOR_AUTH'),
        ]));
    }
}
