<?php
declare(strict_types=1);

namespace App\Service;

use Cake\Core\Configure;

final class AdminNavbarService
{
    public function getNav(): array
    {
        return (array)Configure::read('AdminNav', []);
    }
}
