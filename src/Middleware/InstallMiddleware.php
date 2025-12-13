<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\InstallState;
use Cake\Http\Response;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class InstallMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (InstallState::isInstalled()) {
            return $handler->handle($request);
        }

        $path = $request->getUri()->getPath();

        if (
            str_starts_with($path, '/install')
            || str_starts_with($path, '/css')
            || str_starts_with($path, '/js')
            || str_starts_with($path, '/img')
            || str_starts_with($path, '/favicon')
        ) {
            return $handler->handle($request);
        }

        $response = new Response();

        return $response
            ->withHeader('Location', '/install')
            ->withStatus(302);
    }
}
