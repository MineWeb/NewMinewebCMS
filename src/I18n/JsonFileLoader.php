<?php
declare(strict_types=1);

namespace App\I18n;

use Cake\Cache\Cache;

final class JsonFileLoader
{
    public function loadFiles(array $files): array
    {
        $messages = [];

        foreach ($files as $file) {
            $file = (string)$file;
            if ($file === '' || !is_file($file)) {
                continue;
            }

            $data = $this->loadFileCached($file);
            if ($data === []) {
                continue;
            }

            $messages = $data + $messages;
        }

        return $messages;
    }

    private function loadFileCached(string $file): array
    {
        $mtime = @filemtime($file);
        $key = 'i18n_json_' . sha1($file . '|' . ($mtime ?: 0));

        $cached = Cache::read($key);
        if (is_array($cached)) {
            return $cached;
        }

        $raw = @file_get_contents($file);
        if (!is_string($raw) || $raw === '') {
            Cache::write($key, []);

            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            Cache::write($key, [], 'default');

            return [];
        }

        $clean = [];
        foreach ($decoded as $k => $v) {
            if (!is_string($k) || $k === '') {
                continue;
            }
            if (is_string($v) || is_numeric($v)) {
                $clean[$k] = (string)$v;
            }
        }

        Cache::write($key, $clean);

        return $clean;
    }
}
