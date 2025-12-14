<?php
declare(strict_types=1);

namespace App\Service\Package;

final class UpdateStateStore
{
    public function __construct(private readonly string $filePath)
    {
    }

    public function read(): ?array
    {
        if (!is_file($this->filePath)) {
            return null;
        }

        $raw = file_get_contents($this->filePath);
        if (!is_string($raw) || $raw === '') {
            return null;
        }

        $decoded = json_decode($raw, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function write(array $state): void
    {
        $dir = dirname($this->filePath);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        file_put_contents($this->filePath, json_encode($state, JSON_UNESCAPED_UNICODE));
    }

    public function clear(): void
    {
        if (is_file($this->filePath)) {
            @unlink($this->filePath);
        }
    }
}
