<?php
declare(strict_types=1);

namespace App\Middleware;

use App\Service\ConfigurationService;
use App\Service\InstallState;
use App\Service\LangService;
use Cake\Core\Configure;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Throwable;

final class LocaleMiddleware implements MiddlewareInterface
{
    private LangService $lang;

    public function __construct(?LangService $lang = null)
    {
        $this->lang = $lang ?? new LangService();
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $defaultLocale = (string)Configure::read('App.defaultLocale', 'fr_FR');

        if (InstallState::isInstalled()) {
            try {
                $config = new ConfigurationService();
                $configuredLocale = (string)$config->get('lang');
                if ($configuredLocale !== '') {
                    $defaultLocale = $configuredLocale;
                }
            } catch (Throwable) {
            }
        }

        $locale = $this->lang->resolveLocale($request, $defaultLocale);
        $this->lang->apply($locale);

        $request = $request->withAttribute('app.locale', $locale);

        return $handler->handle($request);
    }
}
