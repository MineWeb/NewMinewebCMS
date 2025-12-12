<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;
use Throwable;

final class ServerBannerService
{
    use LocatorAwareTrait;

    public function getBannerMessage(): ?string
    {
        $Configurations = $this->fetchTable('Configurations');
        $Servers = $this->fetchTable('Servers');

        $raw = $Configurations->get('banner_server');

        $configuration = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = @unserialize($raw);
            if (is_array($decoded)) {
                $configuration = $decoded;
            }
        }

        try {
            $serverInfos = $configuration ? $Servers->banner_infos($configuration) : $Servers->banner_infos();
        } catch (Throwable) {
            return null;
        }

        if (
            !isset($serverInfos['GET_MAX_PLAYERS'], $serverInfos['GET_PLAYER_COUNT'])
            || (int)$serverInfos['GET_MAX_PLAYERS'] === 0
        ) {
            return null;
        }

        return (string)__(
            'SERVER__STATUS_MESSAGE',
            [
                '{MOTD}' => $serverInfos['getMOTD'] ?? null,
                '{VERSION}' => $serverInfos['getVersion'] ?? null,
                '{ONLINE}' => $serverInfos['GET_PLAYER_COUNT'],
                '{ONLINE_LIMIT}' => $serverInfos['GET_MAX_PLAYERS'],
            ]
        );
    }
}
