<?php
declare(strict_types=1);

namespace App\View\Helper;

use App\Service\AuthService;
use Cake\View\Helper;
use Psr\Http\Message\ServerRequestInterface;

final class AuthHelper extends Helper
{
    private AuthService $auth;
    private ServerRequestInterface $request;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $this->auth = new AuthService();
        $this->request = $this->getView()->getRequest();
    }

    public function isConnected(): bool
    {
        return $this->auth->isConnected($this->request);
    }

    public function isAdmin(): bool
    {
        return $this->auth->isAdmin($this->request);
    }

    public function identity(): mixed
    {
        return $this->auth->identity($this->request);
    }

    public function user(): mixed
    {
        return $this->auth->user($this->request);
    }

    public function id(): ?int
    {
        return $this->auth->id($this->request);
    }

    public function username(): ?string
    {
        return $this->auth->username($this->request);
    }

    public function can(string $permission): bool
    {
        return $this->auth->can($this->request, $permission);
    }

    public function banReason(): mixed
    {
        return $this->auth->banReason($this->request);
    }

    public function isBanned(): bool
    {
        return $this->auth->isBanned($this->request);
    }
}
