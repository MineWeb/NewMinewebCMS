<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use DateTime;
use Exception;
use RobThree\Auth\TwoFactorAuth;

class AuthentificationController extends AppController
{

    public function validLogin()
    {
        $this->response = $this->response->withType('application/json');
        $this->autoRender = false;

        // valid request
        if (!$this->request->is('post'))
            throw new NotFoundException('Not post');
        if (!$this->getRequest()->getSession()->read('user_id_two_factor_auth'))
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__LOGIN_INFOS_NOT_FOUND')]));
        if (empty($this->request->getData('code')))
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__LOGIN_CODE_EMPTY')]));
        // find user
        $user = $this->User->find('all', ['conditions' => ['id' => $this->getRequest()->getSession()->read('user_id_two_factor_auth')]])->first();
        if (empty($user))
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__LOGIN_INFOS_NOT_FOUND')]));
        // get user infos
        $this->Authentification = TableRegistry::getTableLocator()->get('Authentification');
        $infos = $this->Authentification->find('all', conditions: ['user_id' => $user['id']])->first();
        if ($infos == null || !$infos['enabled'])
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__LOGIN_INFOS_NOT_FOUND')]));
        // include library & init
        $ga = new TwoFactorAuth();
        // check code
        $checkResult = $ga->verifyCode($infos['secret'], $this->request->getData('code'), 2);    // 2 = 2*30sec clock tolerance
        if (!$checkResult)
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__LOGIN_CODE_INVALID')]));
        // remove TwoFactorAuth session
        $this->getRequest()->getSession()->delete('user_id_two_factor_auth');
        // login
        if ($this->request->getData('remember_me')) {
            $cookie = new Cookie('remember_me', [
                'pseudo' => $user['pseudo'],
                'password' => $this->User->getFromUser('password', $user['pseudo'])
            ], new DateTime('+1 weeks'));
            $this->response = $this->getResponse()->withCookie($cookie);
        }
        $this->getRequest()->getSession()->write('user', $user['id']);
        $event = new Event('afterLogin', $this, ['user' => $this->User->getAllFromUser($user['pseudo'])]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->getResult();
        }
        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('USER__REGISTER_LOGIN')]));
    }

    /**
     * @throws Exception
     */
    public function generateSecret()
    {
        $this->response = $this->response->withType('application/json');
        $this->autoRender = false;
        // valid request
        if (!$this->isConnected)
            throw new ForbiddenException('Not logged');
        // include library & init
        $ga = new TwoFactorAuth();
        // generate and set into session
        $secret = $ga->createSecret();
        $qrCodeUrl = $ga->getQRCodeImageAsDataUri($this->User->getKey('pseudo'), $secret);
        $this->log('La');
        $this->getRequest()->getSession()->write('two-factor-auth-secret', $secret);
        // send to user
        return $this->response->withStringBody(json_encode(['qrcode_url' => $qrCodeUrl, 'secret' => $secret]));
    }

    public function validEnable()
    {
        $this->response = $this->response->withType('application/json');
        $this->autoRender = false;
        // valid request
        if (!$this->request->is('post'))
            throw new NotFoundException('Not post');
        if (!$this->isConnected)
            throw new ForbiddenException('Not logged');
        if (empty($this->request->getData('code')))
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__LOGIN_CODE_EMPTY')]));
        if (!$this->getRequest()->getSession()->read('two-factor-auth-secret'))
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__SECRET_NOT_FOUND')]));
        $secret = $this->getRequest()->getSession()->read('two-factor-auth-secret');
        // include library & init
        $ga = new TwoFactorAuth();
        // check code
        $checkResult = $ga->verifyCode($secret, $this->request->getData('code'), 2);    // 2 = 2*30sec clock tolerance
        if (!$checkResult)
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__LOGIN_CODE_INVALID')]));
        // remove TwoFactorAuth session
        $this->getRequest()->getSession()->delete('two-factor-auth-secret');
        // save into db
        $this->Authentification = TableRegistry::getTableLocator()->get('Authentification');

        if ($infos = $this->Authentification->find('all', conditions: ['user_id' => $this->User->getKey('id')])->first())
            $auth = $this->Authentification->get($infos['id']);
        else
            $auth = $this->Authentification->newEmptyEntity();

        $auth->set(['secret' => $secret, 'enabled' => true, 'user_id' => $this->User->getKey('id')]);
        $this->Authentification->save($auth);
        // send to user
        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('USER__SUCCESS_ENABLED_TWO_FACTOR_AUTH')]));
    }

    public function disable()
    {
        $this->response = $this->response->withType('application/json');
        $this->autoRender = false;
        // valid request
        if (!$this->isConnected)
            throw new ForbiddenException('Not logged');
        // save into db
        $this->Authentification = TableRegistry::getTableLocator()->get('Authentification');
        $infos = $this->Authentification->find('all', conditions: ['user_id' => $this->User->getKey('id')])->first();
        $auth = $this->Authentification->get($infos['id']);
        $auth->set(['enabled' => false]);
        $this->Authentification->save($auth);
        //send to user
        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('USER__SUCCESS_DISABLED_TWO_FACTOR_AUTH')]));
    }
}
