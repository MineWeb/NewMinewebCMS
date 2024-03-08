<?php
namespace App\Controller;

class APIController extends AppController
{
    public function initialize(): void
    {
        parent::initialize();

        $this->loadComponent('API');
    }

    public function launcher($username, $password, $args = null)
    {
        $this->disableAutoRender();
        $this->response = $this->response->withType('application/json');
        $args = explode(',', $args);
        return $this->response->withStringBody(json_encode($this->API->get($username, $password, $args)));
    }

    public function getSkin($name)
    {
        header('Content-Type: image/png');
        $this->disableAutoRender();
        echo $this->API->get_skin($name);
    }

    public function getHeadSkin($name, $size = 50)
    {
        header('Content-Type: image/png');
        $this->disableAutoRender();
        echo $this->API->get_head_skin($name, $size);
    }

}
