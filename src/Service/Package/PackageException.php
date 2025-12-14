<?php
declare(strict_types=1);

namespace App\Service\Package;

use RuntimeException;
use Throwable;

final class PackageException extends RuntimeException
{
    public function __construct(
        public readonly string $messageKey,
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($messageKey, $code, $previous);
    }
}
