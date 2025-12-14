<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Datasource\Exception\RecordNotFoundException;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;

/**
 * @property \App\Controller\Component\HistoryComponent $History
 */
class NavbarController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Auth->can('MANAGE_NAV')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('NAVBAR__TITLE'));

        $navbarTable = $this->fetchTable('Navbars');
        $navbars = $navbarTable
            ->find()
            ->orderBy(['order_by'])
            ->toArray();

        $pageTable = $this->fetchTable('Pages');
        $pages = $pageTable
            ->find('all')
            ->select(['id', 'slug'])
            ->all();

        $pagesListed = [];
        foreach ($pages as $page) {
            $pagesListed[$page['id']] = $page['slug'];
        }

        foreach ($navbars as $key => $nav) {
            $urlData = $nav['urlData'] ?? [];

            if (!is_array($urlData) || !isset($urlData['type'])) {
                $navbars[$key]['url'] = '#';
                continue;
            }

            if ($urlData['type'] === 'plugin') {
                $plugin = null;

                if (isset($urlData['route'])) {
                    $plugin = $this->addons->findPlugin('slug', $urlData['id'] ?? null);
                } else {
                    $plugin = $this->addons->findPlugin('DBid', $urlData['id'] ?? null);
                }

                if (!empty($plugin)) {
                    if (isset($urlData['route'])) {
                        $navbars[$key]['url'] = Router::url($urlData['route']);
                    } else {
                        $navbars[$key]['url'] = Router::url('/' . strtolower((string)$plugin->slug));
                    }
                } else {
                    $navbars[$key]['url'] = false;
                }
            } elseif ($urlData['type'] === 'page') {
                $pageId = $urlData['id'] ?? null;
                if ($pageId !== null && isset($pagesListed[$pageId])) {
                    $navbars[$key]['url'] = Router::url(['_name' => 'pages_index', $pagesListed[$pageId]]);
                } else {
                    $navbars[$key]['url'] = '#';
                }
            } elseif ($urlData['type'] === 'custom') {
                $navbars[$key]['url'] = $urlData['url'] ?? '#';
            } else {
                $navbars[$key]['url'] = '#';
            }
        }

        $this->set('navbars', $navbars);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Navbar')
            ->setTemplate('index');

        return null;
    }

    public function saveAjax(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NAV'))) {
            return $this->redirect(['_name' => 'home']);
        }

        $request = $this->getRequest();

        if (!$request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $raw = (string)$request->getData('navbar_order', '');
        if ($raw === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $pairs = explode('&', $raw);
        $orders = [];
        $position = 1;

        foreach ($pairs as $pair) {
            $parts = explode('=', $pair, 2);
            if (!isset($parts[0]) || $parts[0] === '') {
                continue;
            }

            $key = $parts[0];
            if (substr($key, -2) === '[]') {
                $key = substr($key, 0, -2);
            }

            $orders[$key] = $position;
            $position++;
        }

        if (!$orders) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $navbarTable = $this->fetchTable('Navbars');
        $error = false;

        foreach ($orders as $id => $orderBy) {
            $entity = $navbarTable
                ->find()
                ->where(['id' => $id])
                ->first();

            if ($entity === null) {
                $error = true;
                continue;
            }

            $entity->set('order_by', $orderBy);
            $navbarTable->save($entity);
        }

        if ($error) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('NAVBAR__SAVE_SUCCESS'),
        ]));
    }

    public function delete(int|string|null $id = null): Response
    {
        $this->disableAutoRender();

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_NAV'))) {
            return $this->redirect(['_name' => 'home']);
        }

        if ($id === null) {
            return $this->redirect(['_name' => 'admin_navbar_index']);
        }

        $navbarTable = $this->fetchTable('Navbars');

        try {
            $nav = $navbarTable->get($id);
        } catch (RecordNotFoundException) {
            throw new NotFoundException();
        }

        if ($navbarTable->delete($nav)) {
            $this->History->set('DELETE_NAV', 'navbar');
            $this->Flash->success(__('NAVBAR__DELETE_SUCCESS'));
        }

        return $this->redirect(['_name' => 'admin_navbar_index']);
    }

    public function add(): ?Response
    {
        if (!$this->Auth->can('MANAGE_NAV')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('NAVBAR__ADD_LINK'));

        $pageTable = $this->fetchTable('Pages');
        $pages = $pageTable
            ->find()
            ->select(['id', 'title'])
            ->all();

        $urlPages = [];
        foreach ($pages as $page) {
            $urlPages[$page['id']] = $page['title'];
        }

        $this->set('url_pages', $urlPages);
        $this->set('url_plugins', $this->addons->findPluginsLinks());

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Navbar')
            ->setTemplate('add');

        return null;
    }

    public function addAjax(): Response
    {
        if (!$this->Auth->can('MANAGE_NAV')) {
            throw new ForbiddenException();
        }

        if (!$this->getRequest()->is('ajax')) {
            throw new NotFoundException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $request = $this->getRequest();

        $name = (string)$request->getData('name', '');
        $type = (string)$request->getData('type', '');
        $url = $request->getData('url');
        $icon = (string)$request->getData('icon', '');
        $openNewTabRaw = (string)$request->getData('open_new_tab', '');

        if ($name === '' || $type === '' || $url === null || $url === '' || $url === 'undefined') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $navbarTable = $this->fetchTable('Navbars');

        $last = $navbarTable
            ->find()
            ->orderBy(['order_by' => 'DESC'])
            ->first();

        $order = $last === null ? 1 : (int)$last['order_by'] + 1;
        $openNewTab = $openNewTabRaw === 'true' ? 1 : 0;

        $data = [
            'order_by' => $order,
            'name' => $name,
            'icon' => $icon,
            'type' => $type,
            'url' => $url,
            'open_new_tab' => $openNewTab,
        ];

        $data = $this->extracted($data);

        $entity = $navbarTable->newEntity($data);
        $navbarTable->save($entity);

        $this->History->set('ADD_NAV', 'navbar');
        $this->Flash->success(__('NAVBAR__ADD_SUCCESS'));

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('NAVBAR__ADD_SUCCESS'),
        ]));
    }

    public function edit(int|string|null $id = null): ?Response
    {
        if (!$this->Auth->can('MANAGE_NAV')) {
            throw new ForbiddenException();
        }

        if ($id === null) {
            throw new NotFoundException();
        }

        $navbarTable = $this->fetchTable('Navbars');
        $nav = $navbarTable
            ->find()
            ->where(['id' => $id])
            ->first();

        if ($nav === null) {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('NAVBAR__EDIT_TITLE'));

        $pageTable = $this->fetchTable('Pages');
        $pages = $pageTable
            ->find()
            ->select(['id', 'title'])
            ->all();

        $urlPages = [];
        foreach ($pages as $page) {
            $urlPages[$page['id']] = $page['title'];
        }

        $this->set('url_pages', $urlPages);
        $this->set('nav', $nav);
        $this->set('url_plugins', $this->addons->findPluginsLinks());

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Navbar')
            ->setTemplate('edit');

        return null;
    }

    public function editAjax(int|string $id): Response
    {
        if (!$this->Auth->can('MANAGE_NAV')) {
            throw new ForbiddenException();
        }

        if (!$this->getRequest()->is('ajax')) {
            throw new NotFoundException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $request = $this->getRequest();

        $name = (string)$request->getData('name', '');
        $type = (string)$request->getData('type', '');
        $url = $request->getData('url');
        $icon = (string)$request->getData('icon', '');
        $openNewTabRaw = (string)$request->getData('open_new_tab', '');

        if ($name === '' || $type === '' || $url === null || $url === '' || $url === 'undefined') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $openNewTab = $openNewTabRaw === 'true' ? 1 : 0;

        $navbarTable = $this->fetchTable('Navbars');
        $nav = $navbarTable->get($id);

        $data = [
            'name' => $name,
            'icon' => $icon,
            'type' => $type,
            'url' => $url,
            'open_new_tab' => $openNewTab,
        ];

        $data = $this->extracted($data);

        $nav->set($data);
        $navbarTable->save($nav);

        $this->History->set('EDIT_NAV', 'navbar');
        $this->Flash->success(__('NAVBAR__EDIT_SUCCESS'));

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('NAVBAR__EDIT_SUCCESS'),
        ]));
    }

    private function extracted(array $data): array
    {
        if (($data['type'] ?? '') === 'dropdown') {
            $data['type'] = 2;
            $data['url'] = json_encode(['type' => 'submenu']);
            $data['submenu'] = json_encode($data['url']);
        } else {
            $data['type'] = 1;
        }

        return $data;
    }
}
