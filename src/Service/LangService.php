<?php
declare(strict_types=1);

namespace App\Service;

use Cake\I18n\FrozenTime;
use Cake\I18n\I18n;
use Psr\Http\Message\ServerRequestInterface;

class LangService
{
    public static function history(string $action): string
    {
        $key = 'HISTORY__ACTION_' . $action;
        $translated = __($key);

        return $translated === $key ? $action : $translated;
    }

    public static function date(string $date, string $format = 'dd/MM/yyyy HH:mm'): string
    {
        return FrozenTime::parse($date)->i18nFormat($format);
    }

    public static function set(string $key, string $value): void
    {
        $locale = I18n::getLocale();
        $file = ROOT . '/resources/locales/' . $locale . '/default.json';

        if (!file_exists($file)) {
            return;
        }

        $json = json_decode(file_get_contents($file), true);
        if (!is_array($json)) {
            $json = [
                'INFORMATIONS' => [
                    'name' => $locale,
                    'version' => '1.0.0',
                    'author' => 'System',
                ],
                'MESSAGES' => [],
            ];
        }

        $messages = $json['MESSAGES'] ?? [];
        $messages[$key] = $value;

        $json['MESSAGES'] = $messages;

        file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function saveMany(array $data): void
    {
        $locale = I18n::getLocale();
        $file = ROOT . '/resources/locales/' . $locale . '/default.json';

        if (!file_exists($file)) {
            return;
        }

        $json = json_decode(file_get_contents($file), true);
        if (!is_array($json)) {
            $json = [
                'INFORMATIONS' => [
                    'name' => $locale,
                    'version' => '1.0.0',
                    'author' => 'System',
                ],
                'MESSAGES' => [],
            ];
        }

        $messages = $json['MESSAGES'] ?? [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) {
                continue;
            }
            if (!is_scalar($value)) {
                continue;
            }
            if ($key === '_csrfToken' || $key === 'xss') {
                continue;
            }

            $messages[$key] = (string)$value;
        }

        $json['MESSAGES'] = $messages;

        file_put_contents($file, json_encode($json, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function loadCurrentMessages(): array
    {
        $locale = I18n::getLocale();
        $file = ROOT . '/resources/locales/' . $locale . '/default.json';

        if (!file_exists($file)) {
            return [];
        }

        $json = json_decode(file_get_contents($file), true);
        if (!is_array($json)) {
            return [];
        }

        return $json['MESSAGES'] ?? [];
    }

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
