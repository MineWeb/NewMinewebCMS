<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Service\LangService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;

class NotificationsController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('DataTable');
    }

    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_NOTIFICATIONS')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('NOTIFICATION__TITLE'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Notifications')
            ->setTemplate('index');

        return null;
    }

    public function getAll(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->getRequest()->is('ajax')) {
            return $this->response->withStringBody(json_encode([]));
        }

        $Notifications = $this->fetchTable('Notifications');
        $this->DataTable->setTable($Notifications);

        $this->paginate = [
            'contain' => ['Users', 'FromUsers'],
            'fields' => [
                'Notifications.id',
                'Notifications.group',
                'Notifications.user_id',
                'Notifications.from',
                'Notifications.content',
                'Notifications.seen',
                'Notifications.type',
                'Notifications.created_at',
                'Users.username',
                'FromUsers.username',
            ],
        ];

        $this->DataTable->mDataProp = true;
        $response = $this->DataTable->getResponse();

        $rows = $response['aaData'] ?? [];
        $data = [];

        foreach ($rows as $notification) {
            $id = (int)($notification['id'] ?? 0);
            $userId = (int)($notification['user_id'] ?? 0);

            $fromHtml = '<small class="text-muted">' . __('NOTIFICATION__NO_FROM') . '</small>';
            $fromVal = $notification['from'] ?? null;
            if ($fromVal !== null && (string)$fromVal !== '' && (int)$fromVal > 0) {
                $fromName = $notification['from_user']['username'] ?? $notification['FromUsers']['username'] ?? null;
                if (is_string($fromName) && $fromName !== '') {
                    $fromHtml = $fromName;
                }
            }

            $actions = '<div class="btn-group-vertical" role="group" style="width:100%;">';
            if (!empty($notification['seen'])) {
                $actions .= '<button type="button" class="btn btn-default disabled active" disabled>'
                    . __('NOTIFICATION__SEEN')
                    . '</button>';
            } else {
                $actions .= '<a class="btn btn-default mark-as-seen" data-seen="' . __('NOTIFICATION__SEEN') . '" href="'
                    . Router::url(['_name' => 'admin_notifications_mark_as_seen_from_user', $id, $userId])
                    . '">'
                    . __('NOTIFICATION__MARK_AS_SEEN')
                    . '</a>';
            }

            $actions .= '<a class="btn btn-danger delete-notification" href="'
                . Router::url(['_name' => 'admin_notifications_clear_from_user', $id, $userId])
                . '">'
                . __('GLOBAL__DELETE')
                . '</a>';
            $actions .= '</div>';

            $typeVal = (string)($notification['type'] ?? '');
            $typeHtml = $typeVal === 'admin'
                ? '<span class="badge badge-danger">' . __('NOTIFICATION__TYPE_ADMIN') . '</span>'
                : '<span class="badge badge-success">' . __('NOTIFICATION__TYPE_USER') . '</span>';

            $groupVal = (string)($notification['group'] ?? '');
            $groupHtml = $groupVal !== ''
                ? '#' . $groupVal
                : '<small class="text-muted">' . __('NOTIFICATION__NO_GROUP') . '</small>';

            $createdRaw = $notification['created_at'] ?? null;
            $created = $createdRaw === null ? '' : 'Le ' . LangService::date((string)$createdRaw);

            $data[] = [
                'Notifications' => [
                    'group' => $groupHtml,
                    'from' => $fromHtml,
                    'content' => (string)($notification['content'] ?? ''),
                    'type' => $typeHtml,
                    'created_at' => $created,
                    'actions' => $actions,
                ],
                'Users' => [
                    'username' => (string)($notification['user']['username'] ?? $notification['Users']['username'] ?? ''),
                ],
            ];
        }

        $response['aaData'] = $data;

        return $this->response->withStringBody(json_encode($response));
    }

    public function setTo(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $request = $this->getRequest();

        if (!$request->is('ajax') || !$request->is('post')) {
            throw new NotFoundException();
        }

        $content = trim((string)$request->getData('content', ''));
        $userIdRaw = $request->getData('user_id');
        $username = trim((string)$request->getData('user_username', ''));
        $fromFlag = (bool)$request->getData('from', false);

        if ($content === '' || $userIdRaw === null || ($userIdRaw !== 'all' && $username === '')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $Notifications = $this->fetchTable('Notifications');

        $from = null;
        if ($fromFlag) {
            $current = $this->Auth->user();
            if (is_object($current)) {
                $from = (int)($current->get('id') ?? 0);
                if ($from <= 0) {
                    $from = null;
                }
            }
        }

        if ($userIdRaw === 'all') {
            $Notifications->setToAll($content, $from);
        } else {
            $Users = $this->fetchTable('Users');

            $user = $Users->find()
                ->select(['id'])
                ->where(['Users.username' => $username])
                ->first();

            $userId = $user ? (int)$user->get('id') : 0;

            if ($userId <= 0) {
                return $this->response->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('USER__EDIT_ERROR_UNKNOWN'),
                ]));
            }

            $Notifications->setToUser($content, $userId, $from);
        }

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('NOTIFICATION__SUCCESS_SET'),
        ]));
    }

    public function clearFromUser(int|string $id, int|string $user_id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $Notifications = $this->fetchTable('Notifications');
        $status = $Notifications->clearFromUser((int)$id, (int)$user_id);

        return $this->response->withStringBody(json_encode(['status' => (bool)$status]));
    }

    public function clearAllFromUser(int|string $user_id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $Notifications = $this->fetchTable('Notifications');
        $status = $Notifications->clearAllFromUser((int)$user_id);

        return $this->response->withStringBody(json_encode(['status' => (bool)$status]));
    }

    public function clearFromAllUsers(int|string $id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $Notifications = $this->fetchTable('Notifications');
        $status = $Notifications->clearFromAllUsers((int)$id);

        return $this->response->withStringBody(json_encode(['status' => (bool)$status]));
    }

    public function clearAllFromAllUsers(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationsTable = $this->fetchTable('Notifications');
        $affected = $notificationsTable->clearAllFromAllUsers();

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'affected' => (int)$affected,
        ]));
    }

    public function markAsSeenFromUser(int|string $id, int|string $user_id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $Notifications = $this->fetchTable('Notifications');
        $status = $Notifications->markAsSeenFromUser((int)$id, (int)$user_id);

        return $this->response->withStringBody(json_encode(['status' => (bool)$status]));
    }

    public function markAllAsSeenFromUser(int|string $user_id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $Notifications = $this->fetchTable('Notifications');
        $status = $Notifications->markAllAsSeenFromUser((int)$user_id);

        return $this->response->withStringBody(json_encode(['status' => (bool)$status]));
    }

    public function markAsSeenFromAllUsers(int|string $id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $Notifications = $this->fetchTable('Notifications');
        $status = $Notifications->markAsSeenFromAllUsers((int)$id);

        return $this->response->withStringBody(json_encode(['status' => (bool)$status]));
    }

    public function markAllAsSeenFromAllUsers(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationsTable = $this->fetchTable('Notifications');
        $affected = $notificationsTable->markAllAsSeenFromAllUsers();

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'affected' => (int)$affected,
        ]));
    }

    public function clearAllFromGroup(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->getRequest()->is('ajax') || !$this->getRequest()->is('post')) {
            throw new NotFoundException();
        }

        $group = trim((string)$this->getRequest()->getData('group', ''));

        if ($group === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $notificationsTable = $this->fetchTable('Notifications');
        $affected = $notificationsTable->clearAllFromGroup($group);

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'affected' => (int)$affected,
            'messages' => __('NOTIFICATION__SUCCESS_REMOVE'),
        ]));
    }
}
