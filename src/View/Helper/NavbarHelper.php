<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\NavbarService;
use Cake\View\Helper;
use Cake\View\View;

final class NavbarHelper extends Helper
{
    protected NavbarService $service;

    public function __construct(View $view, array $config = [])
    {
        parent::__construct($view, $config);
        $this->service = new NavbarService();
    }

    public function items(): array
    {
        return $this->service->getNavbar();
    }

    public function isActive(string $url): bool
    {
        return $this->getView()->getRequest()->getRequestTarget() === $url;
    }

    public function icon(?string $icon): string
    {
        if (!$icon) {
            return '';
        }

        return str_contains($icon, 'fa-') ? $icon : 'fa fa-' . $icon;
    }
}
