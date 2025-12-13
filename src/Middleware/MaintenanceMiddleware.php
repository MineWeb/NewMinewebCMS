<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\AuthService;
use App\Service\InstallState;
use Cake\Http\Response;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class MaintenanceMiddleware implements MiddlewareInterface
{
    use LocatorAwareTrait;

    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!InstallState::isInstalled()) {
            return $handler->handle($request);
        }

        if ((string)$request->getParam('controller') === 'Maintenance') {
            return $handler->handle($request);
        }

        if ($this->auth->can($request, 'BYPASS_MAINTENANCE')) {
            return $handler->handle($request);
        }

        $path = $request->getRequestTarget();

        try {
            $Maintenances = $this->fetchTable('Maintenances');
            $maintenance = $Maintenances->checkMaintenance($path);
            if ($maintenance) {
                return (new Response())
                    ->withHeader('Location', Router::url(['_name' => 'maintenance_index', $maintenance['url']]))
                    ->withStatus(302);
            }
        } catch (Throwable) {
        }

        return $handler->handle($request);
    }
}
