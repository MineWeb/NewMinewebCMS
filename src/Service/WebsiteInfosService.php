<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

final class WebsiteInfosService
{
    use LocatorAwareTrait;

    public function usersCount(): int
    {
        return $this->fetchTable('Users')
            ->find()
            ->count();
    }

    public function usersCountToday(): int
    {
        return $this->fetchTable('Users')
            ->find()
            ->where(['created LIKE' => date('Y-m-d') . '%'])
            ->count();
    }

    public function lastUser(): ?array
    {
        $user = $this->fetchTable('Users')
            ->find()
            ->orderByDesc('created_at')
            ->first();

        return $user ? $user->toArray() : null;
    }

    public function visitsToday(): int
    {
        $result = $this->fetchTable('Visits')
            ->getVisitsByDay(date('Y-m-d'));

        return (int)($result['count'] ?? 0);
    }

    public function visitsTotal(): int
    {
        return (int)$this->fetchTable('Visits')->getVisitsCount();
    }
}
