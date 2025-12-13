<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Service\ServerBridgeService;
use Cake\Controller\Component;

final class ServerComponent extends Component
{
    private ServerBridgeService $service;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->service = new ServerBridgeService();
    }

    public function call(mixed $methods, int|false|null $serverId = false): mixed
    {
        return $this->service->call($methods, $serverId ?? false);
    }

    public function online(int|false|null $serverId = false): bool
    {
        return (bool)$this->service->online($serverId ?? false);
    }

    public function ping(array $config): array|false
    {
        return $this->service->pingPublic($config);
    }

    public function sendCommand(string $cmd, int|false|null $serverId = false): mixed
    {
        return $this->service->sendCommand($cmd, $serverId ?? false);
    }

    public function commands(mixed $commands, int|false|null $serverId = false): mixed
    {
        return $this->service->commands($commands, $serverId ?? false);
    }

    public function scheduleCommands(mixed $commands, int $time, array $servers = []): bool
    {
        return $this->service->scheduleCommands($commands, $time, $servers);
    }

    public function check(mixed $info, array $value): bool
    {
        return $this->service->check($info, $value);
    }

    public function bannerInfos(mixed $serverId = false): array
    {
        return $this->service->bannerInfos($serverId);
    }

    public function getSecretKey(): string
    {
        return $this->service->getSecretKey();
    }
}
