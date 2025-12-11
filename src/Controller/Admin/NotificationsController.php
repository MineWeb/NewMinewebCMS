<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Utility\LangService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;

class NotificationsController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_NOTIFICATIONS')) {
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
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationTable = $this->fetchTable('Notifications');

        $this->DataTable = $this->loadComponent('DataTable');
        $this->DataTable->setTable($notificationTable);

        $this->paginate = [
            'contain' => ['User'],
            'fields' => [
                'Notification.id',
                'User.pseudo',
                'Notification.group',
                'Notification.user_id',
                'Notification.from',
                'Notification.content',
                'Notification.seen',
                'Notification.type',
                'Notification.created',
            ],
            'recursive' => 1,
        ];

        $this->DataTable->mDataProp = true;
        $response = $this->DataTable->getResponse();

        $data = [];
        foreach ($response['aaData'] as $notification) {
            if ($notification['from'] === null) {
                $from = '<small class="text-muted">' . __('NOTIFICATION__NO_FROM') . '</small>';
            } else {
                $from = $this->User->getFromUser('pseudo', $notification['from']);
            }

            $actions = '<div class="btn btn-group">';
            if ($notification['seen']) {
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

            if ($notification['type'] === 'admin') {
                $type = '<span class="label label-danger">' . __('NOTIFICATION__TYPE_ADMIN') . '</span>';
            } else {
                $type = '<span class="label label-success">' . __('NOTIFICATION__TYPE_USER') . '</span>';
            }

            $groupLabel = !empty($notification['group'])
                ? '#' . $notification['group']
                : '<small class="text-muted">' . __('NOTIFICATION__NO_FROM') . '</small>';

            $data[] = [
                'Notification' => [
                    'group' => $groupLabel,
                    'from' => $from,
                    'content' => $notification['content'],
                    'type' => $type,
                    'created' => LangService::date($notification['created']),
                    'actions' => $actions,
                ],
                'User' => $notification['user'],
            ];
        }

        $response['aaData'] = $data;

        return $this->response->withStringBody(json_encode($response));
    }

    public function setTo(): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
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
        $userPseudo = (string)$request->getData('user_pseudo', '');
        $fromFlag = $request->getData('from');

        if ($content === '' || $userIdRaw === null || ($userIdRaw !== 'all' && $userPseudo === '')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $notificationTable = $this->fetchTable('Notifications');
        $from = $fromFlag ? $this->User->getKey('id') : null;

        if ($userIdRaw === 'all') {
            $notificationTable->setToAll($content, $from);
        } else {
            $user_id = $this->User->getFromUser('id', $userPseudo);

            if (empty($user_id)) {
                return $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('USER__EDIT_ERROR_UNKNOWN'),
                ]));
            }

            $notificationTable->setToUser($content, $user_id, $from);
        }

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('NOTIFICATION__SUCCESS_SET'),
        ]));
    }

    public function clearFromUser(int|string $id, int|string $user_id): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationTable = $this->fetchTable('Notifications');
        $status = $notificationTable->clearFromUser($id, $user_id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function clearAllFromUser(int|string $user_id): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationTable = $this->fetchTable('Notifications');
        $status = $notificationTable->clearAllFromUser($user_id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function clearFromAllUsers(int|string $id): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationTable = $this->fetchTable('Notifications');
        $status = $notificationTable->clearFromAllUsers($id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function clearAllFromAllUsers(): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationTable = $this->fetchTable('Notifications');
        $status = $notificationTable->clearAllFromAllUsers();

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function markAsSeenFromUser(int|string $id, int|string $user_id): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationTable = $this->fetchTable('Notifications');
        $status = $notificationTable->markAsSeenFromUser($id, $user_id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function markAllAsSeenFromUser(int|string $user_id): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationTable = $this->fetchTable('Notifications');
        $status = $notificationTable->markAllAsSeenFromUser($user_id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function markAsSeenFromAllUsers(int|string $id): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationTable = $this->fetchTable('Notifications');
        $status = $notificationTable->markAsSeenFromAllUsers($id);

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function markAllAsSeenFromAllUsers(): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $notificationTable = $this->fetchTable('Notifications');
        $status = $notificationTable->markAllAsSeenFromAllUsers();

        return $this->response->withStringBody(json_encode(['status' => $status]));
    }

    public function clearAllFromGroup(): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $group = $this->getRequest()->getData('group');

        if ($group === null || $group === '') {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $notificationTable = $this->fetchTable('Notifications');
        $notificationTable->clearAllFromGroup($group);

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('NOTIFICATION__SUCCESS_REMOVE'),
        ]));
    }
}
