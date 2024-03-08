<?php
namespace App\Model\Table;

use Cake\Database\Expression\QueryExpression;
use Cake\Datasource\EntityInterface;
use Cake\Event\Event;
use Cake\I18n\FrozenTime;
use Cake\Log\Log;
use Cake\ORM\Query;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Routing\Router;

class UserTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable("users");

        $this->hasMany("Comment", [
            "className" => "Comment"
        ])
            ->setForeignKey("user_id")
            ->setSort("Comment.created DESC")
            ->setDependent(true);

        $this->hasMany("Like", [
            "className" => "Like"
        ])
            ->setForeignKey("user_id")
            ->setDependent(true);
    }

    private $userData;
    private $isConnected = null;
    private $isAdmin = null;
    private $isBanned = null;

    public function validRegister($data, $UtilComponent)
    {
        if (preg_match('`^([a-zA-Z0-9_]{2,16})$`', $data['pseudo'])) {
            if ($data['password'] == $data['password_confirmation']) {
                $data['password'] = $data['password_confirmation'] = $UtilComponent->password($data['password'], $data['pseudo']);

                if (filter_var($data['email'], FILTER_VALIDATE_EMAIL)) {
                    $search_member_by_pseudo = $this->find('all', conditions: ['pseudo' => $data['pseudo']])->toArray();
                    if (isset($data['uuid']))
                        $search_member_by_uuid = $this->find('all', conditions: ['uuid' => $data['uuid']])->toArray();
                    $search_member_by_email = $this->find('all', conditions: ['email' => $data['email']])->toArray();
                    if (empty($search_member_by_pseudo)) {
                        if (!TableRegistry::getTableLocator()->get("Configuration")->getKey('check_uuid') || empty($search_member_by_uuid)) {
                            if (empty($search_member_by_email)) {
                                return true;
                            } else {
                                return 'USER__ERROR_EMAIL_ALREADY_REGISTERED';
                            }
                        } else {
                            return 'USER__ERROR_UUID_ALREADY_REGISTERED';
                        }
                    } else {
                        return 'USER__ERROR_PSEUDO_ALREADY_REGISTERED';
                    }
                } else {
                    return 'USER__ERROR_EMAIL_NOT_VALID';
                }
            } else {
                return 'USER__ERROR_PASSWORDS_NOT_SAME';
            }
        } else {
            return 'USER__ERROR_PSEUDO_INVALID_FORMAT';
        }
    }

    public function register($data, $UtilComponent)
    {
        $data_to_save = [];

        $data_to_save['pseudo'] = htmlentities($data['pseudo']);
        $data_to_save['email'] = htmlentities($data['email']);

        $data_to_save['ip'] = isset($_SERVER["HTTP_CF_CONNECTING_IP"]) ? htmlentities($_SERVER["HTTP_CF_CONNECTING_IP"]) : $_SERVER["REMOTE_ADDR"];
        $data_to_save['rank'] = 0;

        if (isset($data['uuid']))
            $data_to_save['uuid'] = htmlentities($data['uuid']);

        $data_to_save['password'] = $UtilComponent->password($data['password'], $data['pseudo']);
        $data_to_save['password_hash'] = $UtilComponent->getPasswordHashType();

        $newUser = $this->newEntity($data_to_save);
        $this->save($newUser);
        return $newUser->id;
    }

    public function login($user, $data, bool $confirmEmailIsNeeded, bool $checkUUID, $controller)
    {
        $UtilComponent = $controller->Util;
        $LoginRetryTable = TableRegistry::getTableLocator()->get("LoginRetry");
        $ip = $UtilComponent->getIP();
        $modifiedDate = \Cake\I18n\DateTime::now();
        $modifiedDate = $modifiedDate->modify("- 10 minutes");
        $findRetryWithIP = $LoginRetryTable->find('all',
        conditions: [
            'ip' => $ip,
            'modified >= ' => $modifiedDate->i18nFormat('Y-M-d H:m:s')
        ],
        order: 'created DESC')->first();
        $date = date('Y-m-d H:i:s');
        if (empty($findRetryWithIP)) {
            $loginRetry = $LoginRetryTable->newEntity([
                'ip' => $ip,
                'count' => 1
            ]);
            $LoginRetryTable->save($loginRetry);
        } else {
            $LoginRetryTable->updateAll(
                [new QueryExpression('count = count + 1'), new QueryExpression("modified = '$date'")],
                ['ip' => $ip]
            );
        }
        if (!empty($findRetryWithIP) && $findRetryWithIP['count'] >= 5)
            return 'LOGIN__BLOCKED';

        $username = $user['pseudo'];
        if ($user['password'] != $UtilComponent->password($data['password'], $username, $user['password'], $user['password_hash']))
            return 'USER__ERROR_INVALID_CREDENTIALS';
        $LoginRetryTable->deleteAll(['ip' => $ip]);
        $conditions = [];

        if ($this->getFromUser('password_hash', $username) != $UtilComponent->getPasswordHashType()) {
            $conditions['password'] = $UtilComponent->password($data['password'], $username);
            $conditions['password_hash'] = $UtilComponent->getPasswordHashType();
        }

        if ($confirmEmailIsNeeded && !empty($user['confirmed']) && date('Y-m-d H:i:s', strtotime($user['confirmed'])) != $user['confirmed']) {
            $controller->Session->write('email.confirm.user.id', $user['id']);
            return 'USER__MSG_NOT_CONFIRMED_EMAIL';
        }
        if ($checkUUID) {
            if (empty($user['uuid'])) {
                $pseudoToUUID = file_get_contents("https://api.mojang.com/users/profiles/minecraft/" . $user['pseudo']);
                $conditions['uuid'] = json_decode($pseudoToUUID, true)['id'];

            } else {
                $ch = curl_init();

                curl_setopt($ch, CURLOPT_URL, "https://sessionserver.mojang.com/session/minecraft/profile/" . $user['uuid']);
                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
                curl_setopt($ch, CURLOPT_TIMEOUT, 5);

                curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
                curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 2);

                $uuidToPseudo = curl_exec($ch);
                curl_close($ch);

                if (!empty($uuidToPseudo)) {
                    $array = json_decode($uuidToPseudo, true);
                    $conditions['pseudo'] = $array['name'];
                }
            }
        }
        $conditions['ip'] = $ip;

        $userInTable = $this->get($user['id']);
        $userInTable->set($conditions);
        $this->save($userInTable);

        return ['status' => true, 'session' => $user['id']];
    }

    public function getFromUser($key, $search)
    {
        $search_user = $this->find('all', conditions: $this->__makeCondition($search))->first();
        return (!empty($search_user)) ? $search_user[$key] : null;
    }

    public function __makeCondition($search): array
    {
        if ((string)(int)$search == $search) {
            return [
                'id' => intval($search)
            ];
        } else {
            return [
                'pseudo' => $search
            ];
        }
    }

    public function resetPass($data, $controller)
    {
        $UtilComponent = $controller->Util;
        if ($data['password'] == $data['password2']) {
            unset($data['password2']);
            $search = $this->find('all', conditions: ['email' => $data['email']]);
            if (!empty($search)) {

                $this->Lostpassword = TableRegistry::getTableLocator()->get("Lostpassword");
                $Lostpassword = $this->Lostpassword->find('all', conditions: ['email' => $data['email'], 'key' => $data['key']])->first();
                if (!empty($Lostpassword) && strtotime('+1 hour', strtotime($Lostpassword['created'])) >= time()) {

                    $data_to_save['password'] = $UtilComponent->password($data['password'], $search['0']['pseudo']);
                    $data_to_save['password_hash'] = $UtilComponent->getPasswordHashType();

                    $event = new Event('beforeResetPassword', $this, ['user_id' => $search[0]['id'], 'new_password' => $data_to_save['password']]);
                    $controller->getEventManager()->dispatch($event);
                    if ($event->isStopped()) {
                        return $event->getResult();
                    }

                    $this->Lostpassword->delete($Lostpassword['Lostpassword']['id']);

                    $user = $this->get($search['0']['id']);
                    $user->set($data_to_save);
                    $this->save($user);

                    return ['status' => true, 'session' => $search[0]['id']];

                } else {
                    return 'USER__PASSWORD_RESET_INVALID_KEY';
                }
            } else {
                return 'ERROR__INTERNAL_ERROR';
            }
        } else {
            return 'USER__ERROR_PASSWORDS_NOT_SAME';
        }
    }

    public function isConnected(): bool
    {
        if (!Router::getRequest()->getSession()->check('user'))
            return false;

        $user = $this->getDataBySession();
        return !empty($user);
    }

    public function isBanned()
    {
        $check = TableRegistry::getTableLocator()->get("Ban")->find('all', conditions: ['user_id' => $this->getKey("id")])->first();
        $this->isBanned = $check != null ? $check["reason"] : false;

        return $this->isBanned;

    }

    private function getDataBySession()
    {
        if (empty($this->userData))
            $this->userData = $this->get(Router::getRequest()->getSession()->read('user'));

        return $this->userData;
    }

    public function isAdmin(): bool
    {
        $user = $this->getDataBySession();
        if (empty($user)) return false;
        return ($user['rank'] == 3 || $user['rank'] == 4);
    }

    public function exist($search): bool
    { //username || id
        $search_user = $this->find('all', conditions: $this->__makeCondition($search))->first();
        return (!empty($search_user));
    }

    public function getKey($key): ?string
    {
        if (Router::getRequest()->getSession()->check('user')) {
            $search_user = $this->getDataBySession();
            return ($search_user && isset($search_user[$key])) ? $search_user[$key] : '';
        }

        return null;
    }

    public function setKey($key, $value)
    {
        if (Router::getRequest()->getSession()->check('user')) {
            $search_user = $this->getDataBySession();
            if ($search_user) {
                $user = $this->get($search_user['id']);
                $user->set([$key => $value]);
                $this->save($user);

                // on reset les données
                $this->userData = null;

                return $user->toArray();
            }
        }

        return null;
    }

    public function getUsernameByID($id)
    {
        $search_user = $this->find('all', conditions: ['id' => $id])->first();
        return (!empty($search_user)) ? $search_user['pseudo'] : '';
    }

    public function getAllFromCurrentUser(): ?EntityInterface
    {
        if (Router::getRequest()->getSession()->check('user')) {
            return $this->getDataBySession();
        }

        return null;
    }

    public function getAllFromUser($search = null): array | EntityInterface
    {
        $search_user = $this->find('all', conditions: $this->__makeCondition($search))->first();
        if (!empty($search_user)) {
            return $search_user;
        }
        return [];
    }

    public function setToUser($key, $value, $search)
    {
        $search_user = $this->find('all', conditions: $this->__makeCondition($search))->first();
        if (!empty($search_user)) {
            $this->id = $search_user['id'];
            return $this->saveField($key, $value);
        }

        return null;
    }
}
