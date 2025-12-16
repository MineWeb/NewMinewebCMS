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
        $buttons = $socialButtonTable->find()->orderBy(['order' => 'ASC'])->toArray();

        $this->set('social_buttons', $buttons);

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Social')
            ->setTemplate('index');

        return null;
    }

    public function saveAjax(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SOCIAL'))) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('FORBIDDEN'),
            ]));
        }

        $request = $this->getRequest();

        if (!$request->is('post')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $contentType = strtolower((string)$request->getHeaderLine('Content-Type'));
        $order = [];

        if (strpos($contentType, 'application/json') !== false) {
            $payload = (array)$request->getData();
            $order = isset($payload['order']) && is_array($payload['order']) ? $payload['order'] : [];
        } else {
            $raw = (string)$request->getData('social_button_order', '');
            if ($raw !== '') {
                $pairs = explode('&', $raw);
                foreach ($pairs as $pair) {
                    $parts = explode('=', $pair, 2);
                    $key = $parts[0] ?? '';
                    if ($key === '') {
                        continue;
                    }
                    if (substr($key, -2) === '[]') {
                        $key = substr($key, 0, -2);
                    }
                    $order[] = $key;
                }
            }
        }

        $order = array_values(array_filter($order, function ($v) {
            return is_string($v) && $v !== '' && ctype_digit($v);
        }));

        if (!$order) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $socialButtonTable = $this->fetchTable('SocialButtons');

        try {
            $socialButtonTable->getConnection()->transactional(function () use ($socialButtonTable, $order) {
                $pos = 1;
                foreach ($order as $id) {
                    $socialButtonTable->updateAll(
                        ['order' => $pos],
                        ['id' => (int)$id]
                    );
                    $pos++;
                }
            });
        } catch (\Throwable) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__INTERNAL_ERROR'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SOCIAL__SAVE_SUCCESS'),
        ]));
    }

    public function add(): ?Response
    {
        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SOCIAL'))) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SOCIAL__ADD'));
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
        $type = (string)$request->getData('type', '');
        $title = (string)$request->getData('title', '');
        $img = (string)$request->getData('img', '');
        $icon = (string)$request->getData('icon', '');
        $color = (string)$request->getData('color', '');

        if ($title === '' || $url === '' || $color === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        if ($img !== '' && $icon !== '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('SOCIAL__CANNOT_TWO_TYPE'),
            ]));
        }

        $extra = $type === 'img' ? $img : ($type === 'icon' ? $icon : null);

        $socialButtonTable = $this->fetchTable('SocialButtons');
        $last = $socialButtonTable->find()->orderBy(['order' => 'DESC'])->limit(1)->first();
        $order = $last ? (int)$last['order'] + 1 : 1;

        $button = $socialButtonTable->newEntity([
            'order' => $order,
            'title' => $title,
            'extra' => $extra,
            'color' => $color,
            'url' => $url,
        ]);

        if ($socialButtonTable->save($button)) {
            $this->History->set('ADD_SOCIAL', 'social network');
            return $this->response->withStringBody(json_encode([
                'status' => true,
                'messages' => __('SOCIAL__BUTTON_SUCCESS'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'status' => false,
            'messages' => __('ERROR__INTERNAL_ERROR'),
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
        $button = $socialButtonTable->find()->where(['id' => $id])->first();

        if (!$button) {
            throw new NotFoundException();
        }

        $this->set('title_for_layout', __('SOCIAL__EDIT'));
        $this->set('social_button', $button);
        $this->set('social_default', $this->social_default);

        $type = null;
        if (!empty($button['extra'])) {
            $type = (strpos((string)$button['extra'], 'fa-') !== false) ? 'icon' : 'img';
        }
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
        $type = (string)$request->getData('type', '');
        $title = (string)$request->getData('title', '');
        $img = (string)$request->getData('img', '');
        $icon = (string)$request->getData('icon', '');
        $color = (string)$request->getData('color', '');

        if ($title === '' || $url === '' || $color === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        if ($img !== '' && $icon !== '' && $type === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('SOCIAL__CANNOT_TWO_TYPE'),
            ]));
        }

        $extra = null;
        if ($type === 'img') {
            $extra = $img;
        } elseif ($type === 'icon') {
            $extra = $icon;
        }

        $entity = $socialButtonTable->get($id);
        $entity = $socialButtonTable->patchEntity($entity, [
            'title' => $title,
            'extra' => $extra,
            'color' => $color,
            'url' => $url,
        ]);

        if ($socialButtonTable->save($entity)) {
            $this->History->set('EDIT_SOCIAL', 'social network');
            return $this->response->withStringBody(json_encode([
                'status' => true,
                'messages' => __('SOCIAL__BUTTON_EDIT_SUCCESS'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'status' => false,
            'messages' => __('ERROR__INTERNAL_ERROR'),
        ]));
    }

    public function delete(int|string|null $id = null): Response
    {
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
