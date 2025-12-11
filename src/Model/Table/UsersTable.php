<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Controller\Component\UtilComponent;
use App\Model\Entity\User;
use Cake\Controller\Controller;
use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\I18n\DateTime;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;
use Cake\Validation\Validator;

class UsersTable extends Table
{
    private ?User $userData = null;
    private ?bool $isConnected = null;
    private ?bool $isAdmin = null;
    private string|false|null $isBanned = null;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->setTable('users');
        $this->setPrimaryKey('id');
        $this->setDisplayField('pseudo');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);

        $this->belongsTo('Ranks', [
            'foreignKey' => 'rank',
            'bindingKey' => 'rank_id',
        ]);

        $this->hasMany('News', [
            'foreignKey' => 'user_id',
        ]);

        $this->hasMany('Pages', [
            'foreignKey' => 'user_id',
        ]);

        $this->hasMany('Histories', [
            'foreignKey' => 'user_id',
        ]);

        $this->hasMany('Bans', [
            'foreignKey' => 'user_id',
        ]);

        $this->hasMany('Notifications', [
            'foreignKey' => 'user_id',
        ]);

        $this->hasMany('NotificationsFrom', [
            'className' => 'Notifications',
            'foreignKey' => 'from',
        ]);

        $this->hasMany('Comments', [
            'foreignKey' => 'user_id',
        ]);

        $this->hasMany('Likes', [
            'foreignKey' => 'user_id',
        ]);

        $this->hasOne('UsersTwofactorauth', [
            'foreignKey' => 'user_id',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('pseudo')
            ->maxLength('pseudo', 255)
            ->requirePresence('pseudo', 'create')
            ->notEmptyString('pseudo');

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
            ->requirePresence('money', 'create')
            ->notEmptyString('money');

        $validator
            ->scalar('ip')
            ->maxLength('ip', 50)
            ->requirePresence('ip', 'create')
            ->notEmptyString('ip');

        $validator
            ->integer('skin')
            ->requirePresence('skin', 'create')
            ->notEmptyString('skin');

        $validator
            ->integer('cape')
            ->requirePresence('cape', 'create')
            ->notEmptyString('cape');

        $validator
            ->dateTime('created')
            ->notEmptyDateTime('created');

        $validator
            ->scalar('confirmed')
            ->maxLength('confirmed', 25)
            ->allowEmptyString('confirmed');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->isUnique(['pseudo']));
        $rules->add($rules->isUnique(['email']));
        $rules->add($rules->isUnique(['uuid']));
        $rules->add($rules->existsIn(['rank'], 'Ranks'));

        return $rules;
    }

    public function validRegister(array $data, UtilComponent $UtilComponent): bool|string
    {
        if (!preg_match('`^([a-zA-Z0-9_]{2,16})$`', $data['pseudo'] ?? '')) {
            return 'USER__ERROR_PSEUDO_INVALID_FORMAT';
        }

        if (($data['password'] ?? '') !== ($data['password_confirmation'] ?? '')) {
            return 'USER__ERROR_PASSWORDS_NOT_SAME';
        }

        $data['password'] = $data['password_confirmation'] = $UtilComponent->password(
            (string)$data['password'],
            (string)$data['pseudo'],
        );

        if (!filter_var($data['email'] ?? '', FILTER_VALIDATE_EMAIL)) {
            return 'USER__ERROR_EMAIL_NOT_VALID';
        }

        $search_member_by_pseudo = $this->find('all', conditions: ['pseudo' => $data['pseudo']])->toArray();

        $search_member_by_uuid = [];
        if (isset($data['uuid'])) {
            $search_member_by_uuid = $this->find('all', conditions: ['uuid' => $data['uuid']])->toArray();
        }

        $search_member_by_email = $this->find('all', conditions: ['email' => $data['email']])->toArray();

        if (!empty($search_member_by_pseudo)) {
            return 'USER__ERROR_PSEUDO_ALREADY_REGISTERED';
        }

        $configTable = TableRegistry::getTableLocator()->get('Configurations');
        if ($configTable->getKey('check_uuid') && !empty($search_member_by_uuid)) {
            return 'USER__ERROR_UUID_ALREADY_REGISTERED';
        }

        if (!empty($search_member_by_email)) {
            return 'USER__ERROR_EMAIL_ALREADY_REGISTERED';
        }

        return true;
    }

    public function register(array $data, UtilComponent $UtilComponent): int
    {
        $data_to_save = [];

        $data_to_save['pseudo'] = htmlentities((string)$data['pseudo']);
        $data_to_save['email'] = htmlentities((string)$data['email']);

        $data_to_save['ip'] = isset($_SERVER['HTTP_CF_CONNECTING_IP'])
            ? htmlentities((string)$_SERVER['HTTP_CF_CONNECTING_IP'])
            : (string)($_SERVER['REMOTE_ADDR'] ?? '');
        $data_to_save['rank'] = 0;

        if (isset($data['uuid'])) {
            $data_to_save['uuid'] = htmlentities((string)$data['uuid']);
        }

        $data_to_save['password'] = $UtilComponent->password(
            (string)$data['password'],
            (string)$data['pseudo'],
        );
        $data_to_save['password_hash'] = $UtilComponent->getPasswordHashType();

        $newUser = $this->newEntity($data_to_save);
        $this->save($newUser);

        return (int)$newUser->id;
    }

    public function login(
        array $user,
        array $data,
        bool $confirmEmailIsNeeded,
        bool $checkUUID,
        Controller $controller,
    ): array|string {
        $UtilComponent = $controller->Util;
        $LoginRetryTable = TableRegistry::getTableLocator()->get('LoginRetries');

        $ip = $UtilComponent->getIP();

        $modifiedDate = DateTime::now();
        $modifiedDate = $modifiedDate->modify('- 10 minutes');

        $findRetryWithIP = $LoginRetryTable->find(
            'all',
            conditions: [
                'ip' => $ip,
                'modified >= ' => $modifiedDate->i18nFormat('Y-M-d H:m:s'),
            ],
            order: 'created DESC',
        )->first();

        $date = date('Y-m-d H:i:s');

        if (empty($findRetryWithIP)) {
            $loginRetry = $LoginRetryTable->newEntity([
                'ip' => $ip,
                'count' => 1,
            ]);
            $LoginRetryTable->save($loginRetry);
        } else {
            $LoginRetryTable->updateAll(
                [
                    new QueryExpression('count = count + 1'),
                    new QueryExpression("modified = '$date'"),
                ],
                ['ip' => $ip],
            );
        }

        if (!empty($findRetryWithIP) && (int)$findRetryWithIP['count'] >= 5) {
            return 'LOGIN__BLOCKED';
        }

        $username = (string)$user['pseudo'];

        if (
            (string)$user['password']
            !== $UtilComponent->password(
                (string)($data['password'] ?? ''),
                $username,
                (string)$user['password'],
                (string)$user['password_hash'],
            )
        ) {
            return 'USER__ERROR_INVALID_CREDENTIALS';
        }

        $LoginRetryTable->deleteAll(['ip' => $ip]);

        $conditions = [];

        if ($this->getFromUser('password_hash', $username) !== $UtilComponent->getPasswordHashType()) {
            $conditions['password'] = $UtilComponent->password(
                (string)$data['password'],
                $username,
            );
            $conditions['password_hash'] = $UtilComponent->getPasswordHashType();
        }

        if ($confirmEmailIsNeeded && !empty($user['confirmed'])) {
            $confirmed = (string)$user['confirmed'];
            if (date('Y-m-d H:i:s', strtotime($confirmed)) !== $confirmed) {
                $controller->Session->write('email.confirm.user.id', $user['id']);

                return 'USER__MSG_NOT_CONFIRMED_EMAIL';
            }
        }

        if ($checkUUID) {
            if (empty($user['uuid'])) {
                $pseudoToUUID = @file_get_contents(
                    'https://api.mojang.com/users/profiles/minecraft/' . $user['pseudo'],
                );
                if (!empty($pseudoToUUID)) {
                    $parsed = json_decode($pseudoToUUID, true);
                    if (is_array($parsed) && isset($parsed['id'])) {
                        $conditions['uuid'] = $parsed['id'];
                    }
                }
            } else {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, 'https://sessionserver.mojang.com/session/minecraft/profile/' . $user['uuid']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

                $uuidToPseudo = curl_exec($ch);
                curl_close($ch);

                if (!empty($uuidToPseudo)) {
                    $array = json_decode($uuidToPseudo, true);
                    if (is_array($array) && isset($array['name'])) {
                        $conditions['pseudo'] = $array['name'];
                    }
                }
            }
        }

        $conditions['ip'] = $ip;

        $userInTable = $this->get((int)$user['id']);
        $userInTable->set($conditions);
        $this->save($userInTable);

        return ['status' => true, 'session' => $user['id']];
    }

    public function getFromUser(string $key, int|string $search): mixed
    {
        $search_user = $this->find('all', conditions: $this->makeCondition($search))->first();

        if (empty($search_user)) {
            return null;
        }

        return $search_user[$key] ?? null;
    }

    public function makeCondition(int|string $search): array
    {
        if ((string)(int)$search === (string)$search) {
            return [
                'id' => (int)$search,
            ];
        }

        return [
            'pseudo' => $search,
        ];
    }

    public function resetPass(array $data, Controller $controller): array|string
    {
        $UtilComponent = $controller->Util;

        if (($data['password'] ?? '') !== ($data['password2'] ?? '')) {
            return 'USER__ERROR_PASSWORDS_NOT_SAME';
        }

        unset($data['password2']);

        $searchQuery = $this->find('all', conditions: ['email' => $data['email'] ?? '']);
        $user = $searchQuery->first();

        if (empty($user)) {
            return 'ERROR__INTERNAL_ERROR';
        }

        $this->Lostpassword = TableRegistry::getTableLocator()->get('Lostpasswords');

        $Lostpassword = $this->Lostpassword
            ->find('all', conditions: ['email' => $data['email'], 'key' => $data['key'] ?? ''])
            ->first();

        if (empty($Lostpassword)) {
            return 'USER__PASSWORD_RESET_INVALID_KEY';
        }

        $created = $Lostpassword['created'] ?? null;
        if (empty($created) || strtotime('+1 hour', strtotime((string)$created)) < time()) {
            return 'USER__PASSWORD_RESET_INVALID_KEY';
        }

        $data_to_save = [];
        $data_to_save['password'] = $UtilComponent->password(
            (string)$data['password'],
            (string)$user['pseudo'],
        );
        $data_to_save['password_hash'] = $UtilComponent->getPasswordHashType();

        $event = new Event('beforeResetPassword', $this, [
            'user_id' => $user['id'],
            'new_password' => $data_to_save['password'],
        ]);
        $controller->getEventManager()->dispatch($event);
        if ($event->isStopped()) {
            return $event->getResult();
        }

        $this->Lostpassword->delete($Lostpassword);

        $userEntity = $this->get((int)$user['id']);
        $userEntity->set($data_to_save);
        $this->save($userEntity);

        return ['status' => true, 'session' => $user['id']];
    }

    public function isConnected(): bool
    {
        if (!Router::getRequest()->getSession()->check('user')) {
            return false;
        }

        $user = $this->getDataBySession();

        return !empty($user);
    }

    public function isBanned(): string|false
    {
        $BanTable = TableRegistry::getTableLocator()->get('Bans');

        $check = $BanTable
            ->find('all', conditions: ['user_id' => $this->getKey('id')])
            ->first();

        $this->isBanned = $check !== null ? (string)$check['reason'] : false;

        return $this->isBanned;
    }

    private function getDataBySession(): ?User
    {
        if ($this->userData === null) {
            $userId = Router::getRequest()->getSession()->read('user');
            if ($userId === null) {
                return null;
            }
            $this->userData = $this->get((int)$userId);
        }

        return $this->userData;
    }

    public function isAdmin(): bool
    {
        $user = $this->getDataBySession();
        if (empty($user)) {
            return false;
        }

        $rank = (int)$user['rank'];

        return $rank === 3 || $rank === 4;
    }

    public function exist(int|string $search): bool
    {
        $search_user = $this->find('all', conditions: $this->makeCondition($search))->first();

        return !empty($search_user);
    }

    public function getKey(string $key): ?string
    {
        if (!Router::getRequest()->getSession()->check('user')) {
            return null;
        }

        $search_user = $this->getDataBySession();

        if (!$search_user) {
            return null;
        }

        return isset($search_user[$key]) ? (string)$search_user[$key] : '';
    }

    public function setKey(string $key, mixed $value): ?array
    {
        if (!Router::getRequest()->getSession()->check('user')) {
            return null;
        }

        $search_user = $this->getDataBySession();
        if (!$search_user) {
            return null;
        }

        $user = $this->get((int)$search_user['id']);
        $user->set([$key => $value]);
        $this->save($user);

        $this->userData = null;

        return $user->toArray();
    }

    public function getUsernameByID(int $id): string
    {
        $search_user = $this->find('all', conditions: ['id' => $id])->first();

        return !empty($search_user) ? (string)$search_user['pseudo'] : '';
    }

    public function getAllFromCurrentUser(): ?EntityInterface
    {
        if (!Router::getRequest()->getSession()->check('user')) {
            return null;
        }

        return $this->getDataBySession();
    }

    public function getAllFromUser(int|string|null $search = null): array|EntityInterface
    {
        $search_user = $this->find('all', conditions: $this->makeCondition($search ?? ''))->first();
        if (!empty($search_user)) {
            return $search_user;
        }

        return [];
    }

    public function setToUser(string $key, mixed $value, int|string $search): EntityInterface|false|null
    {
        $search_user = $this->find('all', conditions: $this->makeCondition($search))->first();
        if (empty($search_user)) {
            return null;
        }

        $search_user->set($key, $value);

        return $this->save($search_user);
    }
}
