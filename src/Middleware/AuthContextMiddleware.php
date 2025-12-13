<?php
declare(strict_types=1);

namespace App\Middleware;

use Cake\Log\Log;
use Cake\ORM\Locator\LocatorAwareTrait;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class AuthContextMiddleware implements MiddlewareInterface
{
    use LocatorAwareTrait;

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $identity = null;
        $isConnected = false;
        $isAdmin = false;
        $permissions = [];

        try {
            $session = $request->getSession();
            $userId = $session->read('user');

            if ($userId) {
                $Users = $this->fetchTable('Users');
                $identity = $Users->find()->where(['id' => (int)$userId])->first();

                if ($identity) {
                    $isConnected = true;

                    $rank = (int)($identity->get('rank') ?? 0);
                    $isAdmin = ($rank === 3 || $rank === 4);

                    if ($isAdmin) {
                        $permissions = ['*'];
                    } else {
                        $Permissions = $this->fetchTable('Permissions');
                        $row = $Permissions->find()->where(['rank' => $rank])->first();

                        if ($row) {
                            $raw = (string)($row->get('permissions') ?? '');
                            $decoded = json_decode($raw, true);
                            $permissions = is_array($decoded) ? $decoded : [];
                        }
                    }
                } else {
                    $session->delete('user');
                    $isConnected = false;
                }
            }
        } catch (Throwable $e) {
            Log::error('AuthContextMiddleware Error: ' . $e->getMessage());
            Log::error($e->getTraceAsString());
        }

        $request = $request
            ->withAttribute('auth.identity', $identity)
            ->withAttribute('auth.isConnected', $isConnected)
            ->withAttribute('auth.isAdmin', $isAdmin)
            ->withAttribute('auth.permissions', $permissions);

        return $handler->handle($request);
    }
}
