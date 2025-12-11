<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Utility\Text;

class NewsController extends AppController
{
    public function index(): ?Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NEWS'))) {
            return $this->redirect(['_name' => 'home']);
        }

        $this->set('title_for_layout', __('NEWS__LIST_PUBLISHED'));

        $newsTable = $this->fetchTable('News');
        $view_news = $newsTable->find()->all();

        $this->set(compact('view_news'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/News')
            ->setTemplate('index');

        return null;
    }

    public function delete(int|string|null $id = null): Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NEWS'))) {
            return $this->redirect(['_name' => 'home']);
        }

        if ($id === null) {
            return $this->redirect(['_name' => 'admin_news_index']);
        }

        $event = new Event('beforeDeleteNews', $this, [
            'news_id' => $id,
            'user' => $this->User->getAllFromCurrentUser(),
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->redirect(['_name' => 'admin_news_index']);
        }

        $newsTable = $this->fetchTable('News');
        $likesTable = $this->fetchTable('Likes');
        $commentTable = $this->fetchTable('Comments');

        $entity = $newsTable->get($id);
        if ($newsTable->delete($entity)) {
            $likesTable->deleteAll(['Likes.news_id' => $id]);
            $commentTable->deleteAll(['Comment.news_id' => $id]);

            $this->History->set('DELETE_NEWS', 'news');
            $this->Flash->success(__('NEWS__SUCCESS_DELETE'));
        }

        return $this->redirect(['_name' => 'admin_news_index']);
    }

    public function add(): ?Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NEWS'))) {
            return $this->redirect(['_name' => 'home']);
        }

        $this->set('title_for_layout', __('NEWS__ADD_NEWS'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/News')
            ->setTemplate('add');

        return null;
    }

    public function addAjax(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->isConnected && $this->Permissions->can('MANAGE_NEWS'))) {
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
        $content = (string)$request->getData('content', '');
        $slugRaw = (string)$request->getData('slug', '');
        $published = $request->getData('published');

        if ($title === '' || $content === '' || $slugRaw === '') {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $event = new Event('beforeAddNews', $this, [
            'news' => $request->getData(),
            'user' => $this->User->getAllFromCurrentUser(),
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $newsTable = $this->fetchTable('News');

        $entity = $newsTable->newEntity([
            'title' => $title,
            'content' => $content,
            'user_id' => $this->User->getKey('id'),
            'updated' => date('Y-m-d H:i:s'),
            'comments' => 0,
            'likes' => 0,
            'img' => 0,
            'slug' => Text::slug($slugRaw, '-'),
            'published' => $published,
        ]);

        $newsTable->save($entity);

        $this->History->set('ADD_NEWS', 'news');
        $this->Flash->success(__('NEWS__SUCCESS_ADD'));

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('NEWS__SUCCESS_ADD'),
        ]));
    }

    public function edit(int|string|null $id = null): ?Response
    {
        if (!($this->isConnected && $this->Permissions->can('MANAGE_NEWS'))) {
            return $this->redirect(['_name' => 'home']);
        }

        if ($id === null) {
            throw new NotFoundException();
        }

        $newsTable = $this->fetchTable('News');
        $news = $newsTable
            ->find()
            ->where(['id' => $id])
            ->first();

        if ($news === null) {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('NEWS__EDIT'));
        $this->set('news', $news);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/News')
            ->setTemplate('edit');

        return null;
    }

    public function editAjax(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->isConnected && $this->Permissions->can('MANAGE_NEWS'))) {
            throw new ForbiddenException();
        }

        $request = $this->getRequest();

        if (!$request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $id = $request->getData('id');
        $title = (string)$request->getData('title', '');
        $content = (string)$request->getData('content', '');
        $slugRaw = (string)$request->getData('slug', '');
        $published = $request->getData('published');

        if ($id === null || $title === '' || $content === '' || $slugRaw === '') {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $event = new Event('beforeEditNews', $this, [
            'news' => $request->getData(),
            'news_id' => $id,
            'user' => $this->User->getAllFromCurrentUser(),
        ]);
        $this->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            $result = $event->getResult();
            if ($result instanceof Response) {
                return $result;
            }

            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $newsTable = $this->fetchTable('News');
        $entity = $newsTable->get($id);

        $entity->set([
            'title' => $title,
            'content' => $content,
            'updated' => date('Y-m-d H:i:s'),
            'slug' => Text::slug($slugRaw, '-'),
            'published' => $published,
        ]);

        $newsTable->save($entity);

        $this->History->set('EDIT_NEWS', 'news');
        $this->Flash->success(__('NEWS__SUCCESS_EDIT'));

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('NEWS__SUCCESS_EDIT'),
        ]));
    }
}
