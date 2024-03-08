<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Log\Log;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

class NavbarController extends AppController
{
    public function index()
    {
        if (!$this->Permissions->can('MANAGE_NAV'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('NAVBAR__TITLE'));

        $this->Navbar = TableRegistry::getTableLocator()->get('Navbar');
        $navbars = $this->Navbar->find()
            ->orderBy(['order_by'])
            ->toArray();

        $this->Page = TableRegistry::getTableLocator()->get('Page');
        $pages = $this->Page->find('all', fields: ['id', 'slug'])->all();
        $pages_listed = [];
        foreach ($pages as $value)
            $pages_listed[$value['id']] = $value['slug'];

        foreach ($navbars as $key => $value) {
            if ($value['urlData']['type'] == "plugin") {
                if (isset($value['urlData']['route']))
                    $plugin = $this->EyPlugin->findPlugin('slug', $value['urlData']['id']);
                else
                    $plugin = $this->EyPlugin->findPlugin('DBid', $value['urlData']['id']);
                if (!empty($plugin)) {
                    $navbars[$key]['url'] = (isset($value['urlData']['route'])) ? Router::url($value['urlData']['route']) : Router::url('/' . strtolower($plugin->slug));
                } else {
                    $navbars[$key]['url'] = false;
                }
            } else if ($value['urlData']['type'] == "page") {
                if (isset($pages_listed[$value['url']['id']])) {
                    $navbars[$key]['url'] = Router::url('/p/' . $pages_listed[$value['urlData']['id']]);
                } else {
                    $navbars[$key]['url'] = '#';
                }
            } else if ($value['urlData']['type'] == "custom") {
                $navbars[$key]['url'] = $value['urlData']['url'];
            } else {
                $navbars[$key]['url'] = '#';
            }
        }
        $this->set(compact('navbars'));
    }

    public function saveAjax()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_NAV')) {
            if ($this->request->is('post')) {
                if (!empty($this->request->getData())) {
                    $data = $this->getRequest()->getData('navbar_order');
                    $data = explode('&', $data);
                    $i = 1;
                    foreach ($data as $value) {
                        $data2[] = explode('=', $value);
                        $data3 = substr($data2[0][0], 0, -2);
                        $data1[$data3] = $i;
                        unset($data3);
                        unset($data2);
                        $i++;
                    }
                    $data = $data1;
                    $this->Navbar = TableRegistry::getTableLocator()->get('Navbar');
                    foreach ($data as $key => $value) {
                        $find = $this->Navbar->find('all', conditions: ['id' => $key])->first();
                        if (!empty($find)) {
                            $id = $find['id'];
                            $nav = $this->Navbar->get($id);
                            $nav->set([
                                'order_by' => $value,
                                'url' => $find['url'],
                            ]);
                            $this->Navbar->save($nav);
                        } else {
                            $error = 1;
                        }
                    }
                    if (empty($error)) {
                        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('NAVBAR__SAVE_SUCCESS')]));
                    } else {
                        return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
                    }
                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
                }
            } else {
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')]));
            }
        } else {
            $this->redirect('/');
        }
    }

    public function delete($id = false)
    {
        $this->autoRender = false;
        if ($this->isConnected and $this->Permissions->can('MANAGE_NAV')) {
            if ($id) {
                $this->Navbar = TableRegistry::getTableLocator()->get('Navbar');
                try {
                    $nav = $this->Navbar->get($id);
                } catch (RecordNotFoundException $e) {
                    throw new NotFoundException();
                }

                if ($this->Navbar->delete($nav)) {
                    $this->History->set('DELETE_NAV', 'navbar');
                    $this->Flash->success($this->Lang->get('NAVBAR__DELETE_SUCCESS'));
                }
            }

            $this->redirect(['controller' => 'navbar', 'action' => 'index', 'admin' => true]);
        } else {
            $this->redirect('/');
        }
    }

    public function add()
    {
        if (!$this->Permissions->can('MANAGE_NAV'))
            throw new ForbiddenException();
        $this->set('title_for_layout', $this->Lang->get('NAVBAR__ADD_LINK'));

        $this->Page = TableRegistry::getTableLocator()->get('Page');
        $url_pages = $this->Page->find('all');
        foreach ($url_pages as $key => $value) {
            $url_pages2[$value['id']] = $value['title'];
        }
        $url_pages = (isset($url_pages2)) ? $url_pages2 : [];
        $this->set('url_plugins', $this->EyPlugin->findPluginsLinks());
        $this->set(compact('url_pages'));
    }

    public function addAjax()
    {
        if (!$this->Permissions->can('MANAGE_NAV'))
            throw new ForbiddenException();
        if (!$this->request->is('ajax'))
            throw new NotFoundException();
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');


        if (empty($this->getRequest()->getData('name')) || empty($this->getRequest()->getData('type')) || empty($this->getRequest()->getData('url')) || $this->getRequest()->getData('url') === "undefined")
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));

        $this->Navbar = TableRegistry::getTableLocator()->get('Navbar');
        $order = $this->Navbar->find()
            ->orderBy(['order_by' => 'DESC'])
            ->first();

        $order = (empty($order)) ? 1 : intval($order['order_by']) + 1;

        $open_new_tab = ($this->getRequest()->getData('open_new_tab') == 'true') ? 1 : 0;

        $nav = $this->Navbar->newEntity($this->extracted([
            'order_by' => $order,
            'name' => $this->getRequest()->getData('name'),
            'icon' => $this->getRequest()->getData('icon'),
            'type' => $this->getRequest()->getData('type'),
            'url' => $this->getRequest()->getData('url'),
            'open_new_tab' => $open_new_tab
        ]));
        $this->Navbar->save($nav);

        $this->History->set('ADD_NAV', 'navbar');

        $this->Flash->success($this->Lang->get('NAVBAR__ADD_SUCCESS'));
        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('NAVBAR__ADD_SUCCESS')]));
    }

    public function edit($id = false)
    {
        if (!$this->Permissions->can('MANAGE_NAV'))
            throw new ForbiddenException();
        if (!$id)
            throw new NotFoundException();

        $this->Navbar = TableRegistry::getTableLocator()->get('Navbar');
        $nav = $this->Navbar->find('all', conditions: ['id' => $id])->first();
        if (empty($nav))
            throw new NotFoundException();

        $this->set('title_for_layout', $this->Lang->get('NAVBAR__EDIT_TITLE'));

        $this->Page = TableRegistry::getTableLocator()->get('Page');
        $url_pages = $this->Page->find()->all();
        foreach ($url_pages as $key => $value) {
            $url_pages2[$value['id']] = $value['title'];
        }
        $url_pages = (isset($url_pages2)) ? $url_pages2 : [];

        $this->set(compact('url_pages', 'nav'));
        $this->set('url_plugins', $this->EyPlugin->findPluginsLinks());
    }

    public function editAjax($id)
    {
        if (!$this->Permissions->can('MANAGE_NAV'))
            throw new ForbiddenException();
        if (!$this->request->is('ajax'))
            throw new NotFoundException();

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (empty($this->getRequest()->getData('name')) || empty($this->getRequest()->getData('type')) || empty($this->getRequest()->getData('url')) || $this->getRequest()->getData('url') === "undefined")
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));

        $open_new_tab = ($this->getRequest()->getData('open_new_tab') == 'true') ? 1 : 0;

        $this->Navbar = TableRegistry::getTableLocator()->get('Navbar');
        $nav = $this->Navbar->get($id);
        $nav->set($this->extracted([
            'name' => $this->getRequest()->getData('name'),
            'icon' => $this->getRequest()->getData('icon'),
            'type' => $this->getRequest()->getData('type'),
            'url' => $this->getRequest()->getData('url'),
            'open_new_tab' => $open_new_tab
        ]));
        $this->Navbar->save($nav);

        $this->History->set('EDIT_NAV', 'navbar');

        $this->Flash->success($this->Lang->get('NAVBAR__EDIT_SUCCESS'));
        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('NAVBAR__EDIT_SUCCESS')]));
    }

    /**
     * @param array $data
     * @throws \Exception
     */
    public function extracted(array $data)
    {
        if ($data['type'] == "dropdown") {
            $data['type'] = 2;
            $data['url'] = json_encode(['type' => 'submenu']);
            $data['submenu'] = json_encode($data['url']);
        } else {
            $data['type'] = 1;
        }

        return $data;
    }

}
