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
    private Table $Notifications;

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Notifications = $this->fetchTable('Notifications');

        $request = $this->getRequest();
        $isAdminRequest = (bool)$request->getParam('admin');
        $action = (string)$request->getParam('action');

        if (!$isAdminRequest || ($action !== 'index')) {
            $this->disableAutoRender();

            $response = $this->response
                ->withType('application/json')
                ->withStringBody(json_encode([]));

            $this->setResponse($response);

            $event->stopPropagation();
        }
    }

    public function getAll(string $type = 'user'): Response
    {
        $this->response = $this->response->withType('application/json');

        if ($this->Auth->isConnected()) {
            $notifications = $this->Notifications->getFromUser((int)$this->Auth->identity()?->get('id'), $type);

            return $this->response->withStringBody(json_encode($notifications));
        }

        return $this->response->withStringBody(json_encode([]));
    }

    public function clear(int $id = 0): Response
    {
        $this->response = $this->response->withType('application/json');

        if ($this->Auth->isConnected()) {
            $status = $this->Notifications->clearFromUser($id, (int)$this->User->get('id'));

            return $this->response->withStringBody(json_encode(['status' => $status]));
        }

        return $this->response->withStringBody(json_encode(['status' => false]));
    }

    public function clearAll(): Response
    {
        $this->response = $this->response->withType('application/json');

        if ($this->Auth->isConnected()) {
            $status = $this->Notifications->clearAllFromUser((int)$this->User->get('id'));

            return $this->response->withStringBody(json_encode(['status' => $status]));
        }

        return $this->response->withStringBody(json_encode(['status' => false]));
    }

    public function markAsSeen(int $id = 0): Response
    {
        $this->response = $this->response->withType('application/json');

        if ($this->Auth->isConnected()) {
            $status = $this->Notifications->markAsSeenFromUser($id, (int)$this->User->get('id'));

            return $this->response->withStringBody(json_encode(['status' => $status]));
        }

        return $this->response->withStringBody(json_encode(['status' => false]));
    }

    public function markAllAsSeen(): Response
    {
        $this->response = $this->response->withType('application/json');

        if ($this->Auth->isConnected()) {
            $status = $this->Notifications->markAllAsSeenFromUser((int)$this->User->get('id'));

            return $this->response->withStringBody(json_encode(['status' => $status]));
        }

        return $this->response->withStringBody(json_encode(['status' => false]));
    }
}
