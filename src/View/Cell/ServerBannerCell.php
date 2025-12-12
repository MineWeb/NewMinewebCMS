<?php
declare(strict_types=1);

namespace App\View\Cell;

use App\Service\ServerBannerService;
use Cake\View\Cell;

final class ServerBannerCell extends Cell
{
    public function display(): void
    {
        $service = new ServerBannerService();
        $banner = $service->getBannerMessage();

        $this->set(compact('banner'));
    }
}
