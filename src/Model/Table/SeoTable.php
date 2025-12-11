<?php
declare(strict_types=1);

namespace App\Model\Table;

use Cake\ORM\Table;
use Cake\Validation\Validator;

class SeoTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('seo');
        $this->setPrimaryKey('id');
        $this->setDisplayField('page');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('title')
            ->maxLength('title', 255)
            ->allowEmptyString('title');

        $validator
            ->scalar('description')
            ->allowEmptyString('description');

        $validator
            ->scalar('favicon_url')
            ->maxLength('favicon_url', 255)
            ->allowEmptyString('favicon_url');

        $validator
            ->scalar('img_url')
            ->maxLength('img_url', 255)
            ->allowEmptyString('img_url');

        $validator
            ->scalar('theme_color')
            ->maxLength('theme_color', 255)
            ->allowEmptyString('theme_color');

        $validator
            ->scalar('twitter_site')
            ->maxLength('twitter_site', 255)
            ->allowEmptyString('twitter_site');

        $validator
            ->scalar('page')
            ->maxLength('page', 255)
            ->allowEmptyString('page');

        return $validator;
    }
}
