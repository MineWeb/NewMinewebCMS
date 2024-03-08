<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Routing\Router;

class SeoController extends AppController
{
    public function index()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SEO'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('SEO__TITLE'));

        $default = $this->Seo->find('all', ["conditions" => ['page IS NULL']])->first();
        $seo_other = $this->Seo->find('all', ["conditions" => ['page IS NOT NULL']])->all();
        $this->set(compact('default', 'seo_other'));
    }

    public function editDefault()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SEO') || !$this->request->is('post'))
            throw new ForbiddenException();

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $default = $this->Seo->find('all', ["conditions" => ['page IS NULL']])->first();
        if (!$this->getRequest()->getData('img_edit')) {
            $already_uploaded = ($this->getRequest()->getData('img-uploaded') !== null);
            if ($already_uploaded) {
                $this->request = $this->getRequest()->withData('favicon_url', Router::url('/') . 'img' . DS . 'uploads' . DS . $this->getRequest()->getData('img-uploaded'));
            } else {
                $isValidImg = $this->Util->isValidImage($this->getRequest(), ['png', 'jpg', 'jpeg']);
                if (!$isValidImg['status']) {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $isValidImg['msg']]));
                } else {
                    $infos = $isValidImg['infos'];
                }

                $time = date('Y-m-d_His');

                $url_img = WWW_ROOT . 'img' . DS . 'uploads' . DS . 'favicons' . DS . $time . '.' . $infos['extension'];

                if (!$this->Util->uploadImage($this->request, $url_img)) {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')]));
                }
                $this->request = $this->getRequest()->withData('favicon_url', Router::url('/') . 'img' . DS . 'uploads' . DS . 'favicons' . DS . $time . '.' . $infos['extension']);
            }
        }


        if (empty($default))
            $seo = $this->Seo->newEmptyEntity();
        else
            $seo = $this->Seo->get($default['id']);

        $seo->set($this->getRequest()->getData());
        $this->Seo->save($seo);

        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SEO__EDIT_SUCCESS')]));
    }


    public function add()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SEO'))
            throw new ForbiddenException();
        $this->set('title_for_layout', $this->Lang->get('SEO__TITLE'));
        $this->layout = 'admin';
        if ($this->request->is('post')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');
            if (!$this->request->getData('img_edit')) {
                $already_uploaded = ($this->getRequest()->getData('img-uploaded') !== null);
                if ($already_uploaded) {
                    $this->request = $this->request->withData('favicon_url', Router::url('/') . 'img' . DS . 'uploads' . DS . $this->request->getData('img-uploaded'));
                } else {
                    $isValidImg = $this->Util->isValidImage($this->getRequest(), ['png', 'jpg', 'jpeg']);
                    if (!$isValidImg['status']) {
                        return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $isValidImg['msg']]));
                    } else {
                        $infos = $isValidImg['infos'];
                    }

                    $time = date('Y-m-d_His');

                    $url_img = WWW_ROOT . 'img' . DS . 'uploads' . DS . 'favicons' . DS . $time . '.' . $infos['extension'];

                    if (!$this->Util->uploadImage($this->request, $url_img)) {
                        return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')]));
                    }
                    $this->request = $this->getRequest()->withData('favicon_url', Router::url('/') . 'img' . DS . 'uploads' . DS . 'favicons' . DS . $time . '.' . $infos['extension']);
                }
            }

            if (empty($this->getRequest()->getData('page')) || (empty($this->getRequest()->getData('title')) && empty($this->getRequest()->getData('description')) && empty($this->getRequest()->getData('favicon_url')) && empty($this->getRequest()->getData('img-url'))))
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));

            $seo = $this->Seo->newEntity($this->getRequest()->getData());
            $this->Seo->save($seo);

            return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SEO__PAGE_ADD_SUCCESS')]));
        }
    }


    public function edit($id = false)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SEO') || !$id)
            throw new ForbiddenException();
        $this->set('title_for_layout', $this->Lang->get('SEO__TITLE'));
        $this->layout = 'admin';
        $page = $this->Seo->find('all', ["conditions" => ['id' => $id]])->first();
        $this->set(compact('page'));

        if ($this->request->is('post')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');

            if (!$this->request->getData('img_edit')) {
                $already_uploaded = ($this->getRequest()->getData('img-uploaded') !== null);
                if ($already_uploaded) {
                    $this->request = $this->request->withData('favicon_url', Router::url('/') . 'img' . DS . 'uploads' . DS . $this->request->getData('img-uploaded'));
                } else {
                    $isValidImg = $this->Util->isValidImage($this->getRequest(), ['png', 'jpg', 'jpeg']);
                    if (!$isValidImg['status']) {
                        return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $isValidImg['msg']]));
                    } else {
                        $infos = $isValidImg['infos'];
                    }

                    $time = date('Y-m-d_His');

                    $url_img = WWW_ROOT . 'img' . DS . 'uploads' . DS . 'favicons' . DS . $time . '.' . $infos['extension'];

                    if (!$this->Util->uploadImage($this->request, $url_img)) {
                        return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')]));
                    }
                    $this->request = $this->getRequest()->withData('favicon_url', Router::url('/') . 'img' . DS . 'uploads' . DS . 'favicons' . DS . $time . '.' . $infos['extension']);
                }
            }

            if (empty($this->getRequest()->getData('page')) || (empty($this->getRequest()->getData('title')) && empty($this->getRequest()->getData('description')) && empty($this->getRequest()->getData('favicon_url')) && empty($this->getRequest()->getData('img-url'))))
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));

            $seo = $this->Seo->get($page['id']);
            $seo->set($this->request->getData());
            $this->Seo->save($seo);

            return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SEO__EDIT_SUCCESS')]));
        }
    }

    public function delete($id = false)
    {
        $this->disableAutoRender();
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SEO') || !$id)
            throw new ForbiddenException();

        $this->Seo->delete($this->Seo->get($id));
        $this->Flash->success($this->Lang->get('SEO__PAGE_DELETE_SUCCESS'));
        $this->redirect(['controller' => 'seo', 'action' => 'index', 'admin' => true]);
    }

}
