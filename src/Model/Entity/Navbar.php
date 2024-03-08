<?php
namespace App\Model\Entity;

use Cake\Log\Log;
use Cake\ORM\Entity;

class Navbar extends Entity
{
    protected function _getUrlData($url)
    {
        if ($this->url == "#") {
            return ['type' => 'submenu'];
        } else {
            return json_decode($this->url, true);
        }
    }
}
