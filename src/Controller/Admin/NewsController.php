<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Event\Event;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class NewsController extends AppController {
    function index()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_NEWS')) {
            $this->set('title_for_layout', $this->Lang->get('NEWS__LIST_PUBLISHED'));
            $this->News = TableRegistry::getTableLocator()->get('News');
            $view_news = $this->News->find()->all();
            $this->set(compact('view_news'));
        } else {
            $this->redirect('/');
        }
    }

    function delete($id = false)
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_NEWS')) {
            if ($id) {
                $event = new Event('beforeDeleteNews', $this, ['news_id' => $id, 'user' => $this->User->getAllFromCurrentUser()]);
                $this->getEventManager()->dispatch($event);
                if ($event->isStopped()) {
                    return $event->getResult();
                }

                $this->News = TableRegistry::getTableLocator()->get('News');
                if ($this->News->delete($this->News->get($id))) {
                    $this->Like = TableRegistry::getTableLocator()->get('Likes');
                    $this->Comment = TableRegistry::getTableLocator()->get('Comment');
                    $this->Like->deleteAll(['Likes.news_id' => $id]);
                    $this->Comment->deleteAll(['Comment.news_id' => $id]);
                    $this->History->set('DELETE_NEWS', 'news');
                    $this->Flash->success($this->Lang->get('NEWS__SUCCESS_DELETE'));
                }

                $this->redirect(['controller' => 'news', 'action' => 'index', 'admin' => true]);
            } else {
                $this->redirect(['controller' => 'news', 'action' => 'index', 'admin' => true]);
            }
        } else {
            $this->redirect('/');
        }
    }

    function add()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_NEWS')) {
            $this->set('title_for_layout', $this->Lang->get('NEWS__ADD_NEWS'));
        } else {
            $this->redirect('/');
        }
    }

    function addAjax()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_NEWS')) {
            if ($this->request->is('post')) {
                if (!empty($this->getRequest()->getData('title')) and !empty($this->getRequest()->getData('content')) and !empty($this->getRequest()->getData('slug'))) {
                    $event = new Event('beforeAddNews', $this, ['news' => $this->request->getData(), 'user' => $this->User->getAllFromCurrentUser()]);
                    $this->getEventManager()->dispatch($event);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }

                    $this->News = TableRegistry::getTableLocator()->get('News');
                    $news = $this->News->newEntity([
                        'title' => $this->getRequest()->getData('title'),
                        'content' => $this->getRequest()->getData('content'),
                        'user_id' => $this->User->getKey('id'),
                        'updated' => date('Y-m-d H:i:s'),
                        'comments' => 0,
                        'likes' => 0,
                        'img' => 0,
                        'slug' => Text::slug($this->getRequest()->getData('slug'), '-'),
                        'published' => $this->getRequest()->getData('published')
                    ]);
                    $this->News->save($news);

                    $this->History->set('ADD_NEWS', 'news');
                    $this->Flash->success($this->Lang->get('NEWS__SUCCESS_ADD'));
                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('NEWS__SUCCESS_ADD')]));
                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
                }
            } else {
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')]));
            }
        } else {
            throw new ForbiddenException();
        }
    }

    function edit($id = false)
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_NEWS')) {
            $this->set('title_for_layout', $this->Lang->get('NEWS__EDIT'));
            if ($id) {
                $this->News = TableRegistry::getTableLocator()->get('News');
                $news = $this->News->find('all', conditions: ['id' => $id])->first();
                if ($news != null) {
                    $this->set(compact('news'));
                } else {
                    throw new NotFoundException();
                }
            } else {
                throw new NotFoundException();
            }
        } else {
            $this->redirect('/');
        }
    }

    function editAjax()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_NEWS')) {
            if ($this->request->is('post')) {
                if (!empty($this->getRequest()->getData('title')) and !empty($this->getRequest()->getData('content')) and !empty($this->getRequest()->getData('id')) and !empty($this->getRequest()->getData('slug'))) {

                    $event = new Event('beforeEditNews', $this, ['news' => $this->request->getData(), 'news_id' => $this->getRequest()->getData('id'), 'user' => $this->User->getAllFromCurrentUser()]);
                    $this->getEventManager()->dispatch($event);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }

                    $this->News = TableRegistry::getTableLocator()->get('News');
                    $news = $this->News->get($this->request->getData('id'));
                    $news->set([
                        'title' => $this->getRequest()->getData('title'),
                        'content' => $this->getRequest()->getData('content'),
                        'updated' => date('Y-m-d H:i:s'),
                        'slug' => Text::slug($this->getRequest()->getData('slug'), '-'),
                        'published' => $this->getRequest()->getData('published')
                    ]);
                    $this->News->save($news);
                    $this->History->set('EDIT_NEWS', 'news');
                    $this->Flash->success($this->Lang->get('NEWS__SUCCESS_EDIT'));
                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('NEWS__SUCCESS_EDIT')]));
                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
                }
            } else {
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')]));
            }
        } else {
            throw new ForbiddenException();
        }

    }
}
