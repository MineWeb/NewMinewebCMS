<?php
declare(strict_types=1);

namespace App\Controller\Admin;

use App\Controller\AppController;
use App\Service\LangService;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\Http\Response;

class ServerController extends AppController
{
    private function requireManageServers(): void
    {
        if (!$this->Auth->isConnected() || !$this->Auth->can('MANAGE_SERVERS')) {
            throw new ForbiddenException();
        }
    }

    private function requireAjax(): void
    {
        if (!$this->getRequest()->is('ajax')) {
            throw new NotFoundException();
        }
    }

    private function jsonResponse(array $payload): Response
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        return $this->response->withStringBody((string)json_encode($payload));
    }

    public function link(): ?Response
    {
        $this->requireManageServers();

        $this->set('title_for_layout', __('SERVER__LINK_TITLE'));

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
        $this->set('isEnabled', (bool)$this->config->get('server_state'));
        $this->set('isCacheEnabled', (bool)$this->config->get('server_cache'));
        $this->set('timeout', (string)$this->config->get('server_timeout'));

        $this->viewBuilder()
            ->setLayout('admin')
            ->setTemplatePath('Admin/Server')
            ->setTemplate('link');

        return null;
    }

    public function editBannerMsg(): Response
    {
        $this->requireManageServers();
        $this->requireAjax();

        $msg = (string)$this->getRequest()->getData('msg', '');
        LangService::set('SERVER__STATUS_MESSAGE', $msg);

        return $this->jsonResponse([
            'status' => true,
            'messages' => __('SERVER__EDIT_BANNER_MSG_SUCCESS'),
        ]);
    }

    public function switchState(): Response
    {
        $this->requireManageServers();

        $this->disableAutoRender();

        $current = (bool)$this->config->get('server_state');
        $this->config->set('server_state', $current ? 0 : 1);

        $this->Flash->success(__('SERVER__SUCCESS_SWITCH'));

        return $this->redirect(['_name' => 'admin_server_link']);
    }

    public function switchCacheState(): Response
    {
        $this->requireManageServers();

        $this->disableAutoRender();

        $current = (bool)$this->config->get('server_cache');
        $this->config->set('server_cache', $current ? 0 : 1);

        $this->Flash->success(__('SERVER__SUCCESS_CACHE_SWITCH'));

        return $this->redirect(['_name' => 'admin_server_link']);
    }

    public function switchBanner(?int $id = null): Response
    {
        $this->requireManageServers();

        $this->disableAutoRender();

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

            $this->config->set('banner_server', serialize(array_values($banner)));
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
                $this->config->set('banner_server', serialize(array_values($banner)));
            }

            $this->Flash->success(__('SERVER__DELETE_SERVER_SUCCESS'));
        } else {
            $this->Flash->error(__('ERROR__INTERNAL_ERROR'));
        }

        return $this->redirect(['_name' => 'admin_server_link']);
    }

    public function config(): Response
    {
        $this->requireManageServers();
        $this->requireAjax();

        $timeoutRaw = $this->getRequest()->getData('timeout');

        if ($timeoutRaw === null || $timeoutRaw === '') {
            return $this->jsonResponse([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]);
        }

        if (!filter_var($timeoutRaw, FILTER_VALIDATE_FLOAT)) {
            return $this->jsonResponse([
                'status' => false,
                'messages' => __('SERVER__INVALID_TIMEOUT'),
            ]);
        }

        $this->config->set('server_timeout', (string)$timeoutRaw);

        return $this->jsonResponse([
            'status' => true,
            'messages' => __('SERVER__TIMEOUT_SAVE_SUCCESS'),
        ]);
    }

    public function linkAjax(): Response
    {
        $this->requireManageServers();
        $this->requireAjax();

        $request = $this->getRequest();

        $idRaw = $request->getData('id');
        $id = null;
        if (is_int($idRaw) && $idRaw > 0) {
            $id = $idRaw;
        } elseif (is_string($idRaw) && ctype_digit($idRaw) && (int)$idRaw > 0) {
            $id = (int)$idRaw;
        }

        $host = trim((string)$request->getData('host', ''));
        $portRaw = trim((string)$request->getData('port', ''));
        $name = trim((string)$request->getData('name', ''));
        $typeRaw = $request->getData('type');

        if ($host === '' || $portRaw === '' || $name === '' || $typeRaw === null) {
            return $this->jsonResponse(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')]);
        }

        if (!ctype_digit($portRaw)) {
            return $this->jsonResponse(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')]);
        }

        $port = (int)$portRaw;
        if ($port < 1 || $port > 65535) {
            return $this->jsonResponse(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')]);
        }

        $type = null;
        if (is_int($typeRaw)) {
            $type = $typeRaw;
        } elseif (is_string($typeRaw) && ctype_digit($typeRaw)) {
            $type = (int)$typeRaw;
        }

        if ($type === null || $type < 0 || $type > 3) {
            return $this->jsonResponse(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')]);
        }

        $serverData = [];
        if ($type === 2) {
            $sd = $request->getData('server_data');
            $rconPortRaw = trim((string)($request->getData('server_data.rcon_port') ?? (is_array($sd) ? ($sd['rcon_port'] ?? '') : '')));
            $rconPassword = (string)($request->getData('server_data.rcon_password') ?? (is_array($sd) ? ($sd['rcon_password'] ?? '') : ''));

            if ($rconPortRaw === '' || !ctype_digit($rconPortRaw) || trim($rconPassword) === '') {
                return $this->jsonResponse(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')]);
            }

            $rconPort = (int)$rconPortRaw;
            if ($rconPort < 1 || $rconPort > 65535) {
                return $this->jsonResponse(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')]);
            }

            $serverData = [
                'rcon_port' => $rconPort,
                'rcon_password' => $rconPassword,
            ];
        }

        if ($type === 0) {
            $timeout = (string)$this->config->get('server_timeout');
            if ($timeout === '' || !filter_var($timeout, FILTER_VALIDATE_FLOAT)) {
                return $this->jsonResponse(['status' => false, 'messages' => __('SERVER__TIMEOUT_UNDEFINED')]);
            }

            $ok = $this->serverBridge->check('connection', [
                'host' => $host,
                'port' => $portRaw,
                'timeout' => (int)$timeout,
            ]);

            if (!$ok) {
                $code = $this->serverBridge->linkErrorCode ?? 'FAILED';
                $base = __('SERVER__LINK_ERROR_' . $code);

                $lines = [$base];
                $lines = array_merge($lines, $this->serverBridge->linkDebugFull($host, $portRaw, false));

                return $this->jsonResponse([
                    'status' => false,
                    'messages' => $lines,
                ]);
            }
        } elseif ($type === 1 || $type === 3) {
            $ok = $this->serverBridge->pingPublic([
                'ip' => $host,
                'port' => $port,
                'udp' => $type === 3,
            ]);

            if ($ok === false) {
                $base = __('SERVER__LINK_ERROR_FAILED');

                $lines = [$base];
                $lines = array_merge($lines, $this->serverBridge->linkDebugPing());

                return $this->jsonResponse([
                    'status' => false,
                    'messages' => $lines,
                ]);
            }
        } elseif ($type === 2) {
            $ok = $this->serverBridge->testRcon(
                [
                    'ip' => $host,
                    'port' => (int)$serverData['rcon_port'],
                    'password' => (string)$serverData['rcon_password'],
                ],
                'say ' . __('SERVER__LINK_SUCCESS')
            );

            if (!$ok) {
                $base = __('SERVER__LINK_ERROR_FAILED');

                $lines = [$base];
                $lines = array_merge($lines, $this->serverBridge->linkDebugPing());

                return $this->jsonResponse([
                    'status' => false,
                    'messages' => $lines,
                ]);
            }
        }

        $this->config->set('server_state', 1);

        $Servers = $this->fetchTable('Servers');
        $entity = $id !== null ? $Servers->get($id) : $Servers->newEmptyEntity();

        $payload = [
            'name' => $name,
            'ip' => $host,
            'port' => $port,
            'type' => $type,
            'data' => json_encode($serverData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ];

        $entity = $Servers->patchEntity($entity, $payload);

        if ($entity->hasErrors()) {
            return $this->jsonResponse(['status' => false, 'messages' => __('ERROR__FILL_ALL_FIELDS')]);
        }

        if (!$Servers->save($entity)) {
            return $this->jsonResponse(['status' => false, 'messages' => __('ERROR__INTERNAL_ERROR')]);
        }

        return $this->jsonResponse(['status' => true, 'messages' => __('SERVER__LINK_SUCCESS')]);
    }

    public function whitelist(?int $server_id = null): ?Response
    {
        $this->requireManageServers();

        $call = $this->serverBridge->call('GET_WHITELISTED_PLAYERS', $server_id ?? false);
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
        $this->requireManageServers();

        $call = $this->serverBridge->call('GET_PLAYER_LIST', $server_id ?? false);
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

    public function banlist(?int $server_id = null): ?Response
    {
        $this->requireManageServers();

        $call = $this->serverBridge->call('GET_BANNED_PLAYERS', $server_id ?? false);
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

    public function cmd(): ?Response
    {
        $this->requireManageServers();

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
        $this->requireManageServers();

        $this->disableAutoRender();

        $ServerCmds = $this->fetchTable('ServerCmds');
        $entity = $ServerCmds->get($id);
        $ServerCmds->delete($entity);

        return $this->redirect(['_name' => 'admin_server_cmd']);
    }

    public function executeCmd(): Response
    {
        $this->requireManageServers();
        $this->requireAjax();

        $cmd = (string)$this->getRequest()->getData('cmd', '');
        $serverIdRaw = $this->getRequest()->getData('server_id');
        $serverId = $this->normalizeServerId($serverIdRaw);

        if ($cmd === '' || $serverId === null) {
            return $this->jsonResponse([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]);
        }

        $this->serverBridge->sendCommand($cmd, $serverId);

        return $this->jsonResponse([
            'status' => true,
            'messages' => __('SERVER__SEND_COMMAND_SUCCESS'),
        ]);
    }

    public function addCmd(): Response
    {
        $this->requireManageServers();
        $this->requireAjax();

        $name = trim((string)$this->getRequest()->getData('name', ''));
        $cmd = trim((string)$this->getRequest()->getData('cmd', ''));
        $serverIdRaw = $this->getRequest()->getData('server_id');
        $serverId = $this->normalizeServerId($serverIdRaw);

        if ($name === '' || $cmd === '' || $serverId === null) {
            return $this->jsonResponse([
                'status' => false,
                'messages' => __('ERROR__FILL_ALL_FIELDS'),
            ]);
        }

        if (str_contains($cmd, '/')) {
            return $this->jsonResponse([
                'status' => false,
                'messages' => __('SERVER__CMD_SLASH'),
            ]);
        }

        $ServerCmds = $this->fetchTable('ServerCmds');
        $entity = $ServerCmds->newEntity([
            'name' => $name,
            'cmd' => $cmd,
            'server_id' => $serverId,
        ]);

        if ($entity->hasErrors() || !$ServerCmds->save($entity)) {
            return $this->jsonResponse([
                'status' => false,
                'messages' => __('ERROR__INTERNAL_ERROR'),
            ]);
        }

        return $this->jsonResponse([
            'status' => true,
            'messages' => __('SERVER__CMD_ADDED'),
        ]);
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
}
