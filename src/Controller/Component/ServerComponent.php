<?php
declare(strict_types=1);

namespace App\Controller\Component;

use Cake\Controller\Component;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;

final class ServerComponent extends Component
{
    use LocatorAwareTrait;

    public ?string $lastErrorMessage = null;
    public ?string $linkErrorCode = null;

    private ?int $timeout = null;

    private array $configCache = [];
    private array $onlineCache = [];

    private ?string $key = null;

    private Table $Configurations;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->Configurations = $this->fetchTable('Configurations');
    }

    public function getServerIdConnected(string $username, string $type = 'BUKKIT'): int|false
    {
        $Servers = TableRegistry::getTableLocator()->get('Servers');

        foreach ($Servers->find()->where(['type' => 0])->all() as $srv) {
            $serverId = (int)$srv->get('id');

            $serverType = $this->getServerType($serverId);
            $check = ((string)$serverType === $type) || ($type === 'ALL');

            if ($this->userIsConnected($username, $serverId) && $check) {
                return $serverId;
            }
        }

        return false;
    }

    public function getServerType(int|false $server_id = false): mixed
    {
        $server_id = $server_id ?: $this->getFirstServerID();
        if (!$server_id) {
            return false;
        }

        $res = $this->call(['GET_PLUGIN_TYPE' => []], $server_id);

        return is_array($res) ? ($res['GET_PLUGIN_TYPE'] ?? false) : false;
    }

    public function getFirstServerID(): ?int
    {
        $Servers = TableRegistry::getTableLocator()->get('Servers');
        $row = $Servers->find()->select(['id'])->first();

        return $row ? (int)$row->get('id') : null;
    }

    public function call(mixed $methods = [], int|false $server_id = false, bool $debug = false): mixed
    {
        $this->lastErrorMessage = null;
        $this->linkErrorCode = null;

        $multi = true;

        $server_id = $server_id ?: $this->getFirstServerID();
        if (!$server_id) {
            $this->lastErrorMessage = 'Unknown server.';

            return false;
        }

        if (!$methods) {
            $this->lastErrorMessage = 'Unknown method.';

            return false;
        }

        $config = $this->getServerConfig($server_id);
        if (!$config) {
            return false;
        }

        if (!is_array($methods)) {
            $methods = [[$methods => []]];
            $multi = false;
        } elseif (!isset($methods[0])) {
            $normalized = [];
            foreach ($methods as $name => $args) {
                $normalized[] = [$name => is_array($args) ? $args : [$args]];
            }
            $methods = $normalized;
            $multi = false;
        }

        if ($config['type'] === 1 || $config['type'] === 2 || $config['type'] === 3) {
            $methodsName = array_map(static function ($method) {
                return array_keys($method)[0];
            }, $methods);

            $result = [];

            if ($config['type'] === 2 && in_array('RUN_COMMAND', $methodsName, true)) {
                foreach ($methods as $key => $method) {
                    if (array_keys($method)[0] === 'RUN_COMMAND') {
                        $result[$key]['RUN_COMMAND'] = $this->rcon(
                            [
                                    'ip' => $config['ip'],
                                    'port' => $config['data']['rcon_port'] ?? null,
                                    'password' => $config['data']['rcon_password'] ?? null,
                                ],
                            (string)$method['RUN_COMMAND']
                        ) !== false;
                    }
                }

                $methodsName = array_values(array_filter($methodsName, static function ($v) {
                    return $v !== 'RUN_COMMAND';
                }));
            }

            if (count($methodsName) > 0) {
                $ping = $this->ping([
                    'ip' => $config['ip'],
                    'port' => $config['port'],
                    'udp' => ($config['type'] === 3),
                ]);

                foreach ($methods as $key => $method) {
                    $name = array_keys($method)[0];
                    if (is_array($ping) && array_key_exists($name, $ping)) {
                        $result[$key][$name] = $ping[$name];
                    }
                }
            }

            if (!$multi) {
                $flat = [];
                foreach ($result as $item) {
                    foreach ($item as $k => $v) {
                        $flat[$k] = $v;
                    }
                }

                return $flat;
            }

            return $result;
        }

        $url = $this->getUrl($server_id);
        if (!$url) {
            return false;
        }

        $payload = json_encode($this->parse($methods));
        if (!is_string($payload)) {
            $this->lastErrorMessage = 'Bad request payload.';

            return false;
        }

        $data = $this->encryptWithKey($payload);

        [$return, $code] = $this->request($url, $data);

        if ($debug) {
            $decoded = json_decode((string)$return);

            return ['get' => $url, 'return' => $decoded ?: $return];
        }

        if ($return && $code === 200) {
            $returnArr = json_decode((string)$return, true);
            if (!is_array($returnArr) || !isset($returnArr['signed'], $returnArr['iv'])) {
                $this->lastErrorMessage = 'Bad response.';

                return false;
            }

            $decrypted = $this->decryptWithKey((string)$returnArr['signed'], (string)$returnArr['iv']);
            if ($decrypted === false) {
                $this->lastErrorMessage = 'Bad response.';

                return false;
            }

            $decoded = json_decode((string)$decrypted, true);
            $parsed = $this->parseResult($decoded);

            if (!$multi) {
                $flat = [];
                foreach ((array)$parsed as $item) {
                    foreach ($item as $k => $v) {
                        $flat[$k] = $v;
                    }
                }

                return $flat;
            }

            return $parsed;
        }

        if ($code === 403 || $code === 500) {
            $this->lastErrorMessage = 'Request not allowed.';

            return false;
        }

        if ($code === 400) {
            $this->lastErrorMessage = 'Plugin not installed or bad request.';

            return false;
        }

        $this->lastErrorMessage = 'Request timeout.';

        return false;
    }

    public function getServerConfig(int|false $server_id = false): array|false
    {
        $server_id = $server_id ?: $this->getFirstServerID();
        if (!$server_id) {
            $this->configCache[(int)$server_id] = false;

            return false;
        }

        if (array_key_exists($server_id, $this->configCache) && $this->configCache[$server_id] !== []) {
            return $this->configCache[$server_id];
        }

        $configuration = $this->Configurations->find()->first();
        if (!$configuration) {
            return $this->configCache[$server_id] = false;
        }

        if ((int)$configuration->get('server_state') !== 1) {
            return $this->configCache[$server_id] = false;
        }

        $this->timeout = (int)$configuration->get('server_timeout');

        $Servers = TableRegistry::getTableLocator()->get('Servers');
        $server = $Servers->find()->where(['id' => $server_id])->first();
        if (!$server) {
            return $this->configCache[$server_id] = false;
        }

        $dataRaw = $server->get('data');
        $dataArr = is_string($dataRaw) ? json_decode($dataRaw, true) : null;

        return $this->configCache[$server_id] = [
            'ip' => (string)$server->get('ip'),
            'port' => (int)$server->get('port'),
            'type' => (int)$server->get('type'),
            'data' => is_array($dataArr) ? $dataArr : [],
        ];
    }

    public function rcon(mixed $config = false, string $cmd = ''): mixed
    {
        if (!is_array($config) || !isset($config['ip'], $config['port'], $config['password'])) {
            return false;
        }

        $rcon = new Rcon((string)$config['ip'], (int)$config['port'], (string)$config['password'], $this->getTimeout());
        if ($rcon->connect()) {
            return $rcon->sendCommand($cmd);
        }

        return false;
    }

    private function getTimeout(): int
    {
        if ($this->timeout !== null) {
            return $this->timeout;
        }

        $row = $this->Configurations->find()->first();
        $this->timeout = $row ? (int)$row->get('server_timeout') : 5;

        return $this->timeout;
    }

    public function ping(mixed $config = false): array|false
    {
        if (!is_array($config) || !isset($config['ip'], $config['port'])) {
            return false;
        }

        try {
            $Query = new MinecraftPing(
                (string)$config['ip'],
                (int)$config['port'],
                $this->getTimeout(),
                (bool)($config['udp'] ?? false)
            );
            $Info = $Query->Query();
        } catch (MinecraftPingException) {
            return false;
        } finally {
            if (isset($Query)) {
                $Query->Close();
            }
        }

        if (!isset($Info['players'])) {
            return false;
        }

        return [
            'GET_MOTD' => $Info['description'] ?? null,
            'GET_VERSION' => $Info['version']['name'] ?? null,
            'GET_PLAYER_COUNT' => $Info['players']['online'] ?? 0,
            'GET_MAX_PLAYERS' => $Info['players']['max'] ?? 0,
        ];
    }

    public function getUrl(int $server_id): string|false
    {
        if (!$server_id) {
            return false;
        }

        $config = $this->getServerConfig($server_id);
        if (!$config) {
            return false;
        }

        return 'http://' . $config['ip'] . ':' . $config['port'] . '/ask';
    }

    private function encryptWithKey(string $data): string
    {
        if ($this->key === null) {
            $row = $this->Configurations->find()->first();
            $this->key = $row ? (string)$row->get('server_secretkey') : '';
        }

        $iv_size = openssl_cipher_iv_length('aes-128-cbc');
        $iv = openssl_random_pseudo_bytes($iv_size);

        $data = $this->pkcs5_pad($data, 16);

        $signed = openssl_encrypt($data, 'aes-128-cbc', substr((string)$this->key, 0, 16), OPENSSL_ZERO_PADDING, $iv);
        if ($signed === false) {
            $signed = '';
        }

        return json_encode(['signed' => $signed, 'iv' => base64_encode($iv)]);
    }

    private function pkcs5_pad(string $text, int $blocksize): string
    {
        $pad = $blocksize - (strlen($text) % $blocksize);

        return $text . str_repeat(chr($pad), $pad);
    }

    private function parse(array $methods): array
    {
        $result = [];

        foreach ($methods as $method) {
            if (!is_array($method)) {
                $result[] = ['name' => $method, 'args' => []];
                continue;
            }

            foreach ($method as $name => $args) {
                $result[] = [
                    'name' => $name,
                    'args' => is_array($args) ? $args : [$args],
                ];
            }
        }

        return $result;
    }

    private function request(string $url, string $data, int|false $timeout = false): array
    {
        if (!$timeout) {
            $timeout = $this->getTimeout();
        }

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_COOKIESESSION, true);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $data);
        curl_setopt($curl, CURLOPT_TIMEOUT, (int)$timeout);
        curl_setopt($curl, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($data),
        ]);

        $return = curl_exec($curl);
        $code = (int)curl_getinfo($curl, CURLINFO_HTTP_CODE);
        curl_close($curl);

        return [$return, $code];
    }

    private function decryptWithKey(string $data, string $iv): string|false
    {
        if ($this->key === null) {
            $row = $this->Configurations->find()->first();
            $this->key = $row ? (string)$row->get('server_secretkey') : '';
        }

        $ivBin = base64_decode($iv, true);
        $dataBin = base64_decode($data, true);

        if ($ivBin === false || $dataBin === false) {
            return false;
        }

        return openssl_decrypt($dataBin, 'AES-128-CBC', substr((string)$this->key, 0, 16), OPENSSL_RAW_DATA, $ivBin);
    }

    private function parseResult(mixed $result): array
    {
        $methods = [];
        if (!is_array($result)) {
            return $methods;
        }

        foreach ($result as $method) {
            if (!is_array($method) || !isset($method['name'])) {
                continue;
            }
            $methods[] = [$method['name'] => ($method['response'] ?? null)];
        }

        return $methods;
    }

    public function userIsConnected(string $username, int|false $server_id = false): bool
    {
        $result = $this->call(['IS_CONNECTED' => $username], $server_id);
        if (is_array($result) && !empty($result['IS_CONNECTED'])) {
            return true;
        }

        $cfg = $this->getServerConfig($server_id);
        if (is_array($cfg) && ($cfg['type'] ?? null) === 2) {
            return true;
        }

        return false;
    }

    public function serversOnline(): bool
    {
        foreach ($this->getAllServers() as $server) {
            $serverId = (int)($server['server_id'] ?? 0);
            if ($serverId && $this->online($serverId) !== false) {
                return true;
            }
        }

        return false;
    }

    public function getAllServers(): array
    {
        $Servers = TableRegistry::getTableLocator()->get('Servers');

        $return = [];
        foreach ($Servers->find()->all() as $value) {
            $return[] = ['server_id' => (int)$value->get('id')];
        }

        return $return;
    }

    public function online(int|false $server_id = false): mixed
    {
        $server_id = $server_id ?: $this->getFirstServerID();
        if (!$server_id) {
            return $this->onlineCache[(int)$server_id] = false;
        }

        $configuration = $this->Configurations->find()->first();
        if ($configuration && (string)$configuration->get('server_state') === '0') {
            return $this->onlineCache[$server_id] = false;
        }

        if (array_key_exists($server_id, $this->onlineCache)) {
            return $this->onlineCache[$server_id];
        }

        $config = $this->getServerConfig($server_id);
        if (!$config) {
            return $this->onlineCache[$server_id] = false;
        }

        if ($config['type'] === 1 || $config['type'] === 2 || $config['type'] === 3) {
            return $this->onlineCache[$server_id] = $this->ping([
                'ip' => $config['ip'],
                'port' => $config['port'],
                'udp' => ($config['type'] === 3),
            ]);
        }

        [$return, $code] = $this->request((string)$this->getUrl($server_id), $this->encryptWithKey('[]'));
        if ($return && $code === 200) {
            return $this->onlineCache[$server_id] = true;
        }

        return $this->onlineCache[$server_id] = false;
    }

    public function check(mixed $info, array $value): bool
    {
        $this->lastErrorMessage = null;
        $this->linkErrorCode = null;

        if (empty($info) || empty($value)) {
            return false;
        }

        if (!isset($value['host'], $value['port'])) {
            return false;
        }

        $path = 'http://' . $value['host'] . ':' . $value['port'] . '/handshake';
        $payload = json_encode([
            'secretKey' => substr($this->getSecretKey(), 0, 16),
            'domain' => Router::url('/', true),
        ]);

        if (!is_string($payload)) {
            $this->lastErrorMessage = 'Invalid params';
            $this->linkErrorCode = 'INVALID_PARAMS';

            return false;
        }

        [$return, $code] = $this->request($path, (string)$payload, (int)($value['timeout'] ?? $this->getTimeout()));

        if ($return && $code === 200) {
            return true;
        }

        switch ($code) {
            case 403:
                $this->lastErrorMessage = 'Already link';
                $this->linkErrorCode = 'ALREADY_LINKED';
                break;
            case 400:
                $this->lastErrorMessage = 'Invalid params';
                $this->linkErrorCode = 'INVALID_PARAMS';
                break;
            default:
                $this->lastErrorMessage = 'Server connection failed: ' . $code;
                $this->linkErrorCode = 'FAILED';
                break;
        }

        return false;
    }

    public function getSecretKey(): string
    {
        $config = $this->Configurations->find()->first();
        if (!$config) {
            return '';
        }

        $existing = (string)$config->get('server_secretkey');
        if ($existing !== '') {
            $this->key = $existing;

            return $existing;
        }

        $possible = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        $generated = '';
        for ($i = 0; $i < 32; $i++) {
            $generated .= $possible[random_int(0, 61)];
        }

        $entity = $this->Configurations->get((int)$config->get('id'));
        $entity->set('server_secretkey', $generated);
        $this->Configurations->save($entity);

        $this->key = $generated;

        return $generated;
    }

    public function banner_infos(mixed $serverId = false): array
    {
        $serverId = $serverId ?: $this->getFirstServerID();
        if (!is_array($serverId)) {
            $serverId = [$serverId];
        }

        $configuration = $this->Configurations->find()->first();

        $cacheFolder = null;
        $cacheFile = null;
        $serverIdString = implode('-', array_map('strval', $serverId));

        if ($configuration && !empty($configuration->get('server_cache'))) {
            $cacheFolder = ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
            $cacheFile = $cacheFolder . 'server.cache';

            if (file_exists($cacheFile) && strtotime('+1 min', filemtime($cacheFile)) > time()) {
                $cacheContent = @unserialize((string)@file_get_contents($cacheFile));
                if (is_array($cacheContent) && isset($cacheContent[$serverIdString]) && is_array($cacheContent[$serverIdString])) {
                    return $cacheContent[$serverIdString];
                }
            }
        }

        $data = [
            'GET_PLAYER_COUNT' => 0,
            'GET_MAX_PLAYERS' => 0,
        ];

        foreach ($serverId as $id) {
            $id = (int)$id;
            if (!$id) {
                continue;
            }

            $req = $this->call(['GET_PLAYER_COUNT' => [], 'GET_MAX_PLAYERS' => []], $id);
            if (!is_array($req)) {
                continue;
            }

            $data['GET_PLAYER_COUNT'] += (int)($req['GET_PLAYER_COUNT'] ?? 0);
            $data['GET_MAX_PLAYERS'] += (int)($req['GET_MAX_PLAYERS'] ?? 0);
        }

        if ($cacheFolder && $cacheFile) {
            if (!is_dir($cacheFolder)) {
                mkdir($cacheFolder, 0755, true);
            }
            @file_put_contents($cacheFile, serialize([$serverIdString => $data]));
        }

        return $data;
    }

    public function send_command(string $cmd, int|false $server_id = false): mixed
    {
        return $this->commands([$cmd], $server_id);
    }

    public function commands(mixed $commands, int|false $server_id = false): mixed
    {
        if (!is_array($commands)) {
            $commands = str_replace('{PLAYER}', '', (string)$commands);
            $commands = explode('[{+}]', (string)$commands);
        }

        $calls = [];
        foreach ($commands as $command) {
            $calls[] = ['RUN_COMMAND' => $command];
        }

        return $this->call($calls, $server_id);
    }

    public function scheduleCommands(mixed $commands, int $time, array $servers = []): bool
    {
        if (empty($servers)) {
            $first = $this->getFirstServerID();
            if ($first) {
                $servers[] = $first;
            }
        }

        foreach ($servers as $server) {
            $server = (int)$server;

            $serverTimestamp = $this->call('GET_SERVER_TIMESTAMP', $server);
            if (!is_array($serverTimestamp) || !isset($serverTimestamp['GET_SERVER_TIMESTAMP'])) {
                return false;
            }

            $timestamp = (int)$serverTimestamp['GET_SERVER_TIMESTAMP'];
            $execTime = $time * 60000 + $timestamp;

            if (!is_array($commands)) {
                $commands = explode('[{+}]', str_replace('{PLAYER}', '', (string)$commands));
            }

            $calls = [];
            foreach ($commands as $command) {
                $calls[] = ['RUN_SCHEDULED_COMMAND' => [$command, '', $execTime]];
            }

            $this->call($calls, $server);
        }

        return true;
    }
}
