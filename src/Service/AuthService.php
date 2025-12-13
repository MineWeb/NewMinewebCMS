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

    public function rankId(ServerRequestInterface $request): int
    {
        $user = $this->identity($request);

        if (is_object($user) && method_exists($user, 'get')) {
            return (int)$user->get('rank');
        }

        return 0;
    }

    public function isConnected(ServerRequestInterface $request): bool
    {
        return (bool)$request->getAttribute('auth.isConnected', false);
    }

    public function isAdmin(ServerRequestInterface $request): bool
    {
        return (bool)$request->getAttribute('auth.isAdmin', false);
    }

    public function permissions(ServerRequestInterface $request): array
    {
        $perms = $request->getAttribute('auth.permissions', []);

        return is_array($perms) ? $perms : [];
    }

    public function can(ServerRequestInterface $request, string $perm): bool
    {
        if (!$this->isConnected($request)) {
            return false;
        }

        if ($this->isAdmin($request)) {
            return true;
        }

        $perms = $this->permissions($request);

        if (in_array('*', $perms, true) || in_array($perm, $perms, true)) {
            return true;
        }

        $rankId = $this->rankId($request);
        if ($rankId === 0) {
            return false;
        }

        return $this->permissionService->have($rankId, $perm);
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
