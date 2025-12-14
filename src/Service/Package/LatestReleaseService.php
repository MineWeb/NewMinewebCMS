<?php
declare(strict_types=1);

namespace App\Service\Package;

use App\Service\Package\Source\GitHubSource;
use Cake\Cache\Cache;

final class LatestReleaseService
{
    public function __construct(private readonly GitHubSource $github)
    {
    }

    public function latestTag(string $repository): ?string
    {
        $repository = trim($repository);
        if ($repository === '') {
            return null;
        }

        $cacheKey = 'github_latest_release_tag_' . sha1($repository);
        $cached = Cache::read($cacheKey);
        if (is_string($cached) && $cached !== '') {
            return $cached;
        }

        $res = $this->github->fetchLatestRelease($repository);
        $status = (int)($res['status'] ?? 0);
        $body = (string)($res['body'] ?? '');

        if ($status < 200 || $status >= 300 || $body === '') {
            return null;
        }

        $json = json_decode($body, true);
        if (!is_array($json)) {
            return null;
        }

        $tag = trim((string)($json['tag_name'] ?? $json['name'] ?? ''));
        if ($tag === '') {
            return null;
        }

        Cache::write($cacheKey, $tag);

        return $tag;
    }

    public function latestVersion(string $repository): ?string
    {
        $tag = $this->latestTag($repository);
        if ($tag === null) {
            return null;
        }

        $v = ltrim($tag, 'v');
        $v = trim($v);

        return $v !== '' ? $v : null;
    }
}
