<?php
namespace App\Model\Table;

use ArrayObject;
use Cake\Cache\Cache;
use Cake\Database\Schema\TableSchema;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\ORM\Entity;
use Cake\ORM\Query;
use Cake\ORM\Table;

class LikesTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('likes');

        $this->belongsTo("User", [
            'className' => "User"
        ])
            ->setForeignKey("user_id");

        $this->belongsTo("News", [
            "className" => "News"
        ])
            ->setForeignKey("news_id");
    }

    public function afterSave(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        Cache::delete('news', 'data');
    }

    public function afterDelete(EventInterface $event, EntityInterface $entity, ArrayObject $options)
    {
        Cache::delete('news', 'data');
    }
}
