<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Service\AuthService;
use Cake\Controller\Component;

final class AuthComponent extends Component
{
    private AuthService $service;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->service = new AuthService();
    }

    public function can(string $perm): bool
    {
        return $this->service->can($this->getController()->getRequest(), $perm);
    }

    public function require(string $perm): void
    {
        $this->service->require($this->getController()->getRequest(), $perm);
    }

    public function identity(): mixed
    {
        return $this->service->identity($this->getController()->getRequest());
    }

    public function user(): mixed
    {
        return $this->service->user($this->getController()->getRequest());
    }

    public function id(): ?int
    {
        return $this->service->id($this->getController()->getRequest());
    }

    public function username(): ?string
    {
        return $this->service->username($this->getController()->getRequest());
    }

    public function isConnected(): bool
    {
        return $this->service->isConnected($this->getController()->getRequest());
    }

    public function isAdmin(): bool
    {
        return $this->service->isAdmin($this->getController()->getRequest());
    }

    public function isBanned(): bool
    {
        return $this->service->isBanned($this->getController()->getRequest());
    }

    public function banReason(): mixed
    {
        return $this->service->banReason($this->getController()->getRequest());
    }
}
