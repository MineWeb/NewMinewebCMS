<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Model\Table\HistoriesTable;
use Cake\Controller\Component;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\ORM\ResultSet;
use Throwable;

final class HistoryComponent extends Component
{
    use LocatorAwareTrait;

    private HistoriesTable $Histories;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->Histories = $this->fetchTable('Histories');
    }

    public function set(string $action, string $category, mixed $optional = null, ?int $userId = null): bool
    {
        try {
            $controller = $this->getController();
            $resolvedUserId = $userId ?? ($controller->Auth->id() ?: null);

            if (!$resolvedUserId) {
                return false;
            }

            $entity = $this->Histories->newEntity([
                'action' => $action,
                'category' => $category,
                'user_id' => $resolvedUserId,
                'other' => $optional,
            ]);

            return (bool)$this->Histories->save($entity);
        } catch (Throwable $e) {
            $this->logThrowable('HistoryComponent::set', $e);
            return false;
        }
    }

    public function get(
        string|false $category = false,
        int|false $limit = false,
        string|false $date = false,
        string|false $action = false,
    ): array {
        try {
            $conditions = $this->buildConditions($category, $date, $action);

            $query = $this->Histories
                ->find('withUser')
                ->where($conditions)
                ->orderBy(['Histories.id' => 'DESC']);

            if ($limit !== false) {
                $query->limit($limit);
            }

            return $this->translateActions($query->all());
        } catch (Throwable $e) {
            $this->logThrowable('HistoryComponent::get', $e);
            return [];
        }
    }

    public function getByAuthor(string $author): array
    {
        try {
            $query = $this->Histories
                ->find('withUser')
                ->matching('Users', function ($q) use ($author) {
                    return $q->where(['Users.username' => $author]);
                })
                ->orderBy(['Histories.id' => 'DESC']);

            return $this->translateActions($query->all());
        } catch (Throwable $e) {
            $this->logThrowable('HistoryComponent::getByAuthor', $e);
            return [];
        }
    }

    public function get_by_author(string $author): array
    {
        return $this->getByAuthor($author);
    }

    private function buildConditions(string|false $category, string|false $date, string|false $action): array
    {
        $conditions = [];

        if ($category !== false) {
            $conditions['Histories.category'] = $category;
        }
        if ($date !== false) {
            $conditions['Histories.created_at LIKE'] = $date . '%';
        }
        if ($action !== false) {
            $conditions['Histories.action'] = $action;
        }

        return $conditions;
    }

    private function translateActions(ResultSet $resultSet): array
    {
        $rows = $resultSet->toArray();

        foreach ($rows as $row) {
            $action = (string)$row->get('action');
            $row->set('action', __($action));
        }

        return $rows;
    }

    private function logThrowable(string $context, Throwable $e): void
    {
        Log::error($context . ' error: ' . $e->getMessage());
        Log::error($e->getTraceAsString());
    }
}
