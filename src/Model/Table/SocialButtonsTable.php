<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SocialButtonsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('social_buttons');
        $this->setPrimaryKey('id');
        $this->setDisplayField('title');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->integer('order')
            ->requirePresence('order', 'create')
            ->notEmptyString('order');

        $validator
            ->scalar('title')
            ->maxLength('title', 20)
            ->allowEmptyString('title');

        $validator
            ->scalar('extra')
            ->maxLength('extra', 120)
            ->allowEmptyString('extra');

        $validator
            ->scalar('color')
            ->maxLength('color', 30)
            ->allowEmptyString('color');

        $validator
            ->scalar('url')
            ->maxLength('url', 120)
            ->allowEmptyString('url');

        return $validator;
    }
}
