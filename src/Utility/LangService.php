<?php
namespace App\Utility;

use Cake\I18n\I18n;
use Cake\I18n\FrozenTime;

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
                    'author' => 'System'
                ],
                'MESSAGES' => []
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
                    'author' => 'System'
                ],
                'MESSAGES' => []
            ];
        }

        $messages = $json['MESSAGES'] ?? [];

        foreach ($data as $key => $value) {
            if (!is_string($key)) continue;
            if (!is_scalar($value)) continue;
            if ($key === '_csrfToken' || $key === 'xss') continue;

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
}
