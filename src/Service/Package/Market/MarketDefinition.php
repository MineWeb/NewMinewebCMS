<?php
declare(strict_types=1);

namespace App\Service\Package\Market;

final class MarketDefinition
{
    public function __construct(
        public readonly string $source,
        public readonly string $repoPattern,
        public readonly string $channel,
        public readonly string $branch,
    ) {
    }

    public static function fromArray(array $cfg, self $defaults): ?self
    {
        $source = trim((string)($cfg['source'] ?? ''));
        if ($source === '') {
            return null;
        }

        $repoPattern = trim((string)($cfg['repoPattern'] ?? $defaults->repoPattern));
        $channel = trim((string)($cfg['channel'] ?? $defaults->channel));
        $branch = trim((string)($cfg['branch'] ?? $defaults->branch));

        if ($repoPattern === '') {
            $repoPattern = $defaults->repoPattern;
        }
        if ($channel !== 'release' && $channel !== 'branch') {
            $channel = $defaults->channel;
        }
        if ($branch === '') {
            $branch = $defaults->branch;
        }

        return new self($source, $repoPattern, $channel, $branch);
    }

    public function toMeta(): array
    {
        return [
            'source' => $this->source,
            'repoPattern' => $this->repoPattern,
            'channel' => $this->channel,
            'branch' => $this->branch,
        ];
    }

    public static function fromMeta(array $meta): ?self
    {
        $source = trim((string)($meta['source'] ?? ''));
        $repoPattern = trim((string)($meta['repoPattern'] ?? ''));
        $channel = trim((string)($meta['channel'] ?? ''));
        $branch = trim((string)($meta['branch'] ?? ''));

        if ($source === '' || $repoPattern === '' || ($channel !== 'release' && $channel !== 'branch') || $branch === '') {
            return null;
        }

        return new self($source, $repoPattern, $channel, $branch);
    }
}
