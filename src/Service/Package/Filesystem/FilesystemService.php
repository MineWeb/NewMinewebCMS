<?php
declare(strict_types=1);

namespace App\Service\Package\Filesystem;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RuntimeException;

final class FilesystemService
{
    public function ensureDir(string $dir, int $mode = 0775): void
    {
        if (is_dir($dir)) {
            return;
        }

        if (!mkdir($dir, $mode, true) && !is_dir($dir)) {
            throw new RuntimeException('Unable to create directory: ' . $dir);
        }
    }

    public function deleteDir(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($it as $file) {
            $path = $file->getPathname();
            if ($file->isDir()) {
                @rmdir($path);
                continue;
            }
            @unlink($path);
        }

        @rmdir($dir);
    }

    public function copyDir(string $src, string $dst): void
    {
        if (!is_dir($src)) {
            throw new RuntimeException('Source directory not found: ' . $src);
        }

        $this->ensureDir($dst);

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($src, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $file) {
            $srcPath = $file->getPathname();
            $rel = substr($srcPath, strlen(rtrim($src, DIRECTORY_SEPARATOR)) + 1);
            $dstPath = rtrim($dst, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $rel;

            if ($file->isDir()) {
                $this->ensureDir($dstPath);
                continue;
            }

            $this->ensureDir(dirname($dstPath));

            if (!copy($srcPath, $dstPath)) {
                throw new RuntimeException('Unable to copy file: ' . $srcPath);
            }
        }
    }

    public function listFiles(string $dir): array
    {
        if (!is_dir($dir)) {
            return [];
        }

        $out = [];

        $it = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($it as $file) {
            if (!$file->isFile()) {
                continue;
            }

            $path = $file->getPathname();
            $out[] = $path;
        }

        return $out;
    }

    public function normalizeRelative(string $absolutePath, string $baseDir): string
    {
        $base = rtrim(str_replace('\\', '/', $baseDir), '/') . '/';
        $abs = str_replace('\\', '/', $absolutePath);

        if (!str_starts_with($abs, $base)) {
            return ltrim($abs, '/');
        }

        return ltrim(substr($abs, strlen($base)), '/');
    }

    public function matchesAny(string $relativePath, array $patterns): bool
    {
        $p = ltrim(str_replace('\\', '/', $relativePath), '/');

        foreach ($patterns as $pattern) {
            $pattern = ltrim(str_replace('\\', '/', (string)$pattern), '/');

            if ($pattern === '') {
                continue;
            }

            if ($p === $pattern) {
                return true;
            }

            if (str_ends_with($pattern, '/')) {
                if (str_starts_with($p, $pattern)) {
                    return true;
                }
            }

            if (str_contains($pattern, '*') && fnmatch($pattern, $p)) {
                return true;
            }

            if (str_ends_with($pattern, '*')) {
                $prefix = rtrim($pattern, '*');
                if ($prefix !== '' && str_starts_with($p, $prefix)) {
                    return true;
                }
            }
        }

        return false;
    }
}
