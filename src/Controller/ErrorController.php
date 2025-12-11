<?php
declare(strict_types=1);

namespace App\Controller;

class ErrorController extends BaseController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()
            ->setLayout('error')
            ->setTemplatePath('Error');
    }
}
