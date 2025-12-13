<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Utility\LangService;
use Cake\View\Helper;

class LangHelper extends Helper
{
    public function history(string $action): string
    {
        return LangService::history($action);
    }

    public function date(string $date, string $format = 'dd/MM/yyyy HH:mm'): string
    {
        return LangService::date($date, $format);
    }
}
