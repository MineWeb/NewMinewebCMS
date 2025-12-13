<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\AuthService;
use Cake\Http\Exception\NotFoundException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequireDashboardAccessMiddleware implements MiddlewareInterface
{
    private AuthService $auth;

    public function __construct(?AuthService $auth = null)
    {
        $this->auth = $auth ?? new AuthService();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!$this->auth->can($request, 'ACCESS_DASHBOARD')) {
            throw new NotFoundException();
        }

        return $handler->handle($request);
    }
}
