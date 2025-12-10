<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Event\EventInterface;
use Cake\Http\Response;
use Exception;

class ErrorController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        try {
            $this->loadComponent('RequestHandler');
        } catch (Exception) {

        }
        $this->viewBuilder()->setLayout('error');
    }

    public function beforeFilter(EventInterface $event): ?Response
    {
        return null;
    }

    public function beforeRender(EventInterface $event): ?Response
    {
        $this->viewBuilder()->setTemplatePath('Error');
        return null;
    }

    public function afterFilter(EventInterface $event): ?Response
    {
        return null;
    }
}
