<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Configuration;
use App\Model\Entity\User;
use Cake\Datasource\EntityInterface;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class ConfigurationsTable extends Table
{
    private Configuration|array|null $dataConfig = null;

    public function initialize(array $config): void
    {
        $this->setTable('configurations');
        $this->setPrimaryKey('id');
        $this->setDisplayField('name');
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('website_url')
            ->requirePresence('website_url', 'create')
            ->notEmptyString('website_url');

        $validator
            ->scalar('name')
            ->maxLength('name', 50)
            ->requirePresence('name', 'create')
            ->notEmptyString('name');

        $validator
            ->scalar('email')
            ->maxLength('email', 50)
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->scalar('lang')
            ->maxLength('lang', 5)
            ->requirePresence('lang', 'create')
            ->notEmptyString('lang');

        $validator
            ->scalar('theme')
            ->maxLength('theme', 50)
            ->requirePresence('theme', 'create')
            ->notEmptyString('theme');

        $validator
            ->scalar('layout')
            ->requirePresence('layout', 'create')
            ->notEmptyString('layout');

        $validator
            ->scalar('money_name_singular')
            ->requirePresence('money_name_singular', 'create')
            ->notEmptyString('money_name_singular');

        $validator
            ->scalar('money_name_plural')
            ->requirePresence('money_name_plural', 'create')
            ->notEmptyString('money_name_plural');

        $validator
            ->integer('server_state')
            ->requirePresence('server_state', 'create')
            ->notEmptyString('server_state');

        $validator
            ->integer('server_cache')
            ->notEmptyString('server_cache');

        $validator
            ->scalar('server_secretkey')
            ->maxLength('server_secretkey', 50)
            ->requirePresence('server_secretkey', 'create')
            ->notEmptyString('server_secretkey');

        $validator
            ->numeric('server_timeout')
            ->requirePresence('server_timeout', 'create')
            ->notEmptyString('server_timeout');

        $validator
            ->scalar('condition')
            ->allowEmptyString('condition');

        $validator
            ->scalar('banner_server')
            ->allowEmptyString('banner_server');

        $validator
            ->integer('email_send_type')
            ->allowEmptyString('email_send_type');

        $validator
            ->scalar('smtpHost')
            ->maxLength('smtpHost', 30)
            ->allowEmptyString('smtpHost');

        $validator
            ->scalar('smtpUsername')
            ->maxLength('smtpUsername', 150)
            ->allowEmptyString('smtpUsername');

        $validator
            ->integer('smtpPort')
            ->allowEmptyString('smtpPort');

        $validator
            ->scalar('smtpPassword')
            ->maxLength('smtpPassword', 100)
            ->allowEmptyString('smtpPassword');

        $validator
            ->scalar('google_analytics')
            ->maxLength('google_analytics', 15)
            ->allowEmptyString('google_analytics');

        $validator
            ->scalar('end_layout_code')
            ->allowEmptyString('end_layout_code');

        $validator
            ->integer('check_uuid')
            ->allowEmptyString('check_uuid');

        $validator
            ->integer('captcha_type')
            ->allowEmptyString('captcha_type');

        $validator
            ->scalar('captcha_sitekey')
            ->maxLength('captcha_sitekey', 60)
            ->allowEmptyString('captcha_sitekey');

        $validator
            ->scalar('captcha_secret')
            ->maxLength('captcha_secret', 60)
            ->allowEmptyString('captcha_secret');

        $validator
            ->integer('confirm_mail_signup')
            ->notEmptyString('confirm_mail_signup');

        $validator
            ->integer('confirm_mail_signup_block')
            ->notEmptyString('confirm_mail_signup_block');

        $validator
            ->integer('member_page_type')
            ->notEmptyString('member_page_type');

        $validator
            ->scalar('passwords_hash')
            ->maxLength('passwords_hash', 10)
            ->allowEmptyString('passwords_hash');

        $validator
            ->integer('passwords_salt')
            ->allowEmptyString('passwords_salt');

        $validator
            ->integer('forced_updates')
            ->allowEmptyString('forced_updates');

        $validator
            ->scalar('session_type')
            ->maxLength('session_type', 10)
            ->allowEmptyString('session_type');

        $validator
            ->scalar('microsoft_client_id')
            ->maxLength('microsoft_client_id', 50)
            ->allowEmptyString('microsoft_client_id');

        $validator
            ->scalar('microsoft_client_secret')
            ->maxLength('microsoft_client_secret', 50)
            ->allowEmptyString('microsoft_client_secret');

        return $validator;
    }

    public function getAll(): Configuration|array
    {
        return $this->getData();
    }

    private function getData(): Configuration|array
    {
        if ($this->dataConfig === null) {
            $config = $this->find()->first();
            if ($config === null) {
                return [];
            }
            $this->dataConfig = $config;
        }

        return $this->dataConfig;
    }

    public function getMoneyName(bool $plural = true): string
    {
        return $plural
            ? (string)$this->getData()->get('money_name_plural')
            : (string)$this->getData()->get('money_name_singular');
    }

    public function getKey(string $key): mixed
    {
        $data = $this->getData();
        if (is_array($data)) {
            return $data[$key] ?? false;
        }

        return $data->get($key) ?? false;
    }

    public function setKey(string $key, mixed $value): EntityInterface|false
    {
        $config = $this->get(1);
        $config->set($key, $value);

        return $this->save($config);
    }

    public function getFirstAdministrator(): ?string
    {
        $userTable = TableRegistry::getTableLocator()->get('User');
        $user = $userTable
            ->find()
            ->where(['rank' => '4'])
            ->first();

        return $user instanceof User ? $user->get('pseudo') : null;
    }

    public function getInstalledDate(): mixed
    {
        $userTable = TableRegistry::getTableLocator()->get('User');
        $user = $userTable
            ->find()
            ->where(['rank' => '4'])
            ->first();

        return $user instanceof User ? $user->get('created') : null;
    }
}
