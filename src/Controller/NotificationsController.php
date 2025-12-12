<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\ORM\Table;

/**
 * @property \App\Model\Table\UsersTable $User
 * @property \App\Model\Table\NotificationsTable $Notification
 */
class NotificationsController extends AppController
{
    private Table $Notification;

    public function beforeFilter(EventInterface $event): ?Response
    {
        $response = parent::beforeFilter($event);
        if ($response instanceof Response) {
            return $response;
        }

        $this->Notification = $this->fetchTable('Notification');

        $request = $this->getRequest();
        $isAdminRequest = (bool)$request->getParam('admin');
        $action = (string)$request->getParam('action');

        if (!$isAdminRequest || ($isAdminRequest && $action !== 'index')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');
            $this->response = $this->response->withStringBody(json_encode([]));

            return $this->response;
        }

        return null;
    }

    public function getAll(string $type = 'user'): Response
    {
        $this->response = $this->response->withType('application/json');

        if ($this->isConnected) {
            $notifications = $this->Notification->getFromUser($this->User->getKey('id'), $type);
            return $this->response->withStringBody(json_encode($notifications));
        }

        return $this->response->withStringBody(json_encode([]));
    }

    public function clear(int $id = 0): Response
    {
        $this->response = $this->response->withType('application/json');

        if ($this->isConnected) {
            $status = $this->Notification->clearFromUser($id, $this->User->getKey('id'));
            return $this->response->withStringBody(json_encode(['status' => $status]));
        }

        return $this->response->withStringBody(json_encode(['status' => false]));
    }

    public function clearAll(): Response
    {
        $this->response = $this->response->withType('application/json');

        if ($this->isConnected) {
            $status = $this->Notification->clearAllFromUser($this->User->getKey('id'));
            return $this->response->withStringBody(json_encode(['status' => $status]));
        }

        return $this->response->withStringBody(json_encode(['status' => false]));
    }

    public function markAsSeen(int $id = 0): Response
    {
        $this->response = $this->response->withType('application/json');

        if ($this->isConnected) {
            $status = $this->Notification->markAsSeenFromUser($id, $this->User->getKey('id'));
            return $this->response->withStringBody(json_encode(['status' => $status]));
        }

        return $this->response->withStringBody(json_encode(['status' => false]));
    }

    public function markAllAsSeen(): Response
    {
        $this->response = $this->response->withType('application/json');

        if ($this->isConnected) {
            $status = $this->Notification->markAllAsSeenFromUser($this->User->getKey('id'));
            return $this->response->withStringBody(json_encode(['status' => $status]));
        }

        return $this->response->withStringBody(json_encode(['status' => false]));
    }
}
