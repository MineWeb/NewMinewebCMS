<?php
declare(strict_types=1);

namespace App\Service\Package\Market;

final class MarketDefinition
{
    public function __construct(
        public readonly string $key,
        public readonly string $name,
        public readonly string $url,
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

        $key = trim((string)($cfg['key'] ?? ''));
        if ($key === '') {
            $key = sha1($source);
        }

        $name = trim((string)($cfg['name'] ?? ''));
        if ($name === '') {
            $name = self::deriveName($source);
        }

        $url = trim((string)($cfg['url'] ?? ''));

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

        return new self(
            key: $key,
            name: $name,
            url: $url,
            source: $source,
            repoPattern: $repoPattern,
            channel: $channel,
            branch: $branch,
        );
    }

    public function toMeta(): array
    {
        return [
            'key' => $this->key,
            'name' => $this->name,
            'url' => $this->url,
            'source' => $this->source,
            'repoPattern' => $this->repoPattern,
            'channel' => $this->channel,
            'branch' => $this->branch,
        ];
    }

    public static function fromMeta(array $meta): ?self
    {
        $key = trim((string)($meta['key'] ?? ''));
        $name = trim((string)($meta['name'] ?? ''));
        $url = trim((string)($meta['url'] ?? ''));
        $source = trim((string)($meta['source'] ?? ''));
        $repoPattern = trim((string)($meta['repoPattern'] ?? ''));
        $channel = trim((string)($meta['channel'] ?? ''));
        $branch = trim((string)($meta['branch'] ?? ''));

        if ($source === '' || $repoPattern === '' || ($channel !== 'release' && $channel !== 'branch') || $branch === '') {
            return null;
        }

        if ($key === '') {
            $key = sha1($source);
        }

        if ($name === '') {
            $name = self::deriveName($source);
        }

        return new self($key, $name, $url, $source, $repoPattern, $channel, $branch);
    }

    private static function deriveName(string $source): string
    {
        $parts = parse_url($source);
        if (is_array($parts)) {
            $host = trim((string)($parts['host'] ?? ''));
            if ($host !== '') {
                return $host;
            }
        }

        return 'market';
    }
}
