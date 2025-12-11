<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SlidersTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('sliders');
        $this->setPrimaryKey('id');
        $this->setDisplayField('title');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('title')
            ->maxLength('title', 50)
            ->requirePresence('title', 'create')
            ->notEmptyString('title');

        $validator
            ->scalar('subtitle')
            ->requirePresence('subtitle', 'create')
            ->notEmptyString('subtitle');

        $validator
            ->scalar('url_img')
            ->requirePresence('url_img', 'create')
            ->notEmptyString('url_img');

        return $validator;
    }
}
