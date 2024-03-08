<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

class NotificationsController extends AppController
{
    public function index()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_NOTIFICATIONS'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('NOTIFICATION__TITLE'));
    }

    public function getAll()
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS')) {
            $this->disableAutoRender();
            $this->Notification = TableRegistry::getTableLocator()->get('Notification');

            $this->DataTable = $this->loadComponent('DataTable');
            $this->DataTable->setTable($this->Notification);
            $this->paginate = [
                'contain' => ['User'],
                'fields' => ['Notification.id', 'User.pseudo', 'Notification.group', 'Notification.user_id', 'Notification.from', 'Notification.content', 'Notification.seen', 'Notification.type', 'Notification.created'],
                'recursive' => 1
            ];
            $this->DataTable->mDataProp = true;

            $response = $this->DataTable->getResponse();

            $data = [];
            foreach ($response['aaData'] as $notification) {

                if ($notification['from'] == null) {
                    $from = '<small class="text-muted">' . $this->Lang->get('NOTIFICATION__NO_FROM') . '</small>';
                } else {
                    $from = $this->User->getFromUser('pseudo', $notification['from']);
                }

                $actions = '<div class="btn btn-group">';
                if ($notification['seen']) {
                    $actions .= '<btn class="btn btn-default disabled active" disabled>' . $this->Lang->get('NOTIFICATION__SEEN') . '</btn>';
                } else {
                    $actions .= '<a class="btn btn-default mark-as-seen" data-seen="' . $this->Lang->get('NOTIFICATION__SEEN') . '" href="' . Router::url(['action' => 'markAsSeenFromUser', 'admin' => true, $notification['id'], $notification['user_id']]) . '">' . $this->Lang->get('NOTIFICATION__MARK_AS_SEEN') . '</a>';
                }
                $actions .= '<a class="btn btn-danger delete-notification" href="' . Router::url(['action' => 'clearFromUser', 'admin' => true, $notification['id'], $notification['user_id']]) . '">' . $this->Lang->get('GLOBAL__DELETE') . '</a>';
                $actions .= '</div>';

                if ($notification['type'] == "admin") {
                    $type = '<span class="label label-danger">' . $this->Lang->get('NOTIFICATION__TYPE_ADMIN') . '</span>';
                } else {
                    $type = '<span class="label label-success">' . $this->Lang->get('NOTIFICATION__TYPE_USER') . '</span>';
                }

                $data[] = [
                    'Notification' => [
                        'group' => (!empty($notification['group']) ? '#' . $notification['group'] : '<small class="text-muted">' . $this->Lang->get('NOTIFICATION__NO_FROM') . '</small>'),
                        'from' => $from,
                        'content' => $notification['content'],
                        'type' => $type,
                        'created' => $this->Lang->date($notification['created']),
                        'actions' => $actions
                    ],
                    'User' => $notification['user']
                ];
            }
            $response['aaData'] = $data;

            return $this->response->withStringBody(json_encode($response));
        } else {
            throw new ForbiddenException();
        }
    }

    public function setTo()
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS')) {
            $this->disableAutoRender();

            if ($this->request->is('ajax')) {

                if (!empty($this->getRequest()->getData('content')) && !empty($this->getRequest()->getData('user_id')) && ($this->getRequest()->getData('user_id') == 'all' || !empty($this->getRequest()->getData('user_pseudo')))) {
                    $this->Notification = TableRegistry::getTableLocator()->get('Notification');

                    $from = ($this->getRequest()->getData('from')) ? $this->User->getKey('id') : null;

                    if ($this->getRequest()->getData('user_id') == 'all') {

                        $this->Notification->setToAll($this->getRequest()->getData('content'), $from);

                    } else {
                        $user_id = $this->User->getFromUser('id', $this->getRequest()->getData('user_pseudo'));

                        if (empty($user_id))
                            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('USER__EDIT_ERROR_UNKNOWN')]));

                        $this->Notification->setToUser($this->getRequest()->getData('content'), $user_id, $from);
                    }

                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('NOTIFICATION__SUCCESS_SET')]));
                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
                }

            } else {
                throw new NotFoundException();
            }

        } else {
            throw new ForbiddenException();
        }
    }

    public function clearFromUser($id, $user_id)
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS')) {
            $this->disableAutoRender();
            $this->Notification = TableRegistry::getTableLocator()->get('Notification');
            $notifications = $this->Notification->clearFromUser($id, $user_id);
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        } else {
            throw new ForbiddenException();
        }
    }

    public function clearAllFromUser($user_id)
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS')) {
            $this->disableAutoRender();
            $this->Notification = TableRegistry::getTableLocator()->get('Notification');
            $notifications = $this->Notification->clearAllFromUser($user_id);
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        } else {
            throw new ForbiddenException();
        }
    }

    public function clearFromAllUsers($id)
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS')) {
            $this->disableAutoRender();
            $this->Notification = TableRegistry::getTableLocator()->get('Notification');
            $notifications = $this->Notification->clearFromAllUsers($id);
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        } else {
            throw new ForbiddenException();
        }
    }

    public function clearAllFromAllUsers()
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS')) {
            $this->disableAutoRender();
            $this->Notification = TableRegistry::getTableLocator()->get('Notification');
            $notifications = $this->Notification->clearAllFromAllUsers();
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        } else {
            throw new ForbiddenException();
        }
    }

    public function markAsSeenFromUser($id, $user_id)
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS')) {
            $this->disableAutoRender();
            $this->Notification = TableRegistry::getTableLocator()->get('Notification');
            $notifications = $this->Notification->markAsSeenFromUser($id, $user_id);
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        } else {
            throw new ForbiddenException();
        }
    }

    public function markAllAsSeenFromUser($user_id)
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS')) {
            $this->disableAutoRender();
            $this->Notification = TableRegistry::getTableLocator()->get('Notification');
            $notifications = $this->Notification->markAllAsSeenFromUser($user_id);
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        } else {
            throw new ForbiddenException();
        }
    }

    public function markAsSeenFromAllUsers($id)
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS')) {
            $this->disableAutoRender();
            $this->Notification = TableRegistry::getTableLocator()->get('Notification');
            $notifications = $this->Notification->markAsSeenFromAllUsers($id);
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        } else {
            throw new ForbiddenException();
        }
    }

    public function markAllAsSeenFromAllUsers()
    {
        if ($this->isConnected && $this->Permissions->can('MANAGE_NOTIFICATIONS')) {
            $this->disableAutoRender();
            $this->Notification = TableRegistry::getTableLocator()->get('Notification');
            $notifications = $this->Notification->markAllAsSeenFromAllUsers();
            return $this->response->withStringBody(json_encode(['status' => $notifications]));
        } else {
            throw new ForbiddenException();
        }
    }

    public function clearAllFromGroup()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_NOTIFICATIONS'))
            throw new ForbiddenException();
        if ($this->getRequest()->getData('group') == null || empty($this->getRequest()->getData('group')))
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));

        $this->disableAutoRender();

        $this->Notification = TableRegistry::getTableLocator()->get('Notification');
        $this->Notification->clearAllFromGroup($this->getRequest()->getData('group'));
        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('NOTIFICATION__SUCCESS_REMOVE')]));
    }
}
