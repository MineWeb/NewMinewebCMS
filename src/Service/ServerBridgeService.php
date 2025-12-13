<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use Rcon;
use xPaw\MinecraftPing;
use xPaw\MinecraftPingException;

final class ServerBridgeService
{
    use LocatorAwareTrait;

    public ?string $lastErrorMessage = null;
    public ?string $linkErrorCode = null;

    private ?int $timeout = null;
    private ?string $key = null;

    private array $configCache = [];
    private array $onlineCache = [];

    public function call(mixed $methods = [], int|false $serverId = false, bool $debug = false): mixed
    {
        $this->lastErrorMessage = null;
        $this->linkErrorCode = null;

        $serverId = $serverId ?: $this->getFirstServerId();
        if (!$serverId) {
            $this->lastErrorMessage = 'Unknown server.';

            return false;
        }

        if (!$methods) {
            $this->lastErrorMessage = 'Unknown method.';

            return false;
        }

        $config = $this->getServerConfig($serverId);
        if (!$config) {
            return false;
        }

        [$normalized, $multi] = $this->normalizeMethods($methods);

        if (in_array($config['type'], [1, 2, 3], true)) {
            $methodsName = array_map(static function (array $method): string {
                return array_keys($method)[0];
            }, $normalized);

            $result = [];

            if ($config['type'] === 2 && in_array('RUN_COMMAND', $methodsName, true)) {
                foreach ($normalized as $k => $method) {
                    $name = array_keys($method)[0];
                    if ($name !== 'RUN_COMMAND') {
                        continue;
                    }

                    $ok = $this->rcon(
                        [
                                'ip' => $config['ip'],
                                'port' => $config['data']['rcon_port'] ?? null,
                                'password' => $config['data']['rcon_password'] ?? null,
                            ],
                        (string)$method['RUN_COMMAND']
                    ) !== false;

                    $result[$k]['RUN_COMMAND'] = $ok;
                }

                $methodsName = array_values(array_filter($methodsName, static function (string $v): bool {
                    return $v !== 'RUN_COMMAND';
                }));
            }

            if (count($methodsName) > 0) {
                $ping = $this->ping([
                    'ip' => $config['ip'],
                    'port' => $config['port'],
                    'udp' => $config['type'] === 3,
                ]);

                foreach ($normalized as $k => $method) {
                    $name = array_keys($method)[0];
                    if (is_array($ping) && array_key_exists($name, $ping)) {
                        $result[$k][$name] = $ping[$name];
                    }
                }
            }

            $flat = $this->flattenResult($result, $multi);

            return $flat;
        }

        $url = $this->getUrl($config);
        if ($url === false) {
            return false;
        }

        $payload = json_encode($this->parse($normalized));
        if (!is_string($payload)) {
            $this->lastErrorMessage = 'Bad request payload.';

            return false;
        }

        $data = $this->encryptWithKey($payload);
        [$return, $code] = $this->request($url, $data);

        if ($debug) {
            $decoded = json_decode((string)$return, true);

            return ['get' => $url, 'return' => $decoded ?: $return];
        }

        if (!$return || $code !== 200) {
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

        return $this->flattenResult($parsed, $multi);
    }

    public function online(int|false $serverId = false): mixed
    {
        $serverId = $serverId ?: $this->getFirstServerId();
        if (!$serverId) {
            return $this->onlineCache[(int)$serverId] = false;
        }

        $Configurations = $this->fetchTable('Configurations');
        $configuration = $Configurations->find()->first();
        if ($configuration && (string)$configuration->get('server_state') === '0') {
            return $this->onlineCache[$serverId] = false;
        }

        if (array_key_exists($serverId, $this->onlineCache)) {
            return $this->onlineCache[$serverId];
        }

        $config = $this->getServerConfig($serverId);
        if (!$config) {
            return $this->onlineCache[$serverId] = false;
        }

        if (in_array($config['type'], [1, 2, 3], true)) {
            return $this->onlineCache[$serverId] = $this->ping([
                'ip' => $config['ip'],
                'port' => $config['port'],
                'udp' => $config['type'] === 3,
            ]);
        }

        [$return, $code] = $this->request((string)$this->getUrl($config), $this->encryptWithKey('[]'));
        if ($return && $code === 200) {
            return $this->onlineCache[$serverId] = true;
        }

        return $this->onlineCache[$serverId] = false;
    }

    public function pingPublic(array $config): array|false
    {
        return $this->ping($config);
    }

    public function sendCommand(string $cmd, int|false $serverId = false): mixed
    {
        return $this->commands([$cmd], $serverId);
    }

    public function commands(mixed $commands, int|false $serverId = false): mixed
    {
        if (!is_array($commands)) {
            $commands = str_replace('{PLAYER}', '', (string)$commands);
            $commands = explode('[{+}]', (string)$commands);
        }

        $calls = [];
        foreach ($commands as $command) {
            $calls[] = ['RUN_COMMAND' => (string)$command];
        }

        return $this->call($calls, $serverId);
    }

    public function scheduleCommands(mixed $commands, int $time, array $servers = []): bool
    {
        if (empty($servers)) {
            $first = $this->getFirstServerId();
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

            $cmds = $commands;
            if (!is_array($cmds)) {
                $cmds = explode('[{+}]', str_replace('{PLAYER}', '', (string)$cmds));
            }

            $calls = [];
            foreach ($cmds as $command) {
                $calls[] = ['RUN_SCHEDULED_COMMAND' => [(string)$command, '', $execTime]];
            }

            $this->call($calls, $server);
        }

        return true;
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

    public function bannerInfos(mixed $serverId = false): array
    {
        $serverId = $serverId ?: $this->getFirstServerId();
        if (!is_array($serverId)) {
            $serverId = [$serverId];
        }

        $Configurations = $this->fetchTable('Configurations');
        $configuration = $Configurations->find()->first();

        $cacheFolder = null;
        $cacheFile = null;
        $serverIdString = implode('-', array_map('strval', $serverId));

        if ($configuration && !empty($configuration->get('server_cache'))) {
            $cacheFolder = ROOT . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . 'cache' . DIRECTORY_SEPARATOR;
            $cacheFile = $cacheFolder . 'server.cache';

            if (is_file($cacheFile) && strtotime('+1 min', filemtime($cacheFile)) > time()) {
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
                @mkdir($cacheFolder, 0755, true);
            }

            $existing = [];
            if (is_file($cacheFile)) {
                $existingRaw = @file_get_contents($cacheFile);
                $existing = is_string($existingRaw) ? @unserialize($existingRaw) : [];
                if (!is_array($existing)) {
                    $existing = [];
                }
            }

            $existing[$serverIdString] = $data;
            @file_put_contents($cacheFile, serialize($existing));
        }

        return $data;
    }

    public function getSecretKey(): string
    {
        $Configurations = $this->fetchTable('Configurations');
        $config = $Configurations->find()->first();
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

        $entity = $Configurations->get((int)$config->get('id'));
        $entity->set('server_secretkey', $generated);
        $Configurations->save($entity);

        $this->key = $generated;

        return $generated;
    }

    private function getServerConfig(int $serverId): array|false
    {
        if (array_key_exists($serverId, $this->configCache) && $this->configCache[$serverId] !== []) {
            return $this->configCache[$serverId];
        }

        $Configurations = $this->fetchTable('Configurations');
        $Servers = $this->fetchTable('Servers');

        $configuration = $Configurations->find()->first();
        if (!$configuration) {
            return $this->configCache[$serverId] = false;
        }

        if ((int)$configuration->get('server_state') !== 1) {
            return $this->configCache[$serverId] = false;
        }

        $this->timeout = (int)$configuration->get('server_timeout');

        $server = $Servers->find()->where(['id' => $serverId])->first();
        if (!$server) {
            return $this->configCache[$serverId] = false;
        }

        $dataRaw = $server->get('data');
        $dataArr = is_string($dataRaw) ? json_decode($dataRaw, true) : null;

        return $this->configCache[$serverId] = [
            'ip' => (string)$server->get('ip'),
            'port' => (int)$server->get('port'),
            'type' => (int)$server->get('type'),
            'data' => is_array($dataArr) ? $dataArr : [],
        ];
    }

    private function getFirstServerId(): ?int
    {
        $Servers = $this->fetchTable('Servers');
        $row = $Servers->find()->select(['id'])->first();

        return $row ? (int)$row->get('id') : null;
    }

    private function getUrl(array $config): string|false
    {
        if (empty($config['ip']) || empty($config['port'])) {
            return false;
        }

        return 'http://' . $config['ip'] . ':' . $config['port'] . '/ask';
    }

    private function getTimeout(): int
    {
        if ($this->timeout !== null) {
            return $this->timeout;
        }

        $Configurations = $this->fetchTable('Configurations');
        $row = $Configurations->find()->first();
        $this->timeout = $row ? (int)$row->get('server_timeout') : 5;

        return $this->timeout;
    }

    private function ping(array $config): array|false
    {
        if (!isset($config['ip'], $config['port'])) {
            return false;
        }

        try {
            $query = new MinecraftPing(
                (string)$config['ip'],
                (int)$config['port'],
                $this->getTimeout(),
                (bool)($config['udp'] ?? false)
            );
            $info = $query->Query();
        } catch (MinecraftPingException) {
            return false;
        } finally {
            if (isset($query)) {
                $query->Close();
            }
        }

        if (!isset($info['players'])) {
            return false;
        }

        return [
            'GET_MOTD' => $info['description'] ?? null,
            'GET_VERSION' => $info['version']['name'] ?? null,
            'GET_PLAYER_COUNT' => $info['players']['online'] ?? 0,
            'GET_MAX_PLAYERS' => $info['players']['max'] ?? 0,
        ];
    }

    private function rcon(mixed $config = false, string $cmd = ''): mixed
    {
        if (!is_array($config) || !isset($config['ip'], $config['port'], $config['password'])) {
            return false;
        }

        if (!class_exists('Rcon')) {
            return false;
        }

        $rcon = new Rcon((string)$config['ip'], (int)$config['port'], (string)$config['password'], $this->getTimeout());
        if ($rcon->connect()) {
            return $rcon->sendCommand($cmd);
        }

        return false;
    }

    private function normalizeMethods(mixed $methods): array
    {

        if (!is_array($methods)) {
            return [[[ (string)$methods => [] ]], false];
        }

        if (isset($methods[0])) {
            return [$methods, true];
        }

        $normalized = [];
        foreach ($methods as $name => $args) {
            $normalized[] = [$name => (is_array($args) ? $args : [$args])];
        }

        return [$normalized, false];
    }

    private function parse(array $methods): array
    {
        $result = [];

        foreach ($methods as $method) {
            if (!is_array($method)) {
                $result[] = ['name' => (string)$method, 'args' => []];
                continue;
            }

            foreach ($method as $name => $args) {
                $result[] = [
                    'name' => (string)$name,
                    'args' => is_array($args) ? $args : [$args],
                ];
            }
        }

        return $result;
    }

    private function request(string $url, string $data, int|false $timeout = false): array
    {
        $timeout = $timeout ?: $this->getTimeout();

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

    private function encryptWithKey(string $data): string
    {
        if ($this->key === null) {
            $Configurations = $this->fetchTable('Configurations');
            $row = $Configurations->find()->first();
            $this->key = $row ? (string)$row->get('server_secretkey') : '';
        }

        $iv = random_bytes(16);

        $data = $this->pkcs5Pad($data, 16);

        $signed = openssl_encrypt($data, 'aes-128-cbc', substr((string)$this->key, 0, 16), OPENSSL_ZERO_PADDING, $iv);
        if ($signed === false) {
            $signed = '';
        }

        return (string)json_encode([
            'signed' => $signed,
            'iv' => base64_encode($iv),
        ]);
    }

    private function decryptWithKey(string $signed, string $iv): string|false
    {
        if ($this->key === null) {
            $Configurations = $this->fetchTable('Configurations');
            $row = $Configurations->find()->first();
            $this->key = $row ? (string)$row->get('server_secretkey') : '';
        }

        $ivBin = base64_decode($iv, true);
        if ($ivBin === false) {
            return false;
        }

        return openssl_decrypt($signed, 'aes-128-cbc', substr((string)$this->key, 0, 16), OPENSSL_ZERO_PADDING, $ivBin);
    }

    private function pkcs5Pad(string $text, int $blocksize): string
    {
        $pad = $blocksize - (strlen($text) % $blocksize);

        return $text . str_repeat(chr($pad), $pad);
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

    private function flattenResult(mixed $parsed, bool $multi): mixed
    {
        if ($multi) {
            return $parsed;
        }

        $flat = [];
        foreach ((array)$parsed as $item) {
            foreach ((array)$item as $k => $v) {
                $flat[$k] = $v;
            }
        }

        return $flat;
    }
}
