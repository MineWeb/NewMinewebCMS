<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class SliderController extends AppController
{
    public function index(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SLIDER')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SLIDER__ADD'));

        $sliderTable = $this->fetchTable('Sliders');
        $sliders = $sliderTable->find()->all();

        $this->set(compact('sliders'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Slider')
            ->setTemplate('index');

        return null;
    }

    public function add(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SLIDER')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SLIDER__ADD'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Slider')
            ->setTemplate('add');

        return null;
    }

    public function addAjax(): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SLIDER') || !$this->getRequest()->is('post')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $request = $this->getRequest();

        $title = (string)$request->getData('title', '');
        $subtitle = (string)$request->getData('subtitle', '');

        if ($title === '' || $subtitle === '') {
            return $this->jsonError(__('ERROR__FILL_ALL_FIELDS'));
        }

        $imgResult = $this->Util->handleImageField($request, 'img', 'slider', ['png', 'jpg', 'jpeg']);
        if (($imgResult['status'] ?? false) === false) {
            return $this->jsonError($imgResult['messages'] ?? __('FORM__ERROR_WHEN_SAVE'));
        }

        $imgDelete = (int)($request->getData('img.delete') ?? 0) === 1;
        if ($imgDelete) {
            return $this->jsonError(__('ERROR__FILL_ALL_FIELDS'));
        }

        if (empty($imgResult['url'])) {
            return $this->jsonError(__('ERROR__FILL_ALL_FIELDS'));
        }

        $sliderTable = $this->fetchTable('Sliders');
        $slider = $sliderTable->newEntity([
            'title' => $title,
            'subtitle' => $subtitle,
            'url_img' => (string)$imgResult['url'],
        ]);

        if (!$sliderTable->save($slider)) {
            return $this->jsonError($slider->getErrors() ?: __('FORM__ERROR_WHEN_SAVE'));
        }

        $this->History->set('ADD_SLIDER', 'slider');
        $this->Flash->success(__('SLIDER__ADD_SUCCESS'));

        return $this->jsonOk(__('SLIDER__ADD_SUCCESS'));
    }

    public function edit(int|string|null $id = null): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SLIDER') || $id === null) {
            throw new ForbiddenException();
        }

        $sliderTable = $this->fetchTable('Sliders');
        $slider = $sliderTable->find()->where(['id' => $id])->first();

        if ($slider === null) {
            throw new NotFoundException();
        }

        $parts = explode('/', (string)$slider['url_img']);
        $slider['filename'] = end($parts);

        $this->set('title_for_layout', __('SLIDER__EDIT'));
        $this->set('slider', $slider);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Slider')
            ->setTemplate('edit');

        return null;
    }

    public function editAjax(): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SLIDER') || !$this->getRequest()->is('post')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $request = $this->getRequest();

        $id = $request->getData('id');
        $title = (string)$request->getData('title', '');
        $subtitle = (string)$request->getData('subtitle', '');

        if ($id === null || $title === '' || $subtitle === '') {
            return $this->jsonError(__('ERROR__FILL_ALL_FIELDS'));
        }

        $sliderTable = $this->fetchTable('Sliders');
        $slider = $sliderTable->find()->where(['id' => $id])->first();

        if ($slider === null) {
            throw new NotFoundException();
        }

        $data = [
            'title' => $title,
            'subtitle' => $subtitle,
        ];

        $imgEdit = (int)($request->getData('img.edit') ?? 0) === 1;
        if ($imgEdit) {
            $imgResult = $this->Util->handleImageField($request, 'img', 'slider', ['png', 'jpg', 'jpeg']);
            if (($imgResult['status'] ?? false) === false) {
                return $this->jsonError($imgResult['messages'] ?? __('FORM__ERROR_WHEN_SAVE'));
            }

            $imgDelete = (int)($request->getData('img.delete') ?? 0) === 1;

            if ($imgDelete) {
                $data['url_img'] = '';
            } elseif (!empty($imgResult['url'])) {
                $data['url_img'] = (string)$imgResult['url'];
            }
        }

        $entity = $sliderTable->get((int)$slider['id']);
        $entity = $sliderTable->patchEntity($entity, $data);

        if (!$sliderTable->save($entity)) {
            return $this->jsonError($entity->getErrors() ?: __('FORM__ERROR_WHEN_SAVE'));
        }

        $this->History->set('EDIT_SLIDER', 'slider');
        $this->Flash->success(__('SLIDER__EDIT_SUCCESS'));

        return $this->jsonOk(__('SLIDER__EDIT_SUCCESS'));
    }

    public function delete(int|string|null $id = null): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SLIDER')) {
            throw new ForbiddenException();
        }

        if ($id !== null) {
            $sliderTable = $this->fetchTable('Sliders');
            $slider = $sliderTable->find()->where(['id' => $id])->first();

            if ($slider) {
                $sliderTable->delete($slider);
                $this->History->set('DELETE_SLIDER', 'slider');
                $this->Flash->success(__('SLIDER__DELETE_SUCCESS'));
            } else {
                $this->Flash->error(__('UNKNONW_ID'));
            }
        }

        return $this->redirect(['_name' => 'admin_slider_index']);
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
