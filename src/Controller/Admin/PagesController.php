<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Utility\Text;

class PagesController extends AppController
{
    public function index(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_PAGE'))) {
            return $this->redirect('/');
        }

        $this->set('title_for_layout', __('PAGE__LIST'));

        $pageTable = $this->fetchTable('Pages');
        $pages = $pageTable->find()->toArray();

        foreach ($pages as $index => $page) {
            $pages[$index]['author'] = $this->User->getFromUser('pseudo', $page['user_id']);
        }

        $this->set('pages', $pages);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Pages')
            ->setTemplate('index');

        return null;
    }

    public function add(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_PAGE'))) {
            return $this->redirect('/');
        }

        $this->set('title_for_layout', __('PAGE__ADD'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Pages')
            ->setTemplate('add');

        return null;
    }

    public function addAjax(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_PAGE'))) {
            throw new ForbiddenException();
        }

        $request = $this->getRequest();

        if (!$request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $title = (string)$request->getData('title', '');
        $slugRaw = (string)$request->getData('slug', '');
        $content = (string)$request->getData('content', '');

        if ($title === '' || $slugRaw === '' || $content === '') {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $pageTable = $this->fetchTable('Pages');

        $entity = $pageTable->newEntity([
            'title' => $title,
            'content' => $content,
            'user_id' => $this->User->getKey('id'),
            'slug' => Text::slug($slugRaw),
            'updated' => date('Y-m-d H:i:s'),
        ]);

        $pageTable->save($entity);

        $this->History->set('ADD_PAGE', 'page');
        $this->Flash->success(__('PAGE__ADD_SUCCESS'));

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('PAGE__ADD_SUCCESS'),
        ]));
    }

    public function delete(int|string|null $id = null): Response
    {
        $this->disableAutoRender();

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_PAGE'))) {
            return $this->redirect(['_name' => 'admin_pages_index']);
        }

        if ($id !== null) {
            $pageTable = $this->fetchTable('Pages');
            $entity = $pageTable->get($id);

            if ($pageTable->delete($entity)) {
                $this->History->set('DELETE_PAGE', 'page');
                $this->Flash->success(__('PAGE__DELETE_SUCCESS'));
            }
        }

        return $this->redirect(['_name' => 'admin_pages_index']);
    }

    public function edit(int|string|null $id = null): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_PAGE'))) {
            return $this->redirect('/');
        }

        if ($id === null) {
            return $this->redirect(['_name' => 'admin_pages_index']);
        }

        $pageTable = $this->fetchTable('Pages');
        $page = $pageTable
            ->find()
            ->where(['id' => $id])
            ->first();

        if ($page === null) {
            return $this->redirect(['_name' => 'admin_pages_index']);
        }

        $this->set('title_for_layout', __('PAGE__EDIT'));
        $this->set('page', $page);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Pages')
            ->setTemplate('edit');

        return null;
    }

    public function editAjax(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_PAGE'))) {
            throw new ForbiddenException();
        }

        $request = $this->getRequest();

        if (!$request->is('post')) {
            throw new NotFoundException();
        }

        $id = $request->getData('id');
        $title = (string)$request->getData('title', '');
        $slugRaw = (string)$request->getData('slug', '');
        $content = (string)$request->getData('content', '');

        if ($id === null || $title === '' || $slugRaw === '' || $content === '') {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $pageTable = $this->fetchTable('Pages');
        $entity = $pageTable->get($id);

        $entity->set([
            'title' => $title,
            'content' => $content,
            'slug' => Text::slug($slugRaw),
            'updated' => date('Y-m-d H:i:s'),
        ]);

        $pageTable->save($entity);

        $this->History->set('EDIT_PAGE', 'page');
        $this->Flash->success(__('PAGE__EDIT_SUCCESS'));

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('PAGE__EDIT_SUCCESS'),
        ]));
    }
}
