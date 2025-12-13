<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;

class SeoController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SEO')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SEO__TITLE'));

        $seoTable = $this->fetchTable('Seo');

        $default = $seoTable
            ->find()
            ->where(['page IS' => null])
            ->first();

        $seo_other = $seoTable
            ->find()
            ->where(['page IS NOT' => null])
            ->all();

        $this->set(compact('default', 'seo_other'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Seo')
            ->setTemplate('index');

        return null;
    }

    public function editDefault(): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SEO') || !$this->getRequest()->is('post')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $seoTable = $this->fetchTable('Seo');

        $default = $seoTable
            ->find()
            ->where(['page IS' => null])
            ->first();

        $request = $this->getRequest();

        if (!$request->getData('img_edit')) {
            $alreadyUploaded = $request->getData('img-uploaded') !== null;

            if ($alreadyUploaded) {
                $request = $request->withData(
                    'favicon_url',
                    Router::url('/') . 'img' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $request->getData('img-uploaded')
                );
                $this->setRequest($request);
            } else {
                $isValidImg = $this->Util->isValidImage($request, ['png', 'jpg', 'jpeg']);
                if (!$isValidImg['status']) {
                    return $this->response->withStringBody(json_encode([
                        'status' => false,
                        'messages' => $isValidImg['msg'],
                    ]));
                }

                $infos = $isValidImg['infos'];
                $time = date('Y-m-d_His');

                $filePath = WWW_ROOT . 'img' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'favicons' . DIRECTORY_SEPARATOR . $time . '.' . $infos['extension'];

                if (!$this->Util->uploadImage($request, $filePath)) {
                    return $this->response->withStringBody(json_encode([
                        'status' => false,
                        'messages' => __('FORM__ERROR_WHEN_UPLOAD'),
                    ]));
                }

                $request = $request->withData(
                    'favicon_url',
                    Router::url('/') . 'img' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'favicons' . DIRECTORY_SEPARATOR . $time . '.' . $infos['extension']
                );
                $this->setRequest($request);
            }
        }

        if ($default === null) {
            $seo = $seoTable->newEmptyEntity();
        } else {
            $seo = $seoTable->get($default['id']);
        }

        $seo->set($this->getRequest()->getData());
        $seoTable->save($seo);

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SEO__EDIT_SUCCESS'),
        ]));
    }

    public function add(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SEO')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SEO__TITLE'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Seo')
            ->setTemplate('add');

        $request = $this->getRequest();

        if (!$request->is('post')) {
            return null;
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$request->getData('img_edit')) {
            $alreadyUploaded = $request->getData('img-uploaded') !== null;

            if ($alreadyUploaded) {
                $request = $request->withData(
                    'favicon_url',
                    Router::url('/') . 'img' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $request->getData('img-uploaded')
                );
                $this->setRequest($request);
            } else {
                $isValidImg = $this->Util->isValidImage($request, ['png', 'jpg', 'jpeg']);
                if (!$isValidImg['status']) {
                    return $this->response->withStringBody(json_encode([
                        'status' => false,
                        'messages' => $isValidImg['msg'],
                    ]));
                }

                $infos = $isValidImg['infos'];
                $time = date('Y-m-d_His');

                $filePath = WWW_ROOT . 'img' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'favicons' . DIRECTORY_SEPARATOR . $time . '.' . $infos['extension'];

                if (!$this->Util->uploadImage($request, $filePath)) {
                    return $this->response->withStringBody(json_encode([
                        'status' => false,
                        'messages' => __('FORM__ERROR_WHEN_UPLOAD'),
                    ]));
                }

                $request = $request->withData(
                    'favicon_url',
                    Router::url('/') . 'img' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'favicons' . DIRECTORY_SEPARATOR . $time . '.' . $infos['extension']
                );
                $this->setRequest($request);
            }
        }

        $page = (string)$this->getRequest()->getData('page', '');

        if ($page === ''
            || (
                (string)$this->getRequest()->getData('title', '') === ''
                && (string)$this->getRequest()->getData('description', '') === ''
                && (string)$this->getRequest()->getData('favicon_url', '') === ''
                && (string)$this->getRequest()->getData('img-url', '') === ''
            )
        ) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $seoTable = $this->fetchTable('Seo');
        $entity = $seoTable->newEntity($this->getRequest()->getData());
        $seoTable->save($entity);

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SEO__PAGE_ADD_SUCCESS'),
        ]));
    }

    public function edit(int|string|null $id = null): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SEO') || $id === null) {
            throw new ForbiddenException();
        }

        $seoTable = $this->fetchTable('Seo');

        $page = $seoTable
            ->find()
            ->where(['id' => $id])
            ->first();

        if ($page === null) {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('SEO__TITLE'));
        $this->set('page', $page);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Seo')
            ->setTemplate('edit');

        $request = $this->getRequest();

        if (!$request->is('post')) {
            return null;
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$request->getData('img_edit')) {
            $alreadyUploaded = $request->getData('img-uploaded') !== null;

            if ($alreadyUploaded) {
                $request = $request->withData(
                    'favicon_url',
                    Router::url('/') . 'img' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . $request->getData('img-uploaded')
                );
                $this->setRequest($request);
            } else {
                $isValidImg = $this->Util->isValidImage($request, ['png', 'jpg', 'jpeg']);
                if (!$isValidImg['status']) {
                    return $this->response->withStringBody(json_encode([
                        'status' => false,
                        'messages' => $isValidImg['msg'],
                    ]));
                }

                $infos = $isValidImg['infos'];
                $time = date('Y-m-d_His');

                $filePath = WWW_ROOT . 'img' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'favicons' . DIRECTORY_SEPARATOR . $time . '.' . $infos['extension'];

                if (!$this->Util->uploadImage($request, $filePath)) {
                    return $this->response->withStringBody(json_encode([
                        'status' => false,
                        'messages' => __('FORM__ERROR_WHEN_UPLOAD'),
                    ]));
                }

                $request = $request->withData(
                    'favicon_url',
                    Router::url('/') . 'img' . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'favicons' . DIRECTORY_SEPARATOR . $time . '.' . $infos['extension']
                );
                $this->setRequest($request);
            }
        }

        $data = $this->getRequest()->getData();

        if (
            empty($data['page'])
            || (
                empty($data['title'])
                && empty($data['description'])
                && empty($data['favicon_url'])
                && empty($data['img-url'])
            )
        ) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $seo = $seoTable->get($page['id']);
        $seo->set($data);
        $seoTable->save($seo);

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SEO__EDIT_SUCCESS'),
        ]));
    }

    public function delete(int|string|null $id = null): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SEO') || $id === null) {
            throw new ForbiddenException();
        }

        $seoTable = $this->fetchTable('Seo');
        $entity = $seoTable->get($id);
        $seoTable->delete($entity);

        $this->Flash->success(__('SEO__PAGE_DELETE_SUCCESS'));

        return $this->redirect([
            '_name' => 'admin_seo_index',
        ]);
    }
}
