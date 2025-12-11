<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Http\Response;

class APIController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();
        $this->loadComponent('API');
    }

    public function launcher(string $username, string $password, ?string $args = null): Response
    {
        $this->disableAutoRender();

        $argsArray = [];
        if ($args !== null && $args !== '') {
            $argsArray = array_values(
                array_filter(
                    array_map('trim', explode(',', $args)),
                    static function (string $v): bool {
                        return $v !== '';
                    }
                )
            );
        }

        $payload = $this->API->get($username, $password, $argsArray);

        return $this->response
            ->withType('application/json')
            ->withStringBody(json_encode($payload));
    }

    public function getSkin(string $name): Response
    {
        $this->disableAutoRender();

        $binary = $this->API->get_skin($name);

        return $this->response
            ->withType('image/png')
            ->withStringBody($binary);
    }

    public function getHeadSkin(string $name, int $size = 50): Response
    {
        $this->disableAutoRender();

        $binary = $this->API->get_head_skin($name, $size);

        return $this->response
            ->withType('image/png')
            ->withStringBody($binary);
    }
}
