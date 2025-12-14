<?php
declare(strict_types=1);

namespace App\Service\Package\Source;

use App\Service\HttpService;
use Cake\Core\Configure;

final class GitHubSource
{
    public function __construct(private readonly HttpService $http)
    {
    }

    public function rawUrl(string $repository, string $branch, string $path): string
    {
        $path = ltrim($path, '/');

        return 'https://raw.githubusercontent.com/' . $repository . '/' . $branch . '/' . $path;
    }

    public function branchZipUrl(string $repository, string $branch): string
    {
        return 'https://github.com/' . $repository . '/archive/refs/heads/' . $branch . '.zip';
    }

    public function apiUrl(string $path): string
    {
        $path = ltrim($path, '/');

        return 'https://api.github.com/' . $path;
    }

    public function githubHeaders(): array
    {
        $cfg = (array)Configure::read('Update.github', []);
        $tokenEnv = (string)($cfg['tokenEnv'] ?? 'GITHUB_TOKEN');
        $userAgent = (string)($cfg['userAgent'] ?? 'MineWebCMS');

        $headers = [
            'User-Agent' => $userAgent,
            'Accept' => 'application/vnd.github+json',
        ];

        $token = (string)env($tokenEnv);
        if ($token !== '') {
            $headers['Authorization'] = 'Bearer ' . $token;
        }

        return $headers;
    }

    public function fetchRaw(string $repository, string $branch, string $path): array
    {
        $url = $this->rawUrl($repository, $branch, $path);

        return $this->http->get($url, [
            'headers' => $this->githubHeaders(),
        ]);
    }

    public function fetchLatestRelease(string $repository): array
    {
        $url = $this->apiUrl('repos/' . $repository . '/releases/latest');

        return $this->http->get($url, [
            'headers' => $this->githubHeaders(),
        ]);
    }
}
