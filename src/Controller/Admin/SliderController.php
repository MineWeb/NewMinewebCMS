<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

class SliderController extends AppController
{
    public function index()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_SLIDER')) {
            $this->set('title_for_layout', $this->Lang->get('SLIDER__ADD'));
            $this->Slider = TableRegistry::getTableLocator()->get('Slider');
            $sliders = $this->Slider->find()->all();
            $this->set(compact('sliders'));
        } else {
            $this->redirect('/');
        }
    }

    public function delete($id = false)
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_SLIDER')) {
            if ($id) {
                $this->Slider = TableRegistry::getTableLocator()->get('Slider');
                $find = $this->Slider->find('all', conditions: ['id' => $id])->first();
                if ($find !== null) {
                    $this->Slider->delete($find);
                    $this->History->set('DELETE_SLIDER', 'slider');
                    $this->Flash->success($this->Lang->get('SLIDER__DELETE_SUCCESS'));
                } else {
                    $this->Flash->error($this->Lang->get('UNKNONW_ID'));
                }
            }
            $this->redirect(['controller' => 'slider', 'action' => 'index', 'admin' => true]);
        } else {
            $this->redirect('/');
        }
    }

    public function edit($id = false)
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_SLIDER')) {
            if ($id) {
                $this->Slider = TableRegistry::getTableLocator()->get('Slider');
                $slider = $this->Slider->find('all', conditions: ['id' => $id])->first();
                if (isset($slider)) {
                    $slider['filename'] = explode('/', $slider['url_img']);
                    $slider['filename'] = end($slider['filename']);

                    $this->set('title_for_layout', $this->Lang->get('SLIDER__EDIT'));
                    $this->set(compact('slider'));
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

    public function editAjax()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_SLIDER')) {
            if ($this->request->is('post')) {
                if (!empty($this->getRequest()->getData('title')) and !empty($this->getRequest()->getData('subtitle')) and !empty($this->getRequest()->getData('id'))) {

                    if (!$this->getRequest()->getData('img_edit') !== null) {
                        $checkIfImageAlreadyUploaded = $this->getRequest()->getData('img-uploaded') !== null;
                        if ($checkIfImageAlreadyUploaded) {

                            $url_img = Router::url('/') . 'img' . DS . 'uploads' . $this->getRequest()->getData('img-uploaded');

                        } else {
                            $isValidImg = $this->Util->isValidImage($this->request, ['png', 'jpg', 'jpeg']);

                            if (!$isValidImg['status']) {
                                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $isValidImg['msg']]));
                            } else {
                                $infos = $isValidImg['infos'];
                            }

                            $time = date('Y-m-d_His');

                            $url_img = WWW_ROOT . 'img' . DS . 'uploads' . DS . 'slider' . DS . $time . '.' . $infos['extension'];

                            if (!$this->Util->uploadImage($this->request, $url_img)) {
                                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')]));
                            }

                            $url_img = Router::url('/') . 'img' . DS . 'uploads' . DS . 'slider' . DS . $time . '.' . $infos['extension'];

                        }

                        $data = [
                            'title' => $this->getRequest()->getData('title'),
                            'subtitle' => $this->getRequest()->getData('subtitle'),
                            'url_img' => $url_img
                        ];

                    } else {

                        $data = [
                            'title' => $this->getRequest()->getData('title'),
                            'subtitle' => $this->getRequest()->getData('subtitle'),
                        ];

                    }

                    $this->Slider = TableRegistry::getTableLocator()->get('Slider');
                    $slider = $this->Slider->get($this->request->getData('id'));
                    $slider->set($data);
                    $this->Slider->save($slider);
                    $this->History->set('EDIT_SLIDER', 'slider');
                    $this->Flash->success($this->Lang->get('SLIDER__EDIT_SUCCESS'));
                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SLIDER__EDIT_SUCCESS')]));
                } else {
                    $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
                }
            } else {
                $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')]));
            }
        } else {
            throw new ForbiddenException();
        }
    }

    public function add()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_SLIDER')) {
            $this->set('title_for_layout', $this->Lang->get('SLIDER__ADD'));
        } else {
            $this->redirect('/');
        }
    }

    public function addAjax()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_SLIDER')) {

            if ($this->request->is('post')) {

                if (!empty($this->getRequest()->getData('title')) and !empty($this->getRequest()->getData('subtitle'))) {

                    $checkIfImageAlreadyUploaded = $this->getRequest()->getData('img-uploaded') !== null;
                    if ($checkIfImageAlreadyUploaded) {

                        $url_img = Router::url('/') . 'img' . DS . 'uploads' . $this->getRequest()->getData('img-uploaded');

                    } else {
                        $isValidImg = $this->Util->isValidImage($this->request, ['png', 'jpg', 'jpeg']);

                        if (!$isValidImg['status']) {
                            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $isValidImg['msg']]));
                        } else {
                            $infos = $isValidImg['infos'];
                        }

                        $time = date('Y-m-d_His');

                        $url_img = WWW_ROOT . 'img' . DS . 'uploads' . DS . 'slider' . DS . $time . '.' . $infos['extension'];

                        if (!$this->Util->uploadImage($this->request, $url_img)) {
                            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('FORM__ERROR_WHEN_UPLOAD')]));
                        }

                        $url_img = Router::url('/') . 'img' . DS . 'uploads' . DS . 'slider' . DS . $time . '.' . $infos['extension'];
                    }

                    $this->Slider = TableRegistry::getTableLocator()->get('Slider');
                    $slider = $this->Slider->newEntity([
                        'title' => $this->getRequest()->getData('title'),
                        'subtitle' => $this->getRequest()->getData('subtitle'),
                        'url_img' => $url_img
                    ]);
                    $this->Slider->save($slider);

                    $this->History->set('ADD_SLIDER', 'slider');

                    $this->Flash->success($this->Lang->get('SLIDER__ADD_SUCCESS'));
                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SLIDER__ADD_SUCCESS')]));
                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
                }
            } else {
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('NOT_POST', $language)]));
            }
        } else {
            throw new ForbiddenException();
        }
    }

}
