<?php
namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

class Notification extends Entity
{
    protected function _getCreated($created) {
        $created = new \Cake\I18n\DateTime($created);
        return $created->toDateTimeString();
    }
}
