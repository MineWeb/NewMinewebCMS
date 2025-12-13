<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;
use Cake\Routing\Router;

class SliderController extends AppController
{
    public function index(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SLIDER'))) {
            return $this->redirect('/');
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

    public function delete(int|string|null $id = null): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SLIDER'))) {
            return $this->redirect('/');
        }

        if ($id !== null) {
            $sliderTable = $this->fetchTable('Sliders');
            $slider = $sliderTable
                ->find()
                ->where(['id' => $id])
                ->first();

            if ($slider) {
                $sliderTable->delete($slider);
                $this->History->set('DELETE_SLIDER', 'slider');
                $this->Flash->success(__('SLIDER__DELETE_SUCCESS'));
            } else {
                $this->Flash->error(__('UNKNONW_ID'));
            }
        }

        return $this->redirect([
            '_name' => 'admin_slider_index',
        ]);
    }

    public function edit(int|string|null $id = null): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SLIDER'))) {
            return $this->redirect('/');
        }

        if ($id === null) {
            throw new NotFoundException();
        }

        $sliderTable = $this->fetchTable('Sliders');
        $slider = $sliderTable
            ->find()
            ->where(['id' => $id])
            ->first();

        if (!$slider) {
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

        return new Response();
    }

    public function editAjax(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SLIDER'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        $request = $this->getRequest();

        if (!$request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $id = $request->getData('id');
        $title = (string)$request->getData('title', '');
        $subtitle = (string)$request->getData('subtitle', '');
        $imgEdit = $request->getData('img_edit');

        if ($id === null || $title === '' || $subtitle === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $data = [
            'title' => $title,
            'subtitle' => $subtitle,
        ];

        if (!$imgEdit) {
            $alreadyUploaded = $request->getData('img-uploaded') !== null;
            if ($alreadyUploaded) {
                $urlImg = Router::url('/') . 'img' . DS . 'uploads' . $request->getData('img-uploaded');
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

                $filePath = WWW_ROOT . 'img' . DS . 'uploads' . DS . 'slider' . DS . $time . '.' . $infos['extension'];

                if (!$this->Util->uploadImage($request, $filePath)) {
                    return $this->response->withStringBody(json_encode([
                        'status' => false,
                        'messages' => __('FORM__ERROR_WHEN_UPLOAD'),
                    ]));
                }

                $urlImg = Router::url('/') . 'img' . DS . 'uploads' . DS . 'slider' . DS . $time . '.' . $infos['extension'];
            }

            $data['url_img'] = $urlImg;
        }

        $sliderTable = $this->fetchTable('Sliders');
        $slider = $sliderTable->get($id);
        $slider->set($data);
        $sliderTable->save($slider);

        $this->History->set('EDIT_SLIDER', 'slider');
        $this->Flash->success(__('SLIDER__EDIT_SUCCESS'));

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SLIDER__EDIT_SUCCESS'),
        ]));
    }

    public function add(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SLIDER'))) {
            return $this->redirect('/');
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
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SLIDER'))) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        $request = $this->getRequest();

        if (!$request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('NOT_POST'),
            ]));
        }

        $title = (string)$request->getData('title', '');
        $subtitle = (string)$request->getData('subtitle', '');

        if ($title === '' || $subtitle === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $alreadyUploaded = $request->getData('img-uploaded') !== null;

        if ($alreadyUploaded) {
            $urlImg = Router::url('/') . 'img' . DS . 'uploads' . $request->getData('img-uploaded');
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

            $filePath = WWW_ROOT . 'img' . DS . 'uploads' . DS . 'slider' . DS . $time . '.' . $infos['extension'];

            if (!$this->Util->uploadImage($request, $filePath)) {
                return $this->response->withStringBody(json_encode([
                    'status' => false,
                    'messages' => __('FORM__ERROR_WHEN_UPLOAD'),
                ]));
            }

            $urlImg = Router::url('/') . 'img' . DS . 'uploads' . DS . 'slider' . DS . $time . '.' . $infos['extension'];
        }

        $sliderTable = $this->fetchTable('Sliders');
        $slider = $sliderTable->newEntity([
            'title' => $title,
            'subtitle' => $subtitle,
            'url_img' => $urlImg,
        ]);
        $sliderTable->save($slider);

        $this->History->set('ADD_SLIDER', 'slider');
        $this->Flash->success(__('SLIDER__ADD_SUCCESS'));

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SLIDER__ADD_SUCCESS'),
        ]));
    }
}
