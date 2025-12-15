<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;

/**
 * @property \App\Model\Table\NotificationsTable $Notifications
 */
class NotificationsController extends AppController
{
    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $this->Notifications = $this->fetchTable('Notifications');

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
    }

    public function getAll(string $type = 'user'): Response
    {
        if (!$this->Auth->isConnected()) {
            return $this->response->withStringBody(json_encode([]));
        }

        $userId = (int)$this->Auth->identity()?->get('id');
        if ($userId <= 0) {
            return $this->response->withStringBody(json_encode([]));
        }

        $notifications = $this->Notifications->getFromUser($userId, $type);

        return $this->response->withStringBody(json_encode($notifications));
    }

    public function clear(int $id = 0): Response
    {
        if (!$this->Auth->isConnected() || $id <= 0) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        $userId = (int)$this->Auth->identity()?->get('id');
        $this->Notifications->clearFromUser($id, $userId);

        return $this->response->withStringBody(json_encode(['status' => true]));
    }

    public function clearAll(): Response
    {
        if (!$this->Auth->isConnected()) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        $userId = (int)$this->Auth->identity()?->get('id');
        $this->Notifications->clearAllFromUser($userId);

        return $this->response->withStringBody(json_encode(['status' => true]));
    }

    public function markAsSeen(int $id = 0): Response
    {
        if (!$this->Auth->isConnected() || $id <= 0) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        $userId = (int)$this->Auth->identity()?->get('id');
        $this->Notifications->markAsSeenFromUser($id, $userId);

        return $this->response->withStringBody(json_encode(['status' => true]));
    }

    public function markAllAsSeen(): Response
    {
        if (!$this->Auth->isConnected()) {
            return $this->response->withStringBody(json_encode(['status' => false]));
        }

        $userId = (int)$this->Auth->identity()?->get('id');
        $this->Notifications->markAllAsSeenFromUser($userId);

        return $this->response->withStringBody(json_encode(['status' => true]));
    }
}
