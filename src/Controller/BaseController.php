<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Http\Response;

class BaseController extends Controller
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('Flash');
    }

    public function __get(string $name): mixed
    {
        if ($this->components()->has($name)) {
            return $this->components()->get($name);
        }

        return parent::__get($name);
    }

    public function sendJSON(mixed $data): Response
    {
        $this->disableAutoRender();
        $response = $this->response->withType('application/json');

        return $response->withStringBody(json_encode($data));
    }
}
