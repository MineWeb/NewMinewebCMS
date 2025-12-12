<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\WebsiteInfosService;
use Cake\View\Helper;
use Cake\View\View;

final class WebsiteInfoHelper extends Helper
{
    protected WebsiteInfosService $service;

    public function __construct(View $view, array $config = [])
    {
        parent::__construct($view, $config);
        $this->service = new WebsiteInfosService();
    }

    public function usersCount(): int
    {
        return $this->service->usersCount();
    }

    public function usersToday(): int
    {
        return $this->service->usersCountToday();
    }

    public function lastUser(): ?array
    {
        return $this->service->lastUser();
    }

    public function visitsToday(): int
    {
        return $this->service->visitsToday();
    }

    public function visitsTotal(): int
    {
        return $this->service->visitsTotal();
    }
}
