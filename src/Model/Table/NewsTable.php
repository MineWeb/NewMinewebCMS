<?php
namespace App\Model\Table;

use Cake\ORM\Query;
use Cake\ORM\Table;

class NewsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->hasMany("Comment")
            ->setSort("Comment.created DESC")
            ->setDependent(true);

        $this->hasMany("Likes")
            ->setDependent(true);
    }

    private Table $userModel;
    private array $usersByID = [];

    public function find(string $type = 'all', mixed ...$args): Query
    {
        return parent::find($type, $args)->contain(['Comment', 'Likes']);
    }
}
