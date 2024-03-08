<?php
namespace App\Controller\Component;

use Cake\Controller\Component;

class EySecurityComponent extends Component
{
    public function xssProtection($string)
    {
        return htmLawed($string, ['safe' => 1, 'deny_attribute' => '* -title -src -alt -style -href']);
    }
}
