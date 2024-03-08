<?php
namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;

class NotificationsController extends AppController
{
    private Table $Notification;
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        if (!$this->getRequest()->getParam('admin') || $this->getRequest()->getParam('admin') && $this->getRequest()->getParam('action') != "index") {
            $this->response = $this->response->withType('application/json');
            $this->disableAutoRender();
            $this->response = $this->response->withStringBody(json_encode([]));
        }

        $this->Notification = TableRegistry::getTableLocator()->get('Notification');
    }

    public function getAll($type = 'user')
    {
        if ($this->isConnected) {
            $notifications = $this->Notification->getFromUser($this->User->getKey('id'), $type);
            return $this->response->withStringBody(json_encode($notifications));
        }
    }

    public function clear($id = 0)
    {
        if ($this->isConnected) {
            $notifications = $this->Notification->clearFromUser($id, $this->User->getKey('id'));
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        }
    }

    public function clearAll()
    {
        if ($this->isConnected) {
            $notifications = $this->Notification->clearAllFromUser($this->User->getKey('id'));
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        }
    }

    public function markAsSeen($id = 0)
    {
        if ($this->isConnected) {
            $notifications = $this->Notification->markAsSeenFromUser($id, $this->User->getKey('id'));
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        }
    }

    public function markAllAsSeen()
    {
        if ($this->isConnected) {
            $notifications = $this->Notification->markAllAsSeenFromUser($this->User->getKey('id'));
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        }
    }
}
