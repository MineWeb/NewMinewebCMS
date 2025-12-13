<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Utility\LangService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class ServerController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('Server');
    }

    public function link(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SERVER__LINK'));

        $Servers = $this->fetchTable('Servers');
        $servers = $Servers->find()->all()->toArray();

        $bannerRaw = (string)$this->config->get('banner_server');
        $banner = @unserialize($bannerRaw);
        if (!is_array($banner)) {
            $banner = [];
        }

        foreach ($servers as $key => $value) {
            $servers[$key]['activeInBanner'] = in_array($value['id'], $banner, true);

            $dataRaw = $value['data'] ?? null;
            $servers[$key]['data'] = is_string($dataRaw) ? (json_decode($dataRaw, true) ?: []) : [];
        }

        $bannerMsg = __('SERVER__STATUS_MESSAGE');

        $this->set(compact('servers', 'bannerMsg'));
        $this->set('isEnabled', $this->config->get('server_state'));
        $this->set('isCacheEnabled', $this->config->get('server_cache'));
        $this->set('timeout', $this->config->get('server_timeout'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Server')
            ->setTemplate('link');

        return null;
    }

    public function cmd(): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SERVER__CMD'));

        $ServerCmds = $this->fetchTable('ServerCmds');
        $Servers = $this->fetchTable('Servers');

        $search_cmd = $ServerCmds
            ->find()
            ->orderBy(['server_id' => 'DESC'])
            ->all();

        $search_server = $Servers->find()->all();

        $this->set(compact('search_cmd', 'search_server'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Server')
            ->setTemplate('cmd');

        return null;
    }

    public function deleteCmd(int $id): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $ServerCmds = $this->fetchTable('ServerCmds');
        $entity = $ServerCmds->get($id);
        $ServerCmds->delete($entity);

        return $this->redirect(['_name' => 'admin_server_cmd']);
    }

    public function executeCmd(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $request = $this->getRequest();
        $cmd = (string)$request->getData('cmd', '');
        $serverId = $this->normalizeServerId($request->getData('server_id'));

        if ($cmd === '' || $serverId === null) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        $this->Server->sendCommand($cmd, $serverId);

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SERVER__SEND_COMMAND_SUCCESS'),
        ]));
    }

    public function addCmd(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $request = $this->getRequest();

        if (!$request->is('ajax')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $name = (string)$request->getData('name', '');
        $cmd = (string)$request->getData('cmd', '');
        $serverId = $this->normalizeServerId($request->getData('server_id'));

        if ($name === '' || $cmd === '' || $serverId === null) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        if (str_contains($cmd, '/')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('SERVER__CMD_SLASH'),
            ]));
        }

        $ServerCmds = $this->fetchTable('ServerCmds');
        $entity = $ServerCmds->newEntity([
            'name' => $name,
            'cmd' => $cmd,
            'server_id' => $serverId,
        ]);
        $ServerCmds->save($entity);

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SERVER__CMD_ADD'),
        ]));
    }

    public function editBannerMsg(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!($this->Auth->isConnected() && $this->Auth->can('MANAGE_SERVERS'))) {
            throw new ForbiddenException();
        }

        $request = $this->getRequest();

        if (!$request->is('ajax')) {
            throw new NotFoundException();
        }

        LangService::set('SERVER__STATUS_MESSAGE', (string)$request->getData('msg'));

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SERVER__EDIT_BANNER_MSG_SUCCESS'),
        ]));
    }

    public function switchState(): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();

        $current = (bool)$this->config->get('server_state');
        $this->config->setKey('server_state', $current ? 0 : 1);

        $this->Flash->success(__('SERVER__SUCCESS_SWITCH'));

        return $this->redirect(['_name' => 'admin_server_link']);
    }

    public function switchCacheState(): Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();

        $current = (bool)$this->config->get('server_cache');
        $this->config->setKey('server_cache', $current ? 0 : 1);

        $this->Flash->success(__('SERVER__SUCCESS_CACHE_SWITCH'));

        return $this->redirect(['_name' => 'admin_server_link']);
    }

    public function switchBanner(?int $id = null): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        if ($id !== null) {
            $bannerRaw = (string)$this->config->get('banner_server');
            $banner = @unserialize($bannerRaw);
            if (!is_array($banner)) {
                $banner = [];
            }

            if (in_array($id, $banner, true)) {
                $index = array_search($id, $banner, true);
                if ($index !== false) {
                    unset($banner[$index]);
                }
            } else {
                $banner[] = $id;
            }

            $this->config->setKey('banner_server', serialize(array_values($banner)));
        }

        return $this->redirect(['_name' => 'admin_server_link']);
    }

    public function delete(?int $id = null): Response
    {
        $this->disableAutoRender();

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            return $this->redirect(['_name' => 'home']);
        }

        if ($id === null) {
            $this->Flash->error(__('ERROR__INTERNAL_ERROR'));

            return $this->redirect(['_name' => 'admin_server_link']);
        }

        $Servers = $this->fetchTable('Servers');
        $entity = $Servers->get($id);

        if ($Servers->delete($entity)) {
            $bannerRaw = (string)$this->config->get('banner_server');
            $banner = @unserialize($bannerRaw);

            if (is_array($banner) && in_array($id, $banner, true)) {
                $index = array_search($id, $banner, true);
                if ($index !== false) {
                    unset($banner[$index]);
                }
                $this->config->setKey('banner_server', serialize(array_values($banner)));
            }

            $this->Flash->success(__('SERVER__DELETE_SERVER_SUCCESS'));
        } else {
            $this->Flash->error(__('ERROR__INTERNAL_ERROR'));
        }

        return $this->redirect(['_name' => 'admin_server_link']);
    }

    public function config(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $request = $this->getRequest();

        if (!$request->is('ajax')) {
            throw new NotFoundException();
        }

        $timeoutRaw = $request->getData('timeout');

        if ($timeoutRaw === null || $timeoutRaw === '') {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        if (!filter_var($timeoutRaw, FILTER_VALIDATE_FLOAT)) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('SERVER__INVALID_TIMEOUT'),
            ]));
        }

        $this->config->setKey('server_timeout', $timeoutRaw);

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SERVER__TIMEOUT_SAVE_SUCCESS'),
        ]));
    }

    public function linkAjax(): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            return $this->redirect(['_name' => 'home']);
        }

        $request = $this->getRequest();

        if (!$request->is('ajax')) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__BAD_REQUEST'),
            ]));
        }

        $host = (string)$request->getData('host', '');
        $port = (string)$request->getData('port', '');
        $name = (string)$request->getData('name', '');
        $type = $request->getData('type');

        if ($host === '' || $port === '' || $name === '' || $type === null) {
            return $this->response->withStringBody(json_encode([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'status' => true,
            'messages' => __('SERVER__LINK_SUCCESS'),
        ]));
    }

    public function banlist(?int $server_id = null): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $call = $this->Server->call('GET_BANNED_PLAYERS', $server_id);
        $list = [];

        if (is_array($call) && isset($call['GET_BANNED_PLAYERS']) && $call['GET_BANNED_PLAYERS'] !== 'NOT_FOUND') {
            foreach ((array)$call['GET_BANNED_PLAYERS'] as $player) {
                $list[] = $player;
            }
        }

        $Servers = $this->fetchTable('Servers');
        $servers = $Servers->find()->where(['type' => 0])->all();

        $this->set(compact('list', 'servers'));
        $this->set('title_for_layout', __('SERVER__BANLIST'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Server')
            ->setTemplate('banlist');

        return null;
    }

    public function whitelist(?int $server_id = null): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $call = $this->Server->call('GET_WHITELISTED_PLAYERS', $server_id);
        $list = [];

        if (is_array($call) && isset($call['GET_WHITELISTED_PLAYERS']) && $call['GET_WHITELISTED_PLAYERS'] !== 'NOT_FOUND') {
            foreach ((array)$call['GET_WHITELISTED_PLAYERS'] as $player) {
                $list[] = $player;
            }
        }

        $Servers = $this->fetchTable('Servers');
        $servers = $Servers->find()->where(['type' => 0])->all();

        $this->set(compact('list', 'servers'));
        $this->set('title_for_layout', __('SERVER__WHITELIST'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Server')
            ->setTemplate('whitelist');

        return null;
    }

    public function online(?int $server_id = null): ?Response
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $call = $this->Server->call('GET_PLAYER_LIST', $server_id);
        $list = [];

        if (is_array($call) && isset($call['GET_PLAYER_LIST']) && $call['GET_PLAYER_LIST'] !== 'NOT_FOUND') {
            foreach ((array)$call['GET_PLAYER_LIST'] as $player) {
                $list[] = $player;
            }
        }

        $Servers = $this->fetchTable('Servers');
        $servers = $Servers->find()->where(['type' => 0])->all();

        $this->set(compact('list', 'servers'));
        $this->set('title_for_layout', __('SERVER__STATUS_ONLINE'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Server')
            ->setTemplate('online');

        return null;
    }

    private function normalizeServerId(mixed $raw): ?int
    {
        if ($raw === null || $raw === '' || $raw === false) {
            return null;
        }

        if (is_int($raw)) {
            return $raw > 0 ? $raw : null;
        }

        if (is_string($raw) && ctype_digit($raw)) {
            $id = (int)$raw;

            return $id > 0 ? $id : null;
        }

        return null;
    }

    private function linkDebugFull(string $msg, string $host, string $port, bool $udp = false): string
    {
        $msg .= $this->linkDebugPing();

        $msg .= '<br /><br />';
        $msg .= '<i class="fa fa-times"></i> ';

        if ($this->Server->ping(['ip' => $host, 'port' => (int)$port, 'udp' => $udp])) {
            $msg .= __('SERVER__SEEMS_USED');
        } else {
            $msg .= __('SERVER__PORT_CLOSE_OR_BAD');
        }

        return $msg;
    }

    private function linkDebugPing(): string
    {
        $msg = '<br /><br />';

        $hypixelIp = gethostbyname('mc.hypixel.net');
        if ($this->Server->ping(['ip' => $hypixelIp, 'port' => 25565, 'udp' => false])) {
            $msg .= '<i class="fa fa-check"></i> ';
            $msg .= __('SERVER__PORT_OPEN');
        } else {
            $msg .= '<i class="fa fa-times"></i> ';
            $msg .= __('SERVER__SEEMS_CLOSE_OR_BLOCKED');
        }

        return $msg;
    }
}
