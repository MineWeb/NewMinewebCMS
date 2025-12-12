<?php
declare(strict_types=1);

namespace App\View\Helper;

use Cake\View\Helper;

final class AdminUiHelper extends Helper
{
    public function darkModeEnabled(): bool
    {
        $cookie = $this->getView()->getRequest()->getCookie('use_admin_dark_mode');

        return $cookie === '1';
    }

    public function bodyClass(): string
    {
        return $this->darkModeEnabled() ? 'dark-mode' : '';
    }
}
