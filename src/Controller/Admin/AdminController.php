<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\ORM\Table;

/**
 * @property \App\Controller\Component\AuthComponent $Auth
 * @property \App\Controller\Component\EyPluginComponent $EyPlugin
 * @property \App\Controller\Component\ServerComponent $Server
 * @property \App\Controller\Component\HistoryComponent $History
 */
class AdminController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
    }

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

        $nbr_news = $this->fetchTable('News')->find()->count();

        $commentTable = $this->fetchTable('Comments');
        $nbr_comments_today = $commentTable->find()
            ->where(['created_at LIKE' => date('Y-m-d') . '%'])
            ->count();

        if ($nbr_comments_today === 0) {
            $nbr_comments = $commentTable->find()->count();
            $nbr_comments_type = 'all';
        } else {
            $nbr_comments = $nbr_comments_today;
            $nbr_comments_type = 'today';
        }

        $userTable = $this->fetchTable('Users');
        $registered_users = $userTable->find()->count();
        $registered_users_today = $userTable->find()
            ->where(['created_at LIKE' => date('Y-m-d') . '%'])
            ->count();

        $visitTable = $this->fetchTable('Visits');
        $count_visits = method_exists($visitTable, 'getVisitsCount') ? (int)$visitTable->getVisitsCount() : 0;

        $count_visits_before_before_yesterday = $this->safeVisitCountByDay($visitTable, date('Y-m-d', strtotime('-3 day')));
        $count_visits_before_yesterday = $this->safeVisitCountByDay($visitTable, date('Y-m-d', strtotime('-2 day')));
        $count_visits_yesterday = $this->safeVisitCountByDay($visitTable, date('Y-m-d', strtotime('-1 day')));
        $count_visits_today = $this->safeVisitCountByDay($visitTable, date('Y-m-d'));

        $purchase = 0;
        $purchase_today = 0;
        $items_solded = [];

        if ($this->EyPlugin->isInstalled('eywek.shop')) {
            $itemsBuyHistoryTable = $this->fetchTable('Shop.ItemsBuyHistory');

            $purchase = $itemsBuyHistoryTable->find()->count();

            $purchase_today = $itemsBuyHistoryTable->find()
                ->where(['created_at LIKE' => date('Y-m-d') . '%'])
                ->count();

            $itemTable = $this->fetchTable('Shop.Item');
            $findItems = $itemTable->find()->all();

            $itemsNameByID = [];
            foreach ($findItems as $value) {
                $itemsNameByID[(int)$value->get('id')] = (string)$value->get('name');
            }

            $find_items_solded = $itemsBuyHistoryTable->find()
                ->select([
                    'cnt' => $itemsBuyHistoryTable->find()->func()->count('*'),
                    'item_id' => 'item_id',
                ])
                ->groupBy('item_id')
                ->orderByDesc('cnt')
                ->limit(5)
                ->all();

            $i = 0;
            foreach ($find_items_solded as $row) {
                $itemId = (int)$row->get('item_id');
                $items_solded[$i] = [
                    'count' => (int)$row->get('cnt'),
                    'item_name' => $itemsNameByID[$itemId] ?? null,
                ];
                $i++;
            }
        }

        $servers = $this->fetchTable('Servers')->find()->all();

        if ($this->request->is('ajax') && $this->Auth->can('SEND_SERVER_COMMAND_FROM_DASHBOARD')) {
            $serverId = $this->request->getData('server_id');
            if (is_scalar($serverId) && (string)$serverId !== '') {
                $this->disableAutoRender();

                $cmd = $this->request->getData('cmd') ?? $this->request->getData('cmd2');
                if (is_string($cmd) && $cmd !== '') {
                    $this->Server->send_command($cmd, $serverId);
                }

                return $this->response
                    ->withType('application/json')
                    ->withStringBody(json_encode([
                        'status' => true,
                        'messages' => __('SERVER__SEND_COMMAND_SUCCESS'),
                    ]));
            }
        }

        $search_cmd = $this->fetchTable('ServerCmds')->find()->all();

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

        $this->set('History', $this->History);
        $this->set('Server', $this->Server);
        $this->set('EyPlugin', $this->EyPlugin);

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
            new Cookie('use_admin_dark_mode', $admin_dark_mode ? '0' : '1')
        );
    }

    private function safeVisitCountByDay(Table $visitTable, string $day): int
    {
        if (!method_exists($visitTable, 'getVisitsByDay')) {
            return 0;
        }

        $data = $visitTable->getVisitsByDay($day);
        if (is_array($data) && isset($data['count'])) {
            return (int)$data['count'];
        }

        return 0;
    }
}
