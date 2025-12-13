<?php
declare(strict_types=1);

namespace App\Service;

class InstallState
{
    public static function databasesFile(): string
    {
        return ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'databases.json';
    }

    public static function installLockFile(): string
    {
        return ROOT . DIRECTORY_SEPARATOR . 'config' . DIRECTORY_SEPARATOR . 'install.lock';
    }

    public static function isDatabaseConfigured(): bool
    {
        $file = self::databasesFile();
        if (!is_file($file)) {
            return false;
        }

        $json = file_get_contents($file);
        if ($json === false) {
            return false;
        }

        $data = json_decode($json, true);
        if (!is_array($data)) {
            return false;
        }

        return !empty($data['configured']) && $data['configured'] === true;
    }

    public static function isInstalled(): bool
    {
        return is_file(self::installLockFile());
    }

    public static function markDatabaseConfigured(array $data): bool
    {
        $data['configured'] = true;
        $json = json_encode($data, JSON_PRETTY_PRINT);
        if ($json === false) {
            return false;
        }

        return file_put_contents(self::databasesFile(), $json) !== false;
    }

    public static function markInstalled(): bool
    {
        $content = 'CREATED AT ' . date('H:i:s d/m/Y') . PHP_EOL;
        return file_put_contents(self::installLockFile(), $content) !== false;
    }
}
