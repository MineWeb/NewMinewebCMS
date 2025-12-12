<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class SocialController extends AppController
{
    private array $social_default = [
        ['title' => 'Discord', 'extra' => 'fab fa-discord', 'color' => '#7289da'],
        ['title' => 'Twitter', 'extra' => 'fab fa-twitter', 'color' => '#00acee'],
        ['title' => 'Youtube', 'extra' => 'fab fa-youtube', 'color' => '#c4302b'],
        ['title' => 'FaceBook', 'extra' => 'fab fa-facebook', 'color' => '#3b5998'],
    ];

    public function index(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SOCIAL'))) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SOCIAL__HOME'));

        $socialButtonTable = $this->fetchTable('SocialButtons');
        $buttons = $socialButtonTable
            ->find()
            ->orderBy(['order' => 'ASC']);

        $this->set('social_buttons', $buttons);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Social')
            ->setTemplate('index');

        return null;
    }

    public function saveAjax(): Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SOCIAL'))) {
            return $this->redirect('/');
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        $request = $this->getRequest();

        if (!$request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $raw = (string)$request->getData('social_button_order', '');
        if ($raw === '') {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $pairs = explode('&', $raw);
        $orderMap = [];
        $position = 1;

        foreach ($pairs as $pair) {
            $parts = explode('=', $pair);
            if (!isset($parts[0])) {
                continue;
            }
            $key = $parts[0];
            $id = substr($key, 0, -2);
            if ($id !== '') {
                $orderMap[$id] = $position;
                $position++;
            }
        }

        if (empty($orderMap)) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $socialButtonTable = $this->fetchTable('SocialButtons');
        $error = false;

        foreach ($orderMap as $id => $order) {
            $button = $socialButtonTable
                ->find()
                ->where(['id' => $id])
                ->first();

            if ($button) {
                $entity = $socialButtonTable->get($button['id']);
                $entity->set(['order' => $order]);
                $socialButtonTable->save($entity);
            } else {
                $error = true;
            }
        }

        if ($error) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('SOCIAL__SAVE_SUCCESS'),
        ]));
    }

    public function add(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SOCIAL'))) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SOCIAL__HOME'));
        $this->set('social_default', $this->social_default);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Social')
            ->setTemplate('add');

        $request = $this->getRequest();

        if (!$request->is('post')) {
            return null;
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $url = (string)$request->getData('url', '');

        if ($url === '') {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        if (!empty($request->getData('img')) && !empty($request->getData('icon')) && empty($request->getData('type'))) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('SOCIAL__CANNOT_TOW_TYPE'),
            ]));
        }

        $extra = null;
        $type = (string)$request->getData('type', '');

        if ($type !== '') {
            if ($type === 'img') {
                $extra = $request->getData('img');
            } else {
                $extra = $request->getData('icon');
            }
        }

        $socialButtonTable = $this->fetchTable('SocialButtons');

        $last = $socialButtonTable
            ->find()
            ->orderBy(['order' => 'DESC'])
            ->limit(1)
            ->first();

        $order = $last ? (int)$last['order'] + 1 : 1;

        $button = $socialButtonTable->newEntity([
            'order' => $order,
            'title' => $request->getData('title'),
            'extra' => $extra,
            'color' => $request->getData('color'),
            'url' => $url,
        ]);

        $socialButtonTable->save($button);

        $this->History->set('ADD_SOCIAL', 'social network');

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('SOCIAL__BUTTON_SUCCESS'),
        ]));
    }

    public function edit(int|string|null $id = null): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SOCIAL'))) {
            throw new ForbiddenException();
        }

        if ($id === null) {
            throw new NotFoundException();
        }

        $socialButtonTable = $this->fetchTable('SocialButtons');

        $button = $socialButtonTable
            ->find()
            ->where(['id' => $id])
            ->orderBy(['id' => 'DESC'])
            ->first();

        if (!$button) {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('SOCIAL__HOME'));

        $type = null;
        if (!empty($button['extra'])) {
            if (strpos((string)$button['extra'], 'fa-') !== false) {
                $type = 'fa';
            } else {
                $type = 'img';
            }
        }

        $this->set('social_button', $button);
        $this->set('social_default', $this->social_default);
        $this->set('social_button_type', $type);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Social')
            ->setTemplate('edit');

        $request = $this->getRequest();

        if (!$request->is('post')) {
            return null;
        }

        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        $url = (string)$request->getData('url', '');

        if ($url === '') {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        if (!empty($request->getData('img')) && !empty($request->getData('icon')) && empty($request->getData('type'))) {
            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('SOCIAL__CANNOT_TOW_TYPE'),
            ]));
        }

        $extra = null;
        $type = (string)$request->getData('type', '');

        if ($type !== '') {
            if ($type === 'img') {
                $extra = $request->getData('img');
            } else {
                $extra = $request->getData('icon');
            }
        }

        $entity = $socialButtonTable->get($id);
        $entity->set([
            'title' => $request->getData('title'),
            'extra' => $extra,
            'color' => $request->getData('color'),
            'url' => $url,
        ]);
        $socialButtonTable->save($entity);

        $this->History->set('EDIT_SOCIAL', 'social network');

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('SOCIAL__BUTTON_EDIT_SUCCESS'),
        ]));
    }

    public function delete(int|string|null $id = null): Response
    {
        $this->disableAutoRender();

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SOCIAL'))) {
            return $this->redirect('/');
        }

        if ($id !== null) {
            $socialButtonTable = $this->fetchTable('SocialButtons');
            $button = $socialButtonTable->get($id);

            if ($socialButtonTable->delete($button)) {
                $this->History->set('DELETE_SOCIAL', 'social network');
                $this->Flash->success(__('SOCIAL__BUTTON_DELETE_SUCCESS'));
            }
        }

        return $this->redirect([
            '_name' => 'admin_social_index',
        ]);
    }
}
