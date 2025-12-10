<?php
namespace App\I18n;

class JsonFileLoader
{
    public function loadFiles(array $files): array
    {
        $messages = [];

        foreach ($files as $file) {
            if (!is_string($file) || !file_exists($file)) {
                continue;
            }

            $data = json_decode(file_get_contents($file), true);
            if (!is_array($data)) {
                continue;
            }

            $msgs = $data['MESSAGES'] ?? $data;

            if (is_array($msgs)) {
                $messages = array_replace($messages, $msgs);
            }
        }

        return $messages;
    }
}
