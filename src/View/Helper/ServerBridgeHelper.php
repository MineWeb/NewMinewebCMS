<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\ServerBridgeService;
use Cake\View\Helper;

final class ServerBridgeHelper extends Helper
{
    private ServerBridgeService $bridge;

    public function __construct($view, array $config = [])
    {
        parent::__construct($view, $config);

        $this->bridge = new ServerBridgeService();
    }

    public function call(mixed $methods = [], int|false $serverId = false, bool $debug = false): mixed
    {
        return $this->bridge->call($methods, $serverId, $debug);
    }

    public function online(int|false $serverId = false): mixed
    {
        return $this->bridge->online($serverId);
    }

    public function pingPublic(array $config): array|false
    {
        return $this->bridge->pingPublic($config);
    }

    public function sendCommand(string $cmd, int|false $serverId = false): mixed
    {
        return $this->bridge->sendCommand($cmd, $serverId);
    }

    public function commands(mixed $commands, int|false $serverId = false): mixed
    {
        return $this->bridge->commands($commands, $serverId);
    }

    public function scheduleCommands(mixed $commands, int $time, array $servers = []): bool
    {
        return $this->bridge->scheduleCommands($commands, $time, $servers);
    }

    public function check(mixed $info, array $value): bool
    {
        return $this->bridge->check($info, $value);
    }

    public function bannerInfos(mixed $serverId = false): array
    {
        return $this->bridge->bannerInfos($serverId);
    }

    public function lastErrorMessage(): ?string
    {
        return $this->bridge->lastErrorMessage;
    }

    public function linkErrorCode(): ?string
    {
        return $this->bridge->linkErrorCode;
    }
}
