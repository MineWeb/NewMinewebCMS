<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class SeoController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SEO')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SEO__TITLE'));

        $seoTable = $this->fetchTable('Seo');

        $default = $seoTable->find()->where(['page IS' => null])->first();

        $seo_other = $seoTable->find()->where(['page IS NOT' => null])->all();

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

        $default = $seoTable->find()->where(['page IS' => null])->first();

        $request = $this->getRequest();

        $faviconResult = $this->Util->handleImageField($request, 'favicon', 'favicons', ['png', 'jpg', 'jpeg']);
        if (($faviconResult['status'] ?? false) === false) {
            return $this->jsonError($faviconResult['messages'] ?? __('FORM__ERROR_WHEN_SAVE'));
        }

        $faviconDelete = (int)($request->getData('favicon.delete') ?? 0) === 1;
        if ($faviconDelete) {
            $request = $request->withData('favicon_url', '');
            $this->setRequest($request);
        } elseif (!empty($faviconResult['url'])) {
            $request = $request->withData('favicon_url', $faviconResult['url']);
            $this->setRequest($request);
        }

        $seo = $default === null ? $seoTable->newEmptyEntity() : $seoTable->get((int)$default['id']);

        $seo = $seoTable->patchEntity($seo, $this->getRequest()->getData());

        if (!$seoTable->save($seo)) {
            return $this->jsonError($seo->getErrors() ?: __('FORM__ERROR_WHEN_SAVE'));
        }

        return $this->jsonOk(__('SEO__EDIT_SUCCESS'));
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

        $faviconResult = $this->Util->handleImageField($request, 'favicon', 'favicons', ['png', 'jpg', 'jpeg']);
        if (($faviconResult['status'] ?? false) === false) {
            return $this->jsonError($faviconResult['messages'] ?? __('FORM__ERROR_WHEN_SAVE'));
        }

        $faviconDelete = (int)($request->getData('favicon.delete') ?? 0) === 1;
        if ($faviconDelete) {
            $request = $request->withData('favicon_url', '');
            $this->setRequest($request);
        } elseif (!empty($faviconResult['url'])) {
            $request = $request->withData('favicon_url', $faviconResult['url']);
            $this->setRequest($request);
        }

        $page = (string)$this->getRequest()->getData('page', '');

        if (
            $page === ''
            || (
                (string)$this->getRequest()->getData('title', '') === ''
                && (string)$this->getRequest()->getData('description', '') === ''
                && (string)$this->getRequest()->getData('favicon_url', '') === ''
                && (string)$this->getRequest()->getData('img_url', '') === ''
            )
        ) {
            return $this->jsonError(__('ERROR__FILL_ALL_FIELDS'));
        }

        $seoTable = $this->fetchTable('Seo');
        $entity = $seoTable->newEntity($this->getRequest()->getData());

        if (!$seoTable->save($entity)) {
            return $this->jsonError($entity->getErrors() ?: __('FORM__ERROR_WHEN_SAVE'));
        }

        return $this->jsonOk(__('SEO__PAGE_ADD_SUCCESS'));
    }

    public function edit(int|string|null $id = null): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SEO') || $id === null) {
            throw new ForbiddenException();
        }

        $seoTable = $this->fetchTable('Seo');

        $page = $seoTable->find()->where(['id' => $id])->first();
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

        $faviconResult = $this->Util->handleImageField($request, 'favicon', 'favicons', ['png', 'jpg', 'jpeg']);
        if (($faviconResult['status'] ?? false) === false) {
            return $this->jsonError($faviconResult['messages'] ?? __('FORM__ERROR_WHEN_SAVE'));
        }

        $faviconDelete = (int)($request->getData('favicon.delete') ?? 0) === 1;
        if ($faviconDelete) {
            $request = $request->withData('favicon_url', '');
            $this->setRequest($request);
        } elseif (!empty($faviconResult['url'])) {
            $request = $request->withData('favicon_url', $faviconResult['url']);
            $this->setRequest($request);
        }

        $data = $this->getRequest()->getData();

        if (
            empty($data['page'])
            || (
                empty($data['title'])
                && empty($data['description'])
                && empty($data['favicon_url'])
                && empty($data['img_url'])
            )
        ) {
            return $this->jsonError(__('ERROR__FILL_ALL_FIELDS'));
        }

        $seo = $seoTable->get((int)$page['id']);
        $seo = $seoTable->patchEntity($seo, $data);

        if (!$seoTable->save($seo)) {
            return $this->jsonError($seo->getErrors() ?: __('FORM__ERROR_WHEN_SAVE'));
        }

        return $this->jsonOk(__('SEO__EDIT_SUCCESS'));
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

        return $this->redirect(['_name' => 'admin_seo_index']);
    }

    private function jsonOk(string $message): Response
    {
        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => $message,
        ]));
    }

    private function jsonError(mixed $message): Response
    {
        return $this->response->withStringBody(json_encode([
            'status' => false,
            'messages' => $message,
        ]));
    }
}
