<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Utility\LangService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\I18n\I18n;
use Cake\ORM\TableRegistry;

class ServerController extends AppController
{
    public function link(): void
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SERVER__LINK'));

        $this->Server = TableRegistry::getTableLocator()->get('Server');
        $servers = $this->Server->find()->all()->toArray();
        $banner_server = unserialize($this->Configuration->getKey('banner_server'));

        if ($banner_server) {
            foreach ($servers as $key => $value) {
                $servers[$key]['activeInBanner'] = in_array($value['id'], $banner_server);
            }
        }

        foreach ($servers as $key => $value) {
            $servers[$key]['data'] = json_decode($value['data'], true);
        }

        $bannerMsg = __('SERVER__STATUS_MESSAGE');

        $this->set(compact('servers', 'bannerMsg'));
        $this->set('isEnabled', $this->Configuration->getKey('server_state'));
        $this->set('isCacheEnabled', $this->Configuration->getKey('server_cache'));
        $this->set('timeout', $this->Configuration->getKey('server_timeout'));
    }

    public function cmd(): void
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $this->set('title_for_layout', __('SERVER__CMD'));

        $this->ServerCmd = TableRegistry::getTableLocator()->get('ServerCmd');
        $this->Server = TableRegistry::getTableLocator()->get('Server');

        $search_cmd = $this->ServerCmd->find('all', order: 'server_id DESC')->all();
        $search_server = $this->Server->find()->all();

        $this->set(compact('search_cmd', 'search_server'));
    }

    public function deleteCmd(int $id): void
    {
        $this->disableAutoRender();

        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $this->ServerCmd = TableRegistry::getTableLocator()->get('ServerCmd');
        $this->ServerCmd->delete($this->ServerCmd->get($id));

        $this->redirect(['action' => 'cmd', 'admin' => true]);
    }

    public function executeCmd(): \Cake\Http\Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $this->ServerComponent = $this->loadComponent('Server');
        $this->ServerComponent->send_command(
            $this->request->getData('cmd'),
            $this->request->getData('server_id')
        );

        return $this->response->withStringBody(json_encode([
            'statut' => true,
            'msg' => __('SERVER__SEND_COMMAND_SUCCESS')
        ]));
    }

    public function addCmd(): \Cake\Http\Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        if ($this->request->is('ajax')) {
            if (
                !empty($this->request->getData('name')) &&
                !empty($this->request->getData('cmd')) &&
                !empty($this->request->getData('server_id'))
            ) {
                if (!str_contains($this->request->getData('cmd'), '/')) {
                    $this->ServerCmd = TableRegistry::getTableLocator()->get('ServerCmd');
                    $cmd = $this->ServerCmd->newEntity([
                        'name' => $this->request->getData('name'),
                        'cmd' => $this->request->getData('cmd'),
                        'server_id' => $this->request->getData('server_id')
                    ]);
                    $this->ServerCmd->save($cmd);

                    return $this->response->withStringBody(json_encode([
                        'statut' => true,
                        'msg' => __('SERVER__CMD_ADD')
                    ]));
                }

                return $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('SERVER__CMD_SLASH')
                ]));
            }

            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS')
            ]));
        }

        return $this->response->withStringBody(json_encode([
            'statut' => false,
            'msg' => __('ERROR__BAD_REQUEST')
        ]));
    }

    public function editBannerMsg(): \Cake\Http\Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if ($this->isConnected && $this->Permissions->can('MANAGE_SERVERS')) {
            if ($this->request->is('ajax')) {

                LangService::set('SERVER__STATUS_MESSAGE', $this->request->getData('msg'));

                return $this->response->withStringBody(json_encode([
                    'statut' => true,
                    'msg' => __('SERVER__EDIT_BANNER_MSG_SUCCESS')
                ]));
            }

            throw new NotFoundException();
        }

        throw new ForbiddenException();
    }

    public function switchState(): void
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();

        $value = $this->Configuration->getKey('server_state') ? 0 : 1;
        $this->Configuration->setKey('server_state', $value);

        $this->Flash->success(__('SERVER__SUCCESS_SWITCH'));
        $this->redirect(['action' => 'link', 'admin' => true]);
    }

    public function switchCacheState(): void
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $this->disableAutoRender();

        $value = $this->Configuration->getKey('server_cache') ? 0 : 1;
        $this->Configuration->setKey('server_cache', $value);

        $this->Flash->success(__('SERVER__SUCCESS_CACHE_SWITCH'));
        $this->redirect(['action' => 'link', 'admin' => true]);
    }

    public function switchBanner(int $id = null): void
    {
        $this->disableAutoRender();

        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        if ($id !== null) {
            $banner = unserialize($this->Configuration->getKey('banner_server'));

            if ($banner) {
                if (in_array($id, $banner)) {
                    unset($banner[array_search($id, $banner)]);
                } else {
                    $banner[] = $id;
                }

                $banner = array_values($banner);
                $this->Configuration->setKey('banner_server', serialize($banner));
            } else {
                $this->Configuration->setKey('banner_server', serialize([$id]));
            }
        }
    }

    public function delete(int $id = null): void
    {
        $this->disableAutoRender();

        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            $this->redirect('/');
            return;
        }

        if ($id !== null) {
            $this->Server = TableRegistry::getTableLocator()->get('Server');

            if ($this->Server->delete($this->Server->get($id))) {
                $banner = unserialize($this->Configuration->getKey('banner_server'));

                if ($banner) {
                    if (in_array($id, $banner)) {
                        unset($banner[array_search($id, $banner)]);
                    }

                    $banner = array_values($banner);
                    $this->Configuration->setKey('banner_server', serialize($banner));
                }

                $this->Flash->success(__('SERVER__DELETE_SERVER_SUCCESS'));
            } else {
                $this->Flash->error(__('ERROR__INTERNAL_ERROR'));
            }

            $this->redirect(['action' => 'link', 'admin' => true]);
            return;
        }

        $this->Flash->error(__('ERROR__INTERNAL_ERROR'));
        $this->redirect(['action' => 'link', 'admin' => true]);
    }

    public function config(): \Cake\Http\Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        if ($this->request->is('ajax')) {
            if (!empty($this->request->getData('timeout'))) {
                if (filter_var($this->request->getData('timeout'), FILTER_VALIDATE_FLOAT)) {
                    $this->Configuration->setKey('server_timeout', $this->request->getData('timeout'));

                    return $this->response->withStringBody(json_encode([
                        'statut' => true,
                        'msg' => __('SERVER__TIMEOUT_SAVE_SUCCESS')
                    ]));
                }

                return $this->response->withStringBody(json_encode([
                    'statut' => false,
                    'msg' => __('SERVER__INVALID_TIMEOUT')
                ]));
            }

            return $this->response->withStringBody(json_encode([
                'statut' => false,
                'msg' => __('ERROR__FILL_ALL_FIELDS')
            ]));
        }

        throw new NotFoundException();
    }

    public function linkAjax(): \Cake\Http\Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
        return $this->redirect('/');
    }

        if (!$this->request->is('ajax')) {
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => __('ERROR__BAD_REQUEST')]));
        }

        if (
            empty($this->request->getData('host')) ||
            empty($this->request->getData('port')) ||
            empty($this->request->getData('name')) ||
            $this->request->getData('type') === null
        ) {
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => __('ERROR__FILL_ALL_FIELDS')]));
        }

        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => __('SERVER__LINK_SUCCESS')]));
    }

    public function banlist(int $server_id = null): void
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $call = $this->Server->call('GET_BANNED_PLAYERS', $server_id);
        $list = [];

        if (isset($call['GET_BANNED_PLAYERS']) && $call['GET_BANNED_PLAYERS'] !== 'NOT_FOUND') {
            foreach ($call['GET_BANNED_PLAYERS'] as $player) {
                $list[] = $player;
            }
        }

        $this->set(compact('list'));

        $this->Server = TableRegistry::getTableLocator()->get('Server');
        $this->set('servers', $this->Server->find('all', conditions: ['type' => 0])->all());

        $this->set('title_for_layout', __('SERVER__BANLIST'));
    }

    public function whitelist(int $server_id = null): void
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $call = $this->Server->call('GET_WHITELISTED_PLAYERS', $server_id);
        $list = [];

        if (isset($call['GET_WHITELISTED_PLAYERS']) && $call['GET_WHITELISTED_PLAYERS'] !== 'NOT_FOUND') {
            foreach ($call['GET_WHITELISTED_PLAYERS'] as $player) {
                $list[] = $player;
            }
        }

        $this->set(compact('list'));

        $this->Server = TableRegistry::getTableLocator()->get('Server');
        $this->set('servers', $this->Server->find('all', conditions: ['type' => 0]));

        $this->set('title_for_layout', __('SERVER__WHITELIST'));
    }

    public function online(int $server_id = null): void
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }

        $call = $this->Server->call('GET_PLAYER_LIST', $server_id);
        $list = [];

        if (isset($call['GET_PLAYER_LIST']) && $call['GET_PLAYER_LIST'] !== 'NOT_FOUND') {
            foreach ($call['GET_PLAYER_LIST'] as $player) {
                $list[] = $player;
            }
        }

        $this->set(compact('list'));

        $this->Server = TableRegistry::getTableLocator()->get('Server');
        $this->set('servers', $this->Server->find('all', conditions: ['type' => 0]));

        $this->set('title_for_layout', __('SERVER__STATUS_ONLINE'));
    }

    private function linkDebugFull(string $msg, string $host, string $port, bool $udp = false): string
    {
        $msg .= $this->linkDebugPing();

        $msg .= "<br /><br />";
        $msg .= "<i class=\"fa fa-times\"></i> ";

        if ($this->Server->ping(['ip' => $host, 'port' => $port, 'udp' => $udp])) {
            $msg .= __('SERVER__SEEMS_USED');
        } else {
            $msg .= __('SERVER__PORT_CLOSE_OR_BAD');
        }

        return $msg;
    }

    private function linkDebugPing(): string
    {
        $msg = "<br /><br />";

        $hypixelIp = gethostbyname('mc.hypixel.net');
        if ($this->Server->ping(['ip' => $hypixelIp, 'port' => 25565, 'udp' => false])) {
            $msg .= "<i class=\"fa fa-check\"></i> ";
            $msg .= __('SERVER__PORT_OPEN');
        } else {
            $msg .= "<i class=\"fa fa-times\"></i> ";
            $msg .= __('SERVER__SEEMS_CLOSE_OR_BLOCKED');
        }

        return $msg;
    }
}
