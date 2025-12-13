<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\SocialButtonService;
use Cake\View\Helper;

final class SocialButtonHelper extends Helper
{
    private SocialButtonService $service;

    public function initialize(array $config): void
    {
        $this->service = new SocialButtonService();
    }

    public function all(): iterable
    {
        return $this->service->all();
    }
}
