<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;

class AdminController extends AppController
{
    public function index(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('ACCESS_DASHBOARD'))) {
            return $this->redirect('/');
        }

        $this->set('title_for_layout', __('GLOBAL__HOME'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Admin')
            ->setTemplate('index');

        $newsTable = $this->fetchTable('News');
        $nbr_news = $newsTable->find()->count();

        $commentTable = $this->fetchTable('Comments');
        $nbr_comments = $commentTable
            ->find('all', conditions: ['created LIKE' => date('Y-m-d') . '%'])
            ->count();

        if ($nbr_comments === 0) {
            $nbr_comments = $commentTable->find()->count();
            $nbr_comments_type = 'all';
        } else {
            $nbr_comments_type = 'today';
        }

        $userTable = $this->User;
        $registered_users = $userTable->find()->count();
        $registered_users_today = $userTable
            ->find('all', ['conditions' => ['created LIKE' => date('Y-m-d') . '%']])
            ->count();

        $count_visits = $this->Visit->getVisitsCount();
        $count_visits_before_before_yesterday = $this->Visit->getVisitsByDay(date('Y-m-d', strtotime('-3 day')))['count'] ?? 0;
        $count_visits_before_yesterday = $this->Visit->getVisitsByDay(date('Y-m-d', strtotime('-2 day')))['count'] ?? 0;
        $count_visits_yesterday = $this->Visit->getVisitsByDay(date('Y-m-d', strtotime('-1 day')))['count'] ?? 0;
        $count_visits_today = $this->Visit->getVisitsByDay(date('Y-m-d'))['count'] ?? 0;

        $purchase = [];
        $purchase_today = [];
        $items_solded = [];

        if ($this->EyPlugin->isInstalled('eywek.shop')) {
            $itemsBuyHistoryTable = $this->fetchTable('Shop.ItemsBuyHistory');
            $purchase = $itemsBuyHistoryTable
                ->find('all', order: 'id DESC')
                ->count();

            $purchase_today = $itemsBuyHistoryTable
                ->find('all', conditions: ['created LIKE' => date('Y-m-d') . '%'], order: 'id DESC')
                ->count();

            $itemTable = $this->fetchTable('Shop.Item');
            $findItems = $itemTable->find()->all();
            $itemsNameByID = [];

            foreach ($findItems as $value) {
                $itemsNameByID[$value['id']] = $value['name'];
            }

            $find_items_solded = $itemsBuyHistoryTable->find(
                'all',
                fields: 'COUNT(*),item_id',
                order: 'COUNT(id) DESC',
                group: 'item_id',
                limit: 5
            )->all();

            $i = 0;
            foreach ($find_items_solded as $value) {
                $items_solded[$i]['count'] = $value[0]['COUNT(*)'] ?? 0;
                $items_solded[$i]['item_name'] = $itemsNameByID[$value['item_id']] ?? null;
                $i++;
            }
        }

        $serverTable = $this->fetchTable('Servers');
        $servers = $serverTable->find()->all();

        if ($this->request->is('ajax') && $this->Auth->can('SEND_SERVER_COMMAND_FROM_DASHBOARD')) {
            $serverId = $this->request->getData('server_id');
            if ($serverId !== null) {
                $this->ServerComponent = $this->loadComponent('Server');
                $this->disableAutoRender();
                $this->response = $this->response->withType('application/json');

                $cmd = $this->request->getData('cmd') ?? $this->request->getData('cmd2');
                if ($cmd !== null) {
                    $this->ServerComponent->send_command($cmd, $serverId);
                }

                return $this->response->withStringBody(json_encode([
                    'statut' => true,
                    'msg' => __('SERVER__SEND_COMMAND_SUCCESS'),
                ]));
            }
        }

        $serverCmdTable = $this->fetchTable('ServerCmds');
        $search_cmd = $serverCmdTable->find()->all();

        $this->set(compact(
            'nbr_news',
            'nbr_comments',
            'nbr_comments_type',
            'registered_users',
            'registered_users_today',
            'count_visits',
            'count_visits_before_before_yesterday',
            'count_visits_before_yesterday',
            'count_visits_yesterday',
            'count_visits_today',
            'purchase',
            'purchase_today',
            'items_solded',
            'servers',
            'search_cmd'
        ));

        return null;
    }

    public function switchAdminDarkMode(): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected()) {
            throw new ForbiddenException();
        }

        $admin_dark_mode = (bool)$this->getRequest()->getCookie('use_admin_dark_mode');

        return $this->response->withCookie(
            new Cookie('use_admin_dark_mode', (string)!$admin_dark_mode)
        );
    }
}
