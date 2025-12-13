<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\I18n;
use Psr\Http\Message\ServerRequestInterface;

final class LocaleService
{

    public function resolveLocale(ServerRequestInterface $request, ?string $defaultLocale = null): string
    {
        $cookie = $request->getCookie('language');

        $header = $request->getHeaderLine('Accept-Language');
        $headerLocale = $header ? substr($header, 0, 5) : null;

        $cookie = $cookie ? str_replace('-', '_', $cookie) : null;
        $headerLocale = $headerLocale ? str_replace('-', '_', $headerLocale) : null;

        return $cookie ?: $defaultLocale ?: $headerLocale ?: 'fr_FR';
    }

    public function apply(string $locale): void
    {
        I18n::setLocale($locale);
    }

    public function htmlLang(string $locale): string
    {
        $lang = str_replace('_', '-', $locale);
        $lang = strtolower(substr($lang, 0, 2));

        return $lang ?: 'fr';
    }
}
