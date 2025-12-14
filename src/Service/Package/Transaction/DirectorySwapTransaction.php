<?php
declare(strict_types=1);

namespace App\Service\Package\Transaction;

use App\Service\Package\Filesystem\FilesystemService;
use RuntimeException;

final class DirectorySwapTransaction
{
    private bool $applied = false;

    public function __construct(
        private readonly FilesystemService $fs,
        private readonly string $targetDir,
        private readonly string $stagedDir,
        private readonly string $backupDir,
    ) {
    }

    public function apply(): void
    {
        if (!is_dir($this->stagedDir)) {
            throw new RuntimeException('Staged directory missing: ' . $this->stagedDir);
        }

        $parent = dirname($this->targetDir);
        $this->fs->ensureDir($parent);
        $this->fs->ensureDir(dirname($this->backupDir));

        if (is_dir($this->backupDir)) {
            $this->fs->deleteDir($this->backupDir);
        }

        if (is_dir($this->targetDir)) {
            if (!@rename($this->targetDir, $this->backupDir)) {
                $this->fs->copyDir($this->targetDir, $this->backupDir);
                $this->fs->deleteDir($this->targetDir);
            }
        }

        if (!@rename($this->stagedDir, $this->targetDir)) {
            $this->fs->copyDir($this->stagedDir, $this->targetDir);
            $this->fs->deleteDir($this->stagedDir);
        }

        $this->applied = true;
    }

    public function rollback(): void
    {
        if (!$this->applied) {
            return;
        }

        if (is_dir($this->targetDir)) {
            $this->fs->deleteDir($this->targetDir);
        }

        if (is_dir($this->backupDir)) {
            if (!@rename($this->backupDir, $this->targetDir)) {
                $this->fs->copyDir($this->backupDir, $this->targetDir);
                $this->fs->deleteDir($this->backupDir);
            }
        }
    }

    public function commit(): void
    {
        if (is_dir($this->backupDir)) {
            $this->fs->deleteDir($this->backupDir);
        }
    }
}
