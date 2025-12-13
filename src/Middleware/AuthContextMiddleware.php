<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\InstallState;
use App\Service\PermissionService;
use Cake\Datasource\ConnectionManager;
use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use PDOException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class AuthContextMiddleware implements MiddlewareInterface
{
    use LocatorAwareTrait;

    private PermissionService $permissionService;

    public function __construct(?PermissionService $permissionService = null)
    {
        $this->permissionService = $permissionService ?? new PermissionService();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = null;
        $isConnected = false;
        $permissions = [];

        $request = $request
            ->withAttribute('auth.identity', null)
            ->withAttribute('auth.isConnected', false)
            ->withAttribute('auth.permissions', []);

        if (!InstallState::isInstalled()) {
            return $handler->handle($request);
        }

        try {
            $session = $request->getSession();
            $userId = (int)$session->read('user');

            if ($userId <= 0) {
                return $handler->handle($request);
            }

            $Users = $this->fetchTable('Users');

            $identity = $Users
                ->find()
                ->select(['id', 'username', 'email', 'role_id'])
                ->where(['id' => $userId])
                ->first();

            if ($identity === null) {
                $session->delete('user');

                return $handler->handle($request);
            }

            $isConnected = true;

            $roleId = (int)($identity->get('role_id') ?? 0);
            $permissions = $roleId > 0 ? $this->permissionService->getRolePermissions($roleId) : [];
        } catch (PDOException $e) {
            Log::warning('AuthContextMiddleware PDO Error: ' . $e->getMessage());
        } catch (Throwable $e) {
            Log::error('AuthContextMiddleware Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
        }

        $request = $request
            ->withAttribute('auth.identity', $identity)
            ->withAttribute('auth.isConnected', $isConnected)
            ->withAttribute('auth.permissions', $permissions);

        return $handler->handle($request);
    }
}
