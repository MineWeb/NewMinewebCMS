<?php
declare(strict_types=1);

namespace App\Service\Package\Transaction;

use App\Service\Package\Filesystem\FilesystemService;
use RuntimeException;

final class FileBackupTransaction
{
    private array $createdFiles = [];
    private array $backedUpFiles = [];

    public function __construct(
        private readonly FilesystemService $fs,
        private readonly string $rootDir,
        private readonly string $backupDir,
    ) {
    }

    public function backupTarget(string $relativePath): void
    {
        $relativePath = ltrim(str_replace('\\', '/', $relativePath), '/');
        if ($relativePath === '') {
            return;
        }

        $targetPath = rtrim($this->rootDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);

        if (!file_exists($targetPath)) {
            $this->createdFiles[] = $targetPath;

            return;
        }

        $backupPath = rtrim($this->backupDir, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $relativePath);
        $this->fs->ensureDir(dirname($backupPath));

        if (is_dir($targetPath)) {
            return;
        }

        if (!copy($targetPath, $backupPath)) {
            throw new RuntimeException('Unable to backup file: ' . $targetPath);
        }

        $this->backedUpFiles[] = [$backupPath, $targetPath];
    }

    public function rollback(): void
    {
        foreach ($this->createdFiles as $path) {
            if (is_file($path)) {
                @unlink($path);
            }
        }

        foreach (array_reverse($this->backedUpFiles) as [$backupPath, $targetPath]) {
            $this->fs->ensureDir(dirname($targetPath));
            if (!copy($backupPath, $targetPath)) {
                throw new RuntimeException('Unable to restore file: ' . $targetPath);
            }
        }

        if (is_dir($this->backupDir)) {
            $this->fs->deleteDir($this->backupDir);
        }
    }

    public function commit(): void
    {
        if (is_dir($this->backupDir)) {
            $this->fs->deleteDir($this->backupDir);
        }
    }
}
