<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

class SocialController extends AppController
{
    private $social_default = [
        ['title' => 'Discord', 'extra' => 'fab fa-discord', 'color' => '#7289da'],
        ['title' => 'Twitter', 'extra' => 'fab fa-twitter', 'color' => '#00acee'],
        ['title' => 'Youtube', 'extra' => 'fab fa-youtube', 'color' => '#c4302b'],
        ['title' => 'FaceBook', 'extra' => 'fab fa-facebook', 'color' => '#3b5998']
    ];

    function index()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SOCIAL'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('SOCIAL__HOME'));

        $this->set('social_buttons', $this->SocialButton->find('all', ['order' => 'Social.order']));
    }

    public function saveAjax()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_SOCIAL')) {
            if ($this->request->is('post')) {
                if (!empty($this->request->getData())) {
                    $data = $this->request->getData('social_button_order');
                    $data = explode('&', $data);
                    $i = 1;
                    foreach ($data as $key => $value) {
                        $data2[] = explode('=', $value);
                        $data3 = substr($data2[0][0], 0, -2);
                        $data1[$data3] = $i;
                        unset($data3);
                        unset($data2);
                        $i++;
                    }

                    $data = $data1;
                    foreach ($data as $key => $value) {
                        $find = $this->SocialButton->find('all', array('conditions' => array('id' => $key)))->first();
                        if (!empty($find)) {
                            $id = $find['id'];
                            $button = $this->SocialButton->get($id);
                            $button->set(array(
                                'order' => $value,
                            ));
                            $this->SocialButton->save($button);
                        } else {
                            $error = 1;
                        }
                    }
                    if (empty($error)) {
                        return $this->response->withStringBody(json_encode(array('statut' => true, 'msg' => $this->Lang->get('SOCIAL__SAVE_SUCCESS'))));
                    } else {
                        return $this->response->withStringBody(json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS'))));
                    }
                } else {
                    return $this->response->withStringBody(json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS'))));
                }
            } else {
                return $this->response->withStringBody(json_encode(array('statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST'))));
            }
        } else {
            return $this->redirect('/');
        }
    }

    function add()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SOCIAL'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('SOCIAL__HOME'));

        $this->set('social_default', $this->social_default);

        if ($this->request->is('post')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');

            if (empty($this->request->getData('url')))
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
            if (!empty($this->request->getData('img')) && !empty($this->request->getData('icon')) && empty($this->request->getData('type')))
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('SOCIAL__CANNOT_TOW_TYPE')]));

            $extra = null;
            if (!empty($this->request->getData('type'))) {
                if ($this->request->getData('type') == "img") {
                    $extra = $this->request->getData('img');
                } else {
                    $extra = $this->request->getData('icon');
                }
            }

            $order = $this->SocialButton->find('all', ['order' => ['order' => 'DESC']])->first();
            $order = (empty($order)) ? 1 : $order['order'] + 1;

            $button = $this->SocialButton->newEntity([
                'order' => $order,
                'title' => $this->request->getData('title'),
                'extra' => $extra,
                'color' => $this->request->getData('color'),
                'url' => $this->request->getData('url')
            ]);
            $this->SocialButton->save($button);

            $this->History->set('ADD_SOCIAL', 'social network');
            return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SOCIAL__BUTTON_SUCCESS')]));
        }
    }

    function edit($id = false)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SOCIAL'))
            throw new ForbiddenException();

        if (!$id)
            throw new NotFoundException();

        $find = $this->SocialButton->find('all', ['order' => 'id desc', 'conditions' => ['id' => $id]])->first();
        if (empty($find))
            throw new NotFoundException();

        $this->set('title_for_layout', $this->Lang->get('SOCIAL__HOME'));
        $this->layout = 'admin';

        $social_button_type = null;
        if (!empty($find['extra'])) {
            if (strpos($find['extra'], 'fa-')) {
                $social_button_type = 'fa';
            } else {
                $social_button_type = 'img';
            }
        }


        $this->set('social_button', $find);
        $this->set('social_default', $this->social_default);
        $this->set('social_button_type', $social_button_type);

        if ($this->request->is('post')) {
            $this->disableAutoRender();
            $this->response = $this->response->withType('application/json');

            if (empty($this->request->getData('url')))
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
            if (!empty($this->request->getData('img')) && !empty($this->request->getData('icon')) && empty($this->request->getData('type')))
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('SOCIAL__CANNOT_TOW_TYPE')]));

            $extra = null;
            if (!empty($this->request->getData('type'))) {
                if ($this->request->getData('type') == "img") {
                    $extra = $this->request->getData('img');;
                } else {
                    $extra = $this->request->getData('icon');;
                }
            }

            $button = $this->SocialButton->get($id);
            $button->set([
                'title' => $this->request->getData('title'),
                'extra' => $extra,
                'color' => $this->request->getData('color'),
                'url' => $this->request->getData('url')
            ]);
            $this->SocialButton->save($button);

            $this->History->set('EDIT_SOCIAL', 'social network');
            return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SOCIAL__BUTTON_EDIT_SUCCESS')]));
        }
    }

    public function delete($id = false)
    {
        $this->disableAutoRender();
        if ($this->isConnected and $this->Permissions->can('MANAGE_SOCIAL')) {
            if ($id) {
                if ($this->SocialButton->delete($this->SocialButton->get($id))) {
                    $this->History->set('DELETE_SOCIAL', 'social network');
                    $this->Flash->success($this->Lang->get('SOCIAL__BUTTON_DELETE_SUCCESS'));
                }
            }

            $this->redirect(['controller' => 'social', 'action' => 'index', 'admin' => true]);
        } else {
            $this->redirect('/');
        }
    }
}
