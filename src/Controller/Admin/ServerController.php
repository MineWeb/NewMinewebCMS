<?php
namespace App\Controller\Admin;

use App\Controller\AppController;
use Cake\Http\Exception\ForbiddenException;
use Cake\Http\Exception\NotFoundException;
use Cake\ORM\TableRegistry;

class ServerController extends AppController
{
    public function link()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS'))
            throw new ForbiddenException();

        $this->set('title_for_layout', $this->Lang->get('SERVER__LINK'));

        $this->Server = TableRegistry::getTableLocator()->get('Server');
        $servers = $this->Server->find()->all()->toArray();
        $banner_server = unserialize($this->Configuration->getKey('banner_server'));

        if ($banner_server) {
            foreach ($servers as $key => $value) {
                if (in_array($value['id'], $banner_server))
                    $servers[$key]['activeInBanner'] = true;
                else
                    $servers[$key]['activeInBanner'] = false;
            }
        }

        foreach ($servers as $key => $value)
            $servers[$key]['data'] = json_decode($value['data'], true);

        $bannerMsg = $this->Lang->get('SERVER__STATUS_MESSAGE');

        $this->set(compact('servers', 'bannerMsg'));
        $this->set('isEnabled', $this->Configuration->getKey('server_state'));
        $this->set('isCacheEnabled', $this->Configuration->getKey('server_cache'));
        $this->set('timeout', $this->Configuration->getKey('server_timeout'));
    }

    public function cmd()
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS'))
            throw new ForbiddenException();
        $this->set('title_for_layout', $this->Lang->get('SERVER__CMD'));

        $this->ServerCmd = TableRegistry::getTableLocator()->get('ServerCmd');
        $this->Server = TableRegistry::getTableLocator()->get('Server');
        $search_cmd = $this->ServerCmd->find('all', order: 'server_id DESC')->all();
        $search_server = $this->Server->find()->all();
        $this->set(compact(
            'search_cmd',
            'search_server'
        ));
    }

    public function deleteCmd($id)
    {
        $this->disableAutoRender();
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS'))
            throw new ForbiddenException();

        $this->ServerCmd = TableRegistry::getTableLocator()->get('ServerCmd');
        $this->ServerCmd->delete($this->ServerCmd->get($id));
        $this->redirect(['action' => 'cmd', 'admin' => true]);
    }

    public function executeCmd()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS'))
            throw new ForbiddenException();

        $this->ServerComponent = $this->loadComponent('Server');
        $call = $this->ServerComponent->send_command($this->request->getData('cmd'), $this->request->getData('server_id'));

        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SERVER__SEND_COMMAND_SUCCESS')]));
    }

    public function addCmd()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS'))
            throw new ForbiddenException();

        if ($this->request->is('ajax')) {
            if (!empty($this->request->getData('name')) and !empty($this->request->getData('cmd')) and !empty($this->request->getData('server_id'))) {
                if (!str_contains($this->request->getData('cmd'), '/')) {
                    $this->ServerCmd = TableRegistry::getTableLocator()->get('ServerCmd');
                    $cmd = $this->ServerCmd->newEntity([
                            'name' => $this->request->getData('name'),
                            'cmd' => $this->request->getData('cmd'),
                            'server_id' => $this->request->getData('server_id')
                    ]);
                    $this->ServerCmd->save($cmd);

                    return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SERVER__CMD_ADD')]));
                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('SERVER__CMD_SLASH')]));
                }
            } else {
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
            }
        } else {
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')]));
        }
    }

    public function editBannerMsg()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if ($this->isConnected and $this->Permissions->can('MANAGE_SERVERS')) {
            if ($this->request->is('ajax')) {
                $this->Lang->set('SERVER__STATUS_MESSAGE', $this->request->getData('msg'));

                return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SERVER__EDIT_BANNER_MSG_SUCCESS')]));
            } else {
                throw new NotFoundException();
            }
        } else {
            throw new ForbiddenException();
        }
    }

    public function switchState()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_SERVERS')) {
            $this->disableAutoRender();

            $value = ($this->Configuration->getKey('server_state')) ? 0 : 1;
            $this->Configuration->setKey('server_state', $value);

            $this->Flash->success($this->Lang->get('SERVER__SUCCESS_SWITCH'));
            $this->redirect(['action' => 'link', 'admin' => true]);
        } else {
            throw new ForbiddenException();
        }
    }

    public function switchCacheState()
    {
        if ($this->isConnected and $this->Permissions->can('MANAGE_SERVERS')) {
            $this->disableAutoRender();

            $value = ($this->Configuration->getKey('server_cache')) ? 0 : 1;
            $this->Configuration->setKey('server_cache', $value);

            $this->Flash->success($this->Lang->get('SERVER__SUCCESS_CACHE_SWITCH'));
            $this->redirect(['action' => 'link', 'admin' => true]);

        } else {
            throw new ForbiddenException();
        }
    }

    public function switchBanner($id = false)
    {
        $this->disableAutoRender();
        if ($this->isConnected && $this->Permissions->can('MANAGE_SERVERS')) {
            if ($id) {
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
        } else {
            throw new ForbiddenException();
        }
    }


    public function delete($id = false)
    {
        $this->disableAutoRender();
        if ($this->isConnected && $this->Permissions->can('MANAGE_SERVERS')) {
            if ($id) {
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

                    $this->Flash->success($this->Lang->get('SERVER__DELETE_SERVER_SUCCESS'));
                } else {
                    $this->Flash->error($this->Lang->get('ERROR__INTERNAL_ERROR'));
                }
            } else {
                $this->Flash->error($this->Lang->get('ERROR__INTERNAL_ERROR'));
            }

            $this->redirect(['controller' => 'server', 'action' => 'link', 'admin' => true]);
        } else {
            $this->redirect('/');
        }
    }

    public function config()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        if ($this->isConnected and $this->Permissions->can('MANAGE_SERVERS')) {
            if ($this->request->is('ajax')) {
                if (!empty($this->request->getData('timeout'))) {
                    if (filter_var($this->request->getData('timeout'), FILTER_VALIDATE_FLOAT)) {
                        $this->Configuration->setKey('server_timeout', $this->request->getData('timeout'));

                        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SERVER__TIMEOUT_SAVE_SUCCESS')]));
                    } else {
                        return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('SERVER__INVALID_TIMEOUT')]));
                    }
                } else {
                    return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
                }
            } else {
                throw new NotFoundException();
            }
        } else {
            throw new ForbiddenException();
        }
    }

    public function linkAjax()
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');

        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS'))
            return $this->redirect('/');
        if (!$this->request->is('ajax'))
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__BAD_REQUEST')]));
        if (empty($this->request->getData('host')) || empty($this->request->getData('port')) || empty($this->request->getData('name')) || $this->request->getData('type') == null)
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));

        /*
         * Link ID
         * 0 : Plugin
         * 1 : Ping
         * 2 : Rcon
         * 3 : Ping MCPE
         */

        if ($this->request->getData('type') == 0) {
            $timeout = $this->Configuration->getKey('server_timeout');
            if (empty($timeout))
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('SERVER__TIMEOUT_UNDEFINED')]));

            if (!$this->Server->check('connection', ['host' => $this->request->getData('host'), 'port' => $this->request->getData('port'), 'timeout' => $timeout])) {
                $msg = $this->Lang->get('SERVER__LINK_ERROR_' . $this->Server->linkErrorCode);
                $msg .= $this->linkDebugFull($msg, $this->request->getData('host'), $this->request->getData('port'));
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $msg]));
            }

        } // use simple ping to retrieve data from MC protocol
        else if ($this->request->getData('type') == 1 || $this->request->getData('type') == 3) {
            if (!$this->Server->ping(['ip' => $this->request->getData('host'), 'port' => $this->request->getData('port'), 'udp' => $this->request->getData('type') == 3])) {
                $msg = $this->Lang->get('SERVER__LINK_ERROR_FAILED');
                $msg .= $this->linkDebugPing();
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $msg]));
            }
        } else if ($this->request->getData('type') == 2) {
            if (!$this->Server->rcon(
                [
                    'ip' => $this->request->getData('host'),
                    'port' => $this->request->getData('server_data')['rcon_port'],
                    'password' => $this->request->getData('server_data')['rcon_password']
                ],
                'say ' . $this->Lang->get('SERVER__LINK_SUCCESS')
            )
            ) {
                $msg = $this->Lang->get('SERVER__LINK_ERROR_FAILED');
                $msg .= $this->linkDebugPing();
                return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $msg]));
            }
        } else {
            return $this->response->withStringBody(json_encode(['statut' => false, 'msg' => $this->Lang->get('ERROR__FILL_ALL_FIELDS')]));
        }

        // save the server inside the database/conf
        $this->Configuration->setKey('server_state', 1);

        $this->Server = TableRegistry::getTableLocator()->get('Server');
        if (!empty($this->request->getData('id'))) {
            $server = $this->Server->get($this->request->getData('id'));
        } else {
            $server = $this->Server->newEmptyEntity();
        }

        $server->set([
            'name' => $this->request->getData('name'),
            'ip' => $this->request->getData('host'),
            'port' => $this->request->getData('port'),
            'type' => $this->request->getData('type'),
            'data' => $this->request->getData('server_data') !== null ? json_encode($this->request->getData('server_data')) : '[]'
        ]);
        $this->Server->save($server);

        return $this->response->withStringBody(json_encode(['statut' => true, 'msg' => $this->Lang->get('SERVER__LINK_SUCCESS')]));
    }

    public function banlist($server_id = false)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS'))
            throw new ForbiddenException();

        $call = $this->Server->call('GET_BANNED_PLAYERS', $server_id);
        $list = [];
        if (isset($call['GET_BANNED_PLAYERS']) && $call['GET_BANNED_PLAYERS'] !== 'NOT_FOUND')
            foreach ($call['GET_BANNED_PLAYERS'] as $player)
                $list[] = $player;
        $this->set(compact('list'));

        $this->Server = TableRegistry::getTableLocator()->get('Server');
        $this->set('servers', $this->Server->find('all', conditions: ['type' => 0])->all());

        $this->set('title_for_layout', $this->Lang->get('SERVER__BANLIST'));
    }

    public function whitelist($server_id = false)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS'))
            throw new ForbiddenException();

        $call = $this->Server->call('GET_WHITELISTED_PLAYERS', $server_id);
        $list = [];
        if (isset($call['GET_WHITELISTED_PLAYERS']) && $call['GET_WHITELISTED_PLAYERS'] !== 'NOT_FOUND')
            foreach ($call['GET_WHITELISTED_PLAYERS'] as $player)
                $list[] = $player;
        $this->set(compact('list'));

        $this->Server = TableRegistry::getTableLocator()->get('Server');
        $this->set('servers', $this->Server->find('all', conditions: ['type' => 0]));

        $this->set('title_for_layout', $this->Lang->get('SERVER__WHITELIST'));
    }

    public function online($server_id = false)
    {
        if (!$this->isConnected || !$this->Permissions->can('MANAGE_SERVERS'))
            throw new ForbiddenException();

        $call = $this->Server->call('GET_PLAYER_LIST', $server_id);
        $list = [];
        if (isset($call['GET_PLAYER_LIST']) && $call['GET_PLAYER_LIST'] !== 'NOT_FOUND')
            foreach ($call['GET_PLAYER_LIST'] as $player)
                $list[] = $player;
        $this->set(compact('list'));

        $this->Server = TableRegistry::getTableLocator()->get('Server');
        $this->set('servers', $this->Server->find('all', conditions: ['type' => 0]));

        $this->set('title_for_layout', $this->Lang->get('SERVER__STATUS_ONLINE'));
    }

    private function linkDebugFull($msg, $host, $port, $udp = false)
    {
        $msg .= $this->linkDebugPing();

        $msg .= "<br /><br />";
        $msg .= "<i class=\"fa fa-times\"></i> ";

        if ($this->Server->ping(['ip' => $host, 'port' => $port, 'udp' => $udp]))
            $msg .= $this->Lang->get('SERVER__SEEMS_USED');
        else
            $msg .= $this->Lang->get('SERVER__PORT_CLOSE_OR_BAD');

        return $msg;
    }

    private function linkDebugPing()
    {
        $msg = "<br /><br />";

        $hypixelIp = gethostbyname('mc.hypixel.net');
        if ($this->Server->ping(['ip' => $hypixelIp, 'port' => 25565, 'udp' => false])) {
            $msg .= "<i class=\"fa fa-check\"></i> ";
            $msg .= $this->Lang->get('SERVER__PORT_OPEN');
        } else {
            $msg .= "<i class=\"fa fa-times\"></i> ";
            $msg .= $this->Lang->get('SERVER__SEEMS_CLOSE_OR_BLOCKED');
        }

        return $msg;
    }

}
