<?php
namespace App\Model\Entity;

use Cake\I18n\FrozenTime;
use Cake\ORM\Entity;

class Page extends Entity
{
    protected function _getCreated($created): string
    {
        $created = new \Cake\I18n\DateTime($created);
        return $created->toDateTimeString();
    }

    protected function _getUpdated($updated): string
    {
        $updated = new \Cake\I18n\DateTime($updated);
        return $updated->toDateTimeString();
    }
}
