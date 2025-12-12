<?php
declare(strict_types=1);

namespace App\Service;

use Cake\ORM\Locator\LocatorAwareTrait;

final class SocialButtonService
{
    use LocatorAwareTrait;

    public function all(): iterable
    {
        return $this->fetchTable('SocialButtons')
            ->find()
            ->orderByAsc('order')
            ->all();
    }
}
