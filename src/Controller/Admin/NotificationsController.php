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

        $notificationsTable = $this->fetchTable('Notifications');

        $this->DataTable = $this->loadComponent('DataTable');
        $this->DataTable->setTable($notificationsTable);

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
            'recursive' => 1,
        ];

        $this->DataTable->mDataProp = true;
        $response = $this->DataTable->getResponse();

        $data = [];
        foreach ($response['aaData'] as $notification) {
            if (($notification['from'] ?? null) === null) {
                $from = '<small class="text-muted">' . __('NOTIFICATION__NO_FROM') . '</small>';
            } else {
                $fromName = $notification['from_user']['username'] ?? null;
                $from = is_string($fromName) && $fromName !== ''
                    ? $fromName
                    : '<small class="text-muted">' . __('NOTIFICATION__NO_FROM') . '</small>';
            }

            $actions = '<div class="btn btn-group">';
            if (!empty($notification['seen'])) {
                $actions .= '<btn class="btn btn-default disabled active" disabled>' . __('NOTIFICATION__SEEN') . '</btn>';
            } else {
                $actions .= '<a class="btn btn-default mark-as-seen" data-seen="' . __('NOTIFICATION__SEEN') . '" href="' . Router::url([
                        '_name' => 'admin_notifications_mark_as_seen_from_user',
                        $notification['id'], $notification['user_id'],
                    ]) . '">' . __('NOTIFICATION__MARK_AS_SEEN') . '</a>';
            }

            $actions .= '<a class="btn btn-danger delete-notification" href="' . Router::url([
                    '_name' => 'admin_notifications_clear_from_user',
                    $notification['id'], $notification['user_id'],
                ]) . '">' . __('GLOBAL__DELETE') . '</a>';
            $actions .= '</div>';

            if (($notification['type'] ?? '') === 'admin') {
                $type = '<span class="label label-danger">' . __('NOTIFICATION__TYPE_ADMIN') . '</span>';
            } else {
                $type = '<span class="label label-success">' . __('NOTIFICATION__TYPE_USER') . '</span>';
            }

            $groupLabel = !empty($notification['group'])
                ? '#' . $notification['group']
                : '<small class="text-muted">' . __('NOTIFICATION__NO_FROM') . '</small>';

            $data[] = [
                'Notifications' => [
                    'group' => $groupLabel,
                    'from' => $from,
                    'content' => $notification['content'] ?? '',
                    'type' => $type,
                    'created_at' => LangService::date($notification['created_at'] ?? null),
                    'actions' => $actions,
                ],
                'Users' => $notification['user'] ?? null,
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

        if (!$request->is('ajax')) {
            throw new NotFoundException();
        }

        $content = (string)$request->getData('content', '');
        $userIdRaw = $request->getData('user_id');
        $username = (string)$request->getData('user_username', '');
        $fromFlag = (bool)$request->getData('from', false);

        if ($content === '' || $userIdRaw === null || ($userIdRaw !== 'all' && $username === '')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $notificationsTable = $this->fetchTable('Notifications');

        $from = $fromFlag ? $this->Auth->id($this->getRequest()) : null;

        if ($userIdRaw === 'all') {
            $notificationsTable->setToAll($content, $from);
        } else {
            $Users = $this->fetchTable('Users');

            $user = $Users->find()
                ->select(['id'])
                ->where(['username' => $username])
                ->first();

            $user_id = $user ? (int)$user->get('id') : 0;

            if ($user_id <= 0) {
                return $this->response->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('USER__EDIT_ERROR_UNKNOWN'),
                ]));
            }

            $notificationsTable->setToUser($content, $user_id, $from);
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

        $notificationsTable = $this->fetchTable('Notifications');
        $status = $notificationsTable->clearFromUser((int)$id, (int)$user_id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function clearAllFromUser(int|string $user_id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationsTable = $this->fetchTable('Notifications');
        $status = $notificationsTable->clearAllFromUser((int)$user_id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function clearFromAllUsers(int|string $id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationsTable = $this->fetchTable('Notifications');
        $status = $notificationsTable->clearFromAllUsers((int)$id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function clearAllFromAllUsers(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationsTable = $this->fetchTable('Notifications');
        $status = $notificationsTable->clearAllFromAllUsers();

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function markAsSeenFromUser(int|string $id, int|string $user_id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationsTable = $this->fetchTable('Notifications');
        $status = $notificationsTable->markAsSeenFromUser((int)$id, (int)$user_id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function markAllAsSeenFromUser(int|string $user_id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationsTable = $this->fetchTable('Notifications');
        $status = $notificationsTable->markAllAsSeenFromUser((int)$user_id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function markAsSeenFromAllUsers(int|string $id): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationsTable = $this->fetchTable('Notifications');
        $status = $notificationsTable->markAsSeenFromAllUsers((int)$id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function markAllAsSeenFromAllUsers(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationsTable = $this->fetchTable('Notifications');
        $status = $notificationsTable->markAllAsSeenFromAllUsers();

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function clearAllFromGroup(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $group = (string)$this->getRequest()->getData('group', '');

        if ($group === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $notificationsTable = $this->fetchTable('Notifications');
        $notificationsTable->clearAllFromGroup($group);

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('NOTIFICATION__SUCCESS_REMOVE'),
        ]));
    }
}
