<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\AuthService;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RequireGuestMiddleware implements MiddlewareInterface
{
    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if ($this->auth->isConnected($request)) {
            return (new Response())->withHeader('Location', '/')->withStatus(302);
        }

        return $handler->handle($request);
    }
}
