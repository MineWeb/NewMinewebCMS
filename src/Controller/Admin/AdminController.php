<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Cookie\Cookie;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Response;
use Cake\ORM\TableRegistry;

class AdminController extends AppController
{
    function index()
    {
        if ($this->isConnected and $this->Permissions->can('ACCESS_DASHBOARD')) {
            $this->set('title_for_layout', $this->Lang->get('GLOBAL__HOME'));
            $this->viewBuilder()->setLayout('admin');

            $this->News = TableRegistry::getTableLocator()->get('News');
            $nbr_news = $this->News->find()->count();

            $this->Comment = TableRegistry::getTableLocator()->get('Comment');
            $nbr_comments = $this->Comment->find('all', conditions: ['created LIKE' => date('Y-m-d') . '%'])->count();
            if ($nbr_comments == 0) {
                $nbr_comments = $this->Comment->find()->count();
                $nbr_comments_type = "all";
            } else {
                $nbr_comments_type = "today";
            }

            $registered_users = $this->User->find()->count();
            $registered_users_today = $this->User->find('all', ['conditions' => ['created LIKE' => date('Y-m-d') . '%']])->count();

            $count_visits = $this->Visit->getVisitsCount();
            $count_visits_before_before_yesterday = $this->Visit->getVisitsByDay(date('Y-m-d', strtotime('-3 day')))['count'];
            $count_visits_before_yesterday = $this->Visit->getVisitsByDay(date('Y-m-d', strtotime('-2 day')))['count'];
            $count_visits_yesterday = $this->Visit->getVisitsByDay(date('Y-m-d', strtotime('-1 day')))['count'];
            $count_visits_today = $this->Visit->getVisitsByDay(date('Y-m-d'))['count'];
            $purchase = [];
            $purchase_today = [];
            $items_solded = [];
            if ($this->EyPlugin->isInstalled('eywek.shop')) {
                $this->ItemsBuyHistory = TableRegistry::getTableLocator()->get('Shop.ItemsBuyHistory');
                $purchase = $this->ItemsBuyHistory->find('all', order: 'id DESC')->count();
                $purchase_today = $this->ItemsBuyHistory->find('all', conditions: ['created LIKE' => date('Y-m-d') . '%'], order: 'id DESC')->count();

                $this->Item = TableRegistry::getTableLocator()->get('Shop.Item');
                $findItems = $this->Item->find()->all();
                $itemsNameByID = [];
                foreach ($findItems as $value) {
                    $itemsNameByID[$value['id']] = $value['name'];
                }

                $find_items_solded = $this->ItemsBuyHistory->find('all',
                fields: 'COUNT(*),item_id',
                order: 'COUNT(id) DESC',
                group: 'item_id',
                limit: 5)->all();
                $i = 0;

                foreach ($find_items_solded as $value) {
                    $items_solded[$i]['count'] = $value[0]['COUNT(*)'];
                    $items_solded[$i]['item_name'] = @$itemsNameByID[$value['item_id']];
                    $i++;
                }
            }

            $this->Server = TableRegistry::getTableLocator()->get('Server');
            $servers = $this->Server->find()->all();

            if ($this->request->is('ajax') && $this->Permissions->can('SEND_SERVER_COMMAND_FROM_DASHBOARD')) {
                if ($this->request->getData('server_id') != null) {
                    $this->ServerComponent = $this->loadComponent('Server');
                    $this->autoRender = false;
                    $this->response = $this->response->withType('application/json');
                    if ($this->request->getData('cmd') != null) {
                         $this->ServerComponent->send_command($this->request->getData('cmd'), $this->request->getData('server_id'));
                    } else {
                        $this->ServerComponent->send_command($this->request->getData('cmd2'), $this->request->getData('server_id'));
                    }

                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SERVER__SEND_COMMAND_SUCCESS')]));
                }
            }

            $this->ServerCmd = TableRegistry::getTableLocator()->get('ServerCmd');
            $search_cmd = $this->ServerCmd->find()->all();
            $this->set(compact(
                'nbr_news',
                'nbr_comments', 'nbr_comments_type',
                'registered_users', 'registered_users_today',
                'count_visits', 'count_visits_before_before_yesterday', 'count_visits_before_yesterday', 'count_visits_yesterday', 'count_visits_today',
                'purchase', 'purchase_today', 'items_solded',
                'servers',
                'search_cmd'
            ));
        } else {
            $this->redirect('/');
        }
    }

    function switchAdminDarkMode(): Response
    {
        $this->autoRender = false;
        if ($this->isConnected) {
            $admin_dark_mode = (bool)$this->getRequest()->getCookie('use_admin_dark_mode');
            return $this->response->withCookie(new Cookie('use_admin_dark_mode', (string)!$admin_dark_mode));
        } else {
            throw new ForbiddenException();
        }
    }

}
