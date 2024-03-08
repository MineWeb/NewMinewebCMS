<?php
namespace App\Controller;

use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

class InstallController extends AppController
{
    public function beforeFilter(EventInterface $event)
    {
        parent::beforeFilter($event);
        if (file_exists(ROOT . DS . 'config' . DS . 'installed.txt'))
            $this->redirect('/');
    }

    public function index()
    {
        $this->viewBuilder()
            ->setLayout('install');

        $this->set('title_for_layout', $this->Lang->get('INSTALL__INSTALL'));
        $users = TableRegistry::getTableLocator()->get('User');
        $admin = $users->find()->first();
        if (!empty($admin)) {
            $this->set('admin_pseudo', $admin->get('pseudo'));
            $this->set('admin_password', 1);
            $this->set('admin_email', $admin->get('email'));
        }
    }

    public function step1()
    {
        $this->autoRender = false;
        $this->response->withType('json');

        if (!$this->request->is('ajax'))
            throw new NotFoundException();
        $ip = $this->Util->getIP();
        if (file_exists(ROOT . DS . 'config' . DS . 'secure.txt')) {
            $secure = json_decode(file_get_contents(ROOT . DS . 'config' . DS . 'secure.txt'), true);
            if ($secure['ip'] != $ip)
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__IP_WRONG')]));
        }
        if (empty($this->request->getData('pseudo')) || empty($this->request->getData('password')) || empty($this->request->getData('password_confirmation')) || empty($this->request->getData('email')))
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
        if ($this->request->getData('password') !== $this->request->getData('password_confirmation'))
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__ERROR_PASSWORDS_NOT_SAME')]));
        if (!filter_var($this->request->getData('email'), FILTER_VALIDATE_EMAIL))
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__ERROR_EMAIL_NOT_VALID')]));

        $this->request = $this->request->withData('ip', $ip);
        $this->request = $this->request->withData('rank', 4);
        $this->request = $this->request->withData('password', $this->Util->password($this->request->getData('password'), $this->request->getData('pseudo')));

        $userTable = TableRegistry::getTableLocator()->get("User");
        $user = $userTable->newEntity($this->request->getData());
        $userTable->save($user);

        $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('USER__REGISTER_SUCCESS')]));
    }

    public function end()
    {
        $this->autoRender = false;
        if (!file_exists(ROOT . DS . 'config' . DS . 'installed.txt')) {
            file_put_contents(ROOT . DS . 'config' . DS . 'installed.txt', "\n");
            $this->redirect('/');
        } else {
            $this->redirect(['controller' => 'install', 'action' => 'index']);
        }
    }
}
