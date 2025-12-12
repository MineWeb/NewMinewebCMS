<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use Cake\Database\Expression\QueryExpression;
use Cake\I18n\FrozenTime;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Utility\Security;
use Throwable;

final class UserAuthService
{
    use LocatorAwareTrait;

    private function configurationKey(string $key): mixed
    {
        try {
            return $this->fetchTable('Configurations')->get($key);
        } catch (Throwable) {
            return null;
        }
    }

    public function getPasswordHashType(): string
    {
        $hash = $this->configurationKey('passwords_hash');

        return is_string($hash) && $hash !== '' ? $hash : 'bcrypt';
    }

    public function hashPassword(string $password): string
    {
        $type = $this->getPasswordHashType();

        if ($type === 'bcrypt' || $type === 'blowfish') {
            $hashed = password_hash($password, PASSWORD_BCRYPT);

            return is_string($hashed) ? $hashed : '';
        }

        $salt = $this->configurationKey('passwords_salt');
        $saltValue = is_string($salt) && $salt !== '' ? $salt : false;

        return (string)Security::hash($password, $type, $saltValue);
    }

    public function verifyPassword(string $password, string $storedHash, ?string $storedType): bool
    {
        $type = is_string($storedType) && $storedType !== '' ? $storedType : $this->getPasswordHashType();

        if ($storedHash === '') {
            return false;
        }

        if ($type === 'bcrypt' || $type === 'blowfish') {
            return password_verify($password, $storedHash);
        }

        $salt = $this->configurationKey('passwords_salt');
        $saltValue = is_string($salt) && $salt !== '' ? $salt : false;

        $computed = (string)Security::hash($password, $type, $saltValue);

        return hash_equals($storedHash, $computed);
    }

    public function needsRehash(string $storedHash, ?string $storedType): bool
    {
        $type = is_string($storedType) && $storedType !== '' ? $storedType : $this->getPasswordHashType();

        if ($type === 'bcrypt' || $type === 'blowfish') {
            if ($storedHash === '') {
                return true;
            }

            return password_needs_rehash($storedHash, PASSWORD_BCRYPT);
        }

        return false;
    }

    public function createUser(array $data, string $ip): int
    {
        $Users = $this->fetchTable('Users');

        $pseudo = (string)($data['pseudo'] ?? '');
        $email = (string)($data['email'] ?? '');

        $dataToSave = [
            'pseudo' => htmlentities($pseudo),
            'email' => htmlentities($email),
            'ip' => $ip,
            'rank' => (int)($data['rank'] ?? 0),
            'money' => 0,
            'skin' => 0,
            'cape' => 0,
            'confirmed' => '',
            'microsoft_user_id' => $data['microsoft_user_id'] ?? null,
            'registered_by_microsoft' => (bool)($data['registered_by_microsoft'] ?? false),
        ];

        if (!empty($data['uuid'])) {
            $dataToSave['uuid'] = htmlentities((string)$data['uuid']);
        }

        $plainPassword = (string)($data['password'] ?? '');
        $dataToSave['password'] = $this->hashPassword($plainPassword);
        $dataToSave['password_hash'] = $this->getPasswordHashType();

        $newUser = $Users->newEntity($dataToSave);
        $Users->save($newUser);

        return (int)($newUser->id ?? 0);
    }

