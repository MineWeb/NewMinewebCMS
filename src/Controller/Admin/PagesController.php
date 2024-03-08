<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Utility\Text;

class PagesController extends AppController {
    public function index()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_PAGE')) {

            $this->set('title_for_layout', $this->Lang->get('PAGE__LIST'));
            $this->layout = 'admin';
            $this->Page = TableRegistry::getTableLocator()->get('Page');
            $pages = $this->Page->find()->toArray();
            foreach ($pages as $pageid => $page) {
                $pages[$pageid]['author'] = $this->User->getFromUser('pseudo', $page['user_id']);
            }
            $this->set(compact('pages'));
        } else {
            $this->redirect('/');
        }
    }

    public function add()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_PAGE')) {

            $this->set('title_for_layout', $this->Lang->get('PAGE__ADD'));
            $this->layout = 'admin';
        } else {
            $this->redirect('/');
        }
    }

    public function addAjax()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_PAGE')) {
            if ($this->request->is('post')) {
                if (!empty($this->request->getData('title')) and !empty($this->request->getData('slug')) and !empty($this->request->getData('content'))) {
                    $this->Page = TableRegistry::getTableLocator()->get('Page');
                    $newPage = $this->Page->newEntity([
                        'title' => $this->request->getData('title'),
                        'content' => $this->request->getData('content'),
                        'user_id' => $this->User->getKey('id'),
                        'slug' => Text::slug($this->request->getData('slug')),
                        'updated' => date('Y-m-d H:i:s'),
                    ]);
                    $this->Page->save($newPage);
                    $this->History->set('ADD_PAGE', 'page');
                    $this->Flash->success($this->Lang->get('PAGE__ADD_SUCCESS'));
                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('PAGE__ADD_SUCCESS')]));
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

    public function delete($id = false)
    {
        $this->disableAutoRender();
        if ($this->isConnected and $this->Permissions->can('MANAGE_PAGE')) {
            if ($id) {
                $this->Page = TableRegistry::getTableLocator()->get('Page');
                if ($this->Page->delete($this->Page->get($id))) {
                    $this->History->set('DELETE_PAGE', 'page');
                    $this->Flash->success($this->Lang->get('PAGE__DELETE_SUCCESS'));
                }
            }

            $this->redirect(['controller' => 'pages', 'action' => 'index', 'admin' => true]);
        } else {
            $this->redirect('/admin/pages');
        }
    }

    public function edit($id = false)
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_PAGE')) {
            if ($id) {
                $this->set('title_for_layout', $this->Lang->get('PAGE__EDIT'));
                $this->layout = 'admin';
                $this->Page = TableRegistry::getTableLocator()->get('Page');
                $page = $this->Page->find('all', conditions: ['id' => $id])->first();
                if (!empty($page)) {
                    $this->set(compact('page'));
                } else {
                    $this->redirect('/admin/pages');
                }
            } else {
                $this->redirect('/admin/pages');
            }
        } else {
            $this->redirect('/');
        }
    }

    public function editAjax()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_PAGE')) {
            if ($this->request->is('post')) {
                if (!empty($this->request->getData('id')) and !empty($this->request->getData('title')) and !empty($this->request->getData('slug')) and !empty($this->request->getData('content'))) {
                    $this->Page = TableRegistry::getTableLocator()->get('Page');
                    $page = $this->Page->get($this->request->getData('id'));
                    $page->set([
                        'title' => $this->request->getData('title'),
                        'content' => $this->request->getData('content'),
                        'slug' => Text::slug($this->request->getData('slug')),
                        'updated' => date('Y-m-d H:i:s'),
                    ]);
                    $this->Page->save($page);
                    $this->History->set('EDIT_PAGE', 'page');
                    $this->Flash->success($this->Lang->get('PAGE__EDIT_SUCCESS'));
                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('PAGE__EDIT_SUCCESS')]));
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
}
