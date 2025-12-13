<?php
declare(strict_types=1);

namespace App\Service;

use App\Model\Entity\User;
use Cake\Http\Exception\ForbiddenException;
use Psr\Http\Message\ServerRequestInterface;

final class AuthService
{
    private PermissionService $permissionService;

    public function __construct(?PermissionService $permissionService = null)
    {
        $this->permissionService = $permissionService ?? new PermissionService();
    }

    public function identity(ServerRequestInterface $request): ?User
    {
        return $request->getAttribute('auth.identity');
    }

    public function user(ServerRequestInterface $request): ?User
    {
        return $this->identity($request);
    }

    public function id(ServerRequestInterface $request): ?int
    {
        $user = $this->identity($request);

        return is_object($user) && method_exists($user, 'get') ? (int)$user->get('id') : null;
    }

    public function username(ServerRequestInterface $request): ?string
    {
        $user = $this->identity($request);

        return is_object($user) && method_exists($user, 'get') ? (string)$user->get('username') : null;
    }

    public function roleId(ServerRequestInterface $request): int
    {
        $user = $this->identity($request);

        if (is_object($user) && method_exists($user, 'get')) {
            return (int)$user->get('role_id');
        }

        return 0;
    }

    public function isConnected(ServerRequestInterface $request): bool
    {
        return (bool)$request->getAttribute('auth.isConnected', false);
    }

    public function isAdmin(ServerRequestInterface $request): bool
    {
        if (!$this->isConnected($request)) {
            return false;
        }

        $roleId = $this->roleId($request);
        if ($roleId <= 0) {
            return false;
        }

        return $this->permissionService->isSuper($roleId);
    }

    public function can(ServerRequestInterface $request, string $perm): bool
    {
        if (!$this->isConnected($request)) {
            return false;
        }

        $roleId = $this->roleId($request);
        if ($roleId <= 0) {
            return false;
        }

        return $this->permissionService->canRole($roleId, $perm);
    }

    public function require(ServerRequestInterface $request, string $perm): void
    {
        if (!$this->can($request, $perm)) {
            throw new ForbiddenException();
        }
    }

    public function banReason(ServerRequestInterface $request): mixed
    {
        return $request->getAttribute('auth.ban');
    }

    public function isBanned(ServerRequestInterface $request): bool
    {
        return $this->banReason($request) !== null;
    }
}
