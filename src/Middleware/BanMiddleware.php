<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\AuthService;
use Cake\Http\Response;
use Cake\ORM\Locator\LocatorAwareTrait;
use Cake\Routing\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class BanMiddleware implements MiddlewareInterface
{
    use LocatorAwareTrait;

    private AuthService $auth;

    public function __construct()
    {
        $this->auth = new AuthService();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $request = $request->withAttribute('auth.ban', null);

        if ((string)$request->getParam('controller') === 'Ban') {
            return $handler->handle($request);
        }

        if ($this->auth->can($request, 'BYPASS_BAN')) {
            return $handler->handle($request);
        }

        $server = $request->getServerParams();
        $ip = (string)($server['HTTP_CF_CONNECTING_IP'] ?? $server['REMOTE_ADDR'] ?? '');
        if ($ip === '') {
            return $handler->handle($request);
        }

        try {
            $Bans = $this->fetchTable('Bans');

            $hit = $Bans->find()
                ->where(['ip' => $ip])
                ->orderByDesc('id')
                ->first();

            if ($hit !== null) {
                $reason = (string)($hit->get('reason') ?? '');
                $reason = $reason !== '' ? $reason : (string)__('BAN__BAN');

                $request = $request->withAttribute('auth.ban', $reason);

                return (new Response())
                    ->withHeader('Location', Router::url(['_name' => 'ban_ip']))
                    ->withStatus(302);
            }

            $identity = $this->auth->identity($request);
            if (is_object($identity) && method_exists($identity, 'get')) {
                $userId = $identity->get('id');
                if ($userId !== null) {
                    $hitUser = $Bans->find()
                        ->where(['user_id' => (int)$userId])
                        ->orderByDesc('id')
                        ->first();

                    if ($hitUser !== null) {
                        $reason = (string)($hitUser->get('reason') ?? '');
                        $reason = $reason !== '' ? $reason : (string)__('BAN__BAN');

                        $request = $request->withAttribute('auth.ban', $reason);

                        return (new Response())
                            ->withHeader('Location', Router::url(['_name' => 'ban_index']))
                            ->withStatus(302);
                    }
                }
            }
        } catch (Throwable) {
            return $handler->handle($request);
        }

        return $handler->handle($request);
    }
}
