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
            $file = is_string($file) ? trim($file) : '';
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
        $key = 'i18n_json_' . sha1($file);
        $cached = Cache::read($key);

        $mtime = @filemtime($file) ?: 0;

        if (is_array($cached) && isset($cached['mtime'], $cached['data']) && (int)$cached['mtime'] === (int)$mtime && is_array($cached['data'])) {
            return $cached['data'];
        }

        $raw = @file_get_contents($file);
        if (!is_string($raw) || trim($raw) === '') {
            Cache::write($key, ['mtime' => $mtime, 'data' => []]);
            return [];
        }

        $decoded = json_decode($raw, true);
        if (!is_array($decoded)) {
            Cache::write($key, ['mtime' => $mtime, 'data' => []]);
            return [];
        }

        $source = $decoded;

        if (isset($decoded['MESSAGES']) && is_array($decoded['MESSAGES'])) {
            $source = $decoded['MESSAGES'];
        } elseif (isset($decoded['messages']) && is_array($decoded['messages'])) {
            $source = $decoded['messages'];
        }

        $clean = [];
        foreach ($source as $k => $v) {
            if (!is_string($k) || $k === '') {
                continue;
            }
            if (is_string($v) || is_numeric($v)) {
                $clean[$k] = (string)$v;
            }
        }

        Cache::write($key, ['mtime' => $mtime, 'data' => $clean]);

        return $clean;
    }
}
