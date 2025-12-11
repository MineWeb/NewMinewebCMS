<?php
declare(strict_types=1);

namespace App\Model\Table;

use App\Model\Entity\Notification;
use App\Model\Entity\User;
use Cake\I18n\DateTime;
use Cake\ORM\Entity;
use Cake\ORM\RulesChecker;
use Cake\ORM\Table;
use Cake\ORM\TableRegistry;
use Cake\Validation\Validator;

class NotificationsTable extends Table
{
    public function initialize(array $config): void
    {
        $this->setTable('notifications');
        $this->setPrimaryKey('id');

        $this->addBehavior('Timestamp', [
            'events' => [
                'Model.beforeSave' => [
                    'created' => 'new',
                ],
            ],
        ]);

        $this->belongsTo('Users', [
            'foreignKey' => 'user_id',
        ]);

        $this->belongsTo('FromUsers', [
            'className' => 'Users',
            'foreignKey' => 'from',
        ]);
    }

    public function validationDefault(Validator $validator): Validator
    {
        $validator
            ->scalar('group')
            ->maxLength('group', 10)
            ->requirePresence('group', 'create')
            ->notEmptyString('group');

        $validator
            ->integer('user_id')
            ->allowEmptyString('user_id');

        $validator
            ->integer('from')
            ->allowEmptyString('from');

        $validator
            ->scalar('content')
            ->requirePresence('content', 'create')
            ->notEmptyString('content');

        $validator
            ->scalar('type')
            ->maxLength('type', 5)
            ->requirePresence('type', 'create')
            ->notEmptyString('type');

        $validator
            ->integer('seen')
            ->notEmptyString('seen');

        $validator
            ->dateTime('created')
            ->notEmptyDateTime('created');

        return $validator;
    }

    public function buildRules(RulesChecker $rules): RulesChecker
    {
        $rules->add($rules->existsIn(['user_id'], 'Users'));
        $rules->add($rules->existsIn(['from'], 'FromUsers'));

        return $rules;
    }

    public function getFromUser(int $user_id, string $type): array
    {
        $query = $this->find()
            ->where(['user_id' => $user_id, 'type' => $type])
            ->order(['id' => 'DESC']);

        $data = [];

        $UserModel = TableRegistry::getTableLocator()->get('User');
        DateTime::$wordFormat = 'd/m/y';

        foreach ($query as $notification) {
            if (!$notification instanceof Notification) {
                continue;
            }

            $from = null;

            if ($notification->get('from') !== null) {
                $from = $UserModel->getFromUser('pseudo', $notification->get('from'));
            }

            $created = $notification->get('created');
            $data[] = [
                'id' => (int)$notification->get('id'),
                'from' => $from,
                'content' => (string)$notification->get('content'),
                'time' => $created instanceof DateTime
                    ? $created->timeAgoInWords()
                    : DateTime::parse($created)->timeAgoInWords(),
                'seen' => (bool)$notification->get('seen'),
            ];
        }

        return $data;
    }

    public function setToAdmin(string $content, ?int $from = null): void
    {
        $group = $this->generateGroup();
        $this->setToRank($content, 4, $from, 'admin', $group);
        $this->setToRank($content, 3, $from, 'admin', $group);
    }

    private function generateGroup(): string
    {
        return substr(md5(microtime()), rand(0, 26), 10);
    }

    public function setToRank(
        string $content,
        int $rank_id,
        ?int $from = null,
        string $type = 'user',
        ?string $group = null,
    ): void {
        if ($group === null) {
            $group = $this->generateGroup();
        }

        $UserModel = TableRegistry::getTableLocator()->get('User');
        $usersToNotify = $UserModel->find()->where(['rank' => $rank_id])->all();

        foreach ($usersToNotify as $user) {
            if ($user instanceof User) {
                $this->setToUser($content, (int)$user->get('id'), $from, $type, $group);
            }
        }
    }

    public function setToUser(
        string $content,
        int $user_id,
        ?int $from = null,
        string $type = 'user',
        ?string $group = null,
    ): Entity|false {
        if ($content === '' || strlen($content) > 255 || $user_id <= 0) {
            return false;
        }

        if ($group === null) {
            $group = $this->generateGroup();
        }

        $notification = $this->newEntity([
            'group' => $group,
            'content' => $content,
            'user_id' => $user_id,
            'from' => $from,
            'type' => $type,
        ]);

        return $this->save($notification);
    }

    public function setToAll(string $content, ?int $from = null): void
    {
        if ($from === null) {
            $from = 0;
        }

        $group = $this->generateGroup();
        $content = addslashes($content);

        $this->getConnection()->execute(
            "INSERT INTO notifications (`group`, `user_id`, `from`, `content`, `type`, `created`)
             SELECT '$group', id, $from, '$content', 'user', '" . date('Y-m-d H:i:s') . "' FROM users",
        );
    }

    public function clearFromUser(int $id, int $user_id): int
    {
        return $this->deleteAll(['user_id' => $user_id, 'Notification.id' => $id]);
    }

    public function clearAllFromUser(int $user_id): int
    {
        return $this->deleteAll(['user_id' => $user_id]);
    }

    public function markAsSeenFromUser(int $id, int $user_id): int
    {
        return $this->updateAll(['seen' => 1], ['user_id' => $user_id, 'Notification.id' => $id]);
    }

    public function markAllAsSeenFromUser(int $user_id): int
    {
        return $this->updateAll(['seen' => 1], ['user_id' => $user_id]);
    }

    public function clearFromAllUsers(int $id): int
    {
        return $this->deleteAll(['id' => $id]);
    }

    public function markAsSeenFromAllUsers(int $id): int
    {
        return $this->updateAll(['seen' => 1], ['Notification.id' => $id]);
    }

    public function clearAllFromGroup(string $group): int
    {
        return $this->deleteAll(['group' => $group]);
    }

    public function clearAllFromAllUsers(): int
    {
        return $this->deleteAll(['1' => '1']);
    }

    public function markAllAsSeenFromAllUsers(): int
    {
        return $this->updateAll(['seen' => 1], ['1' => '1']);
    }
}