    public function attemptLogin(
        User $user,
        string $inputPassword,
        string $ip,
        bool $confirmEmailIsNeeded,
        bool $checkUUID,
    ): array|string {
        $LoginRetries = $this->fetchTable('LoginRetries');
        $Users = $this->fetchTable('Users');

        $modifiedDate = FrozenTime::now()->subMinutes(10);

        $findRetryWithIP = $LoginRetries->find()
            ->where([
                'ip' => $ip,
                'modified >=' => $modifiedDate->toDateTimeString(),
            ])
            ->orderByDesc('created')
            ->first();

        $date = FrozenTime::now()->toDateTimeString();

        if (!$findRetryWithIP) {
            $loginRetry = $LoginRetries->newEntity(['ip' => $ip, 'count' => 1]);
            $LoginRetries->save($loginRetry);
        } else {
            $expr = new QueryExpression('count + 1');
            $LoginRetries->updateAll(
                ['count' => $expr, 'modified' => $date],
                ['ip' => $ip]
            );
        }

        if ($findRetryWithIP && (int)($findRetryWithIP->get('count') ?? 0) >= 5) {
            return 'LOGIN__BLOCKED';
        }

        $storedHash = (string)($user->get('password') ?? '');
        $storedType = (string)($user->get('password_hash') ?? '');

        if (!$this->verifyPassword($inputPassword, $storedHash, $storedType)) {
            return 'USER__ERROR_INVALID_CREDENTIALS';
        }

        $LoginRetries->deleteAll(['ip' => $ip]);

        if ($confirmEmailIsNeeded && !empty($user->get('confirmed'))) {
            $confirmed = (string)$user->get('confirmed');
            if (date('Y-m-d H:i:s', strtotime($confirmed)) !== $confirmed) {
                return [
                    'status' => false,
                    'code' => 'USER__MSG_NOT_CONFIRMED_EMAIL',
                    'email_confirm_user_id' => (int)($user->get('id') ?? 0),
                ];
            }
        }

        $conditions = [];

        $currentType = $this->getPasswordHashType();
        if ($storedType === '' || $storedType !== $currentType || $this->needsRehash($storedHash, $storedType)) {
            $conditions['password'] = $this->hashPassword($inputPassword);
            $conditions['password_hash'] = $currentType;
        }

        if ($checkUUID) {
            $username = (string)($user->get('pseudo') ?? '');
            $currentUuid = (string)($user->get('uuid') ?? '');

            if ($currentUuid === '') {
                $pseudoToUUID = @file_get_contents('https://api.mojang.com/users/profiles/minecraft/' . rawurlencode($username));
                if (!empty($pseudoToUUID)) {
                    $parsed = json_decode($pseudoToUUID, true);
                    if (is_array($parsed) && !empty($parsed['id'])) {
                        $conditions['uuid'] = (string)$parsed['id'];
                    }
                }
            } else {
                $ch = curl_init();
                if ($ch !== false) {
                    curl_setopt($ch, CURLOPT_URL, 'https://sessionserver.mojang.com/session/minecraft/profile/' . rawurlencode($currentUuid));
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

                    $uuidToPseudo = curl_exec($ch);
                    curl_close($ch);

                    if (!empty($uuidToPseudo)) {
                        $array = json_decode((string)$uuidToPseudo, true);
                        if (is_array($array) && !empty($array['name'])) {
                            $conditions['pseudo'] = (string)$array['name'];
                        }
                    }
                }
            }
        }

        $conditions['ip'] = $ip;

        $userId = (int)($user->get('id') ?? 0);
        if ($userId > 0 && $conditions !== []) {
            $userEntity = $Users->get($userId);
            $userEntity->set($conditions);
            $Users->save($userEntity);
        }

        return ['status' => true, 'session' => $userId];
    }

    public function resetPassword(array $data): array|string
    {
        $Users = $this->fetchTable('Users');
        $Lostpasswords = $this->fetchTable('Lostpasswords');

        if (($data['password'] ?? '') !== ($data['password2'] ?? '')) {
            return 'USER__ERROR_PASSWORDS_NOT_SAME';
        }

        $email = (string)($data['email'] ?? '');
        $key = (string)($data['key'] ?? '');

        $user = $Users->find()->where(['email' => $email])->first();
        if (!$user) {
            return 'ERROR__INTERNAL_ERROR';
        }

        $lost = $Lostpasswords->find()->where(['email' => $email, 'key' => $key])->first();
        if (!$lost) {
            return 'USER__PASSWORD_RESET_INVALID_KEY';
        }

        $created = $lost['created'] ?? null;
        if (empty($created) || strtotime('+1 hour', strtotime((string)$created)) < time()) {
            return 'USER__PASSWORD_RESET_INVALID_KEY';
        }

        $Lostpasswords->delete($lost);

        $newPassword = (string)($data['password'] ?? '');

        $userEntity = $Users->get((int)$user['id']);
        $userEntity->set([
            'password' => $this->hashPassword($newPassword),
            'password_hash' => $this->getPasswordHashType(),
        ]);
        $Users->save($userEntity);

        return ['status' => true, 'session' => (int)$user['id']];
    }
}
