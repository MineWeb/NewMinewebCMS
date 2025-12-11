<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;

class ErrorController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();

        $this->viewBuilder()
            ->setLayout('error')
            ->setTemplatePath('Error');
    }
}
