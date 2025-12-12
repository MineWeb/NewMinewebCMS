<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Controller\Component\UtilComponent;
use Cake\Datasource\EntityInterface;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setPrimaryKey('id');
        $this->setDisplayField('username');

        $this->belongsTo('Ranks', [
            'foreignKey' => 'rank',
            'bindingKey' => 'rank_id',
        ]);

        $this->hasMany('News', ['foreignKey' => 'user_id']);
        $this->hasMany('Pages', ['foreignKey' => 'user_id']);
        $this->hasMany('Histories', ['foreignKey' => 'user_id']);
        $this->hasMany('Bans', ['foreignKey' => 'user_id']);
        $this->hasMany('Notifications', ['foreignKey' => 'user_id']);

        $this->hasMany('NotificationsFrom', [
            'className' => 'Notifications',
            'foreignKey' => 'from',
        ]);

        $this->hasMany('Comments', ['foreignKey' => 'user_id']);
        $this->hasMany('Likes', ['foreignKey' => 'user_id']);

        $this->hasOne('UsersTwofactorauth', [
            'foreignKey' => 'user_id',
        ]);

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created_at' => 'new',
                    'updated_at' => 'always',
                ],
            ],
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('username')
            ->maxLength('username', 255)
            ->requirePresence('username', 'create')
            ->notEmptyString('username');

        $validator
            ->scalar('uuid')
            ->allowEmptyString('uuid');

        $validator
            ->scalar('password')
            ->requirePresence('password', 'create')
            ->notEmptyString('password');

        $validator
            ->scalar('password_hash')
            ->allowEmptyString('password_hash');

        $validator
            ->email('email')
            ->requirePresence('email', 'create')
            ->notEmptyString('email');

        $validator
            ->integer('rank')
            ->requirePresence('rank', 'create')
            ->notEmptyString('rank');

        $validator
            ->numeric('money')
            ->allowEmptyString('money');

        $validator
            ->scalar('ip')
            ->maxLength('ip', 50)
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip');

        $validator
            ->integer('skin')
            ->allowEmptyString('skin');

        $validator
            ->integer('cape')
            ->allowEmptyString('cape');

        $validator
            ->scalar('confirmed')
            ->maxLength('confirmed', 25)
            ->allowEmptyString('confirmed');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['username']));
        $rules->add($rules->isUnique(['email']));
        $rules->add($rules->isUnique(['uuid']));
        $rules->add($rules->existsIn(['rank'], 'Ranks'));

        return $rules;
    }

    public function validRegister(array &$data, UtilComponent $UtilComponent): bool|string
    {
        if (!preg_match('`^([a-zA-Z0-9_]{2,16})$`', $data['username'] ?? '')) {
            return 'USER__ERROR_USERNAME_INVALID_FORMAT';
        }

        if (($data['password'] ?? '') !== ($data['password_confirmation'] ?? '')) {
            return 'USER__ERROR_PASSWORDS_NOT_SAME';
        }

        if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            return 'USER__ERROR_EMAIL_NOT_VALID';
        }

        $search_member_by_username = $this->find()->where(['username' => $data['username']])->first();

        $search_member_by_uuid = null;
        if (isset($data['uuid'])) {
            $search_member_by_uuid = $this->find()->where(['uuid' => $data['uuid']])->first();
        }

        $search_member_by_email = $this->find()->where(['email' => $data['email']])->first();

        if ($search_member_by_username) {
            return 'USER__ERROR_USERNAME_ALREADY_REGISTERED';
        }

        $configTable = TableRegistry::getTableLocator()->get('Configurations');
        if ($configTable->get('check_uuid') && $search_member_by_uuid) {
            return 'USER__ERROR_UUID_ALREADY_REGISTERED';
        }

        if ($search_member_by_email) {
            return 'USER__ERROR_EMAIL_ALREADY_REGISTERED';
        }

        return true;
    }

    public function getFromUser(string $key, int|string $search): mixed
    {
        $search_user = $this->find()->where($this->makeCondition($search))->first();

        if (!$search_user) {
            return null;
        }

        return $search_user->get($key);
    }

    public function makeCondition(int|string $search): array
    {
        if ((string)(int)$search === (string)$search) {
            return ['id' => (int)$search];
        }

        return ['username' => $search];
    }

    public function exist(int|string $search): bool
    {
        return (bool)$this->find()->where($this->makeCondition($search))->first();
    }

    public function getUsernameByID(int $id): string
    {
        $search_user = $this->find()->where(['id' => $id])->first();

        return $search_user ? (string)$search_user['username'] : '';
    }

    public function getAllFromUser(int|string|null $search = null): ?EntityInterface
    {
        $searchValue = $search ?? '';

        return $this->find()->where($this->makeCondition($searchValue))->first();
    }

    public function setToUser(string $key, mixed $value, int|string $search): EntityInterface|false|null
    {
        $search_user = $this->find()->where($this->makeCondition($search))->first();
        if (!$search_user) {
            return null;
        }

        $search_user->set($key, $value);

        return $this->save($search_user);
    }
}
