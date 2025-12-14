<?php
declare(strict_types=1);

namespace App\Service\Package\Manifest;

use App\Service\Package\PackageException;
use App\Service\Package\Source\GitHubSource;

final class ManifestLoader
{
    public function __construct(private readonly GitHubSource $github)
    {
    }

    public function loadLocal(string $packageRoot, string $manifestFile = 'manifest.json'): PackageManifest
    {
        $path = rtrim($packageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $manifestFile;
        if (!is_file($path) || !is_readable($path)) {
            throw new PackageException('ERROR__MANIFEST_MISSING');
        }

        $content = file_get_contents($path);
        if (!is_string($content) || $content === '') {
            throw new PackageException('ERROR__MANIFEST_INVALID');
        }

        return PackageManifest::fromJson($content);
    }

    public function loadRemote(string $repository, string $ref, string $manifestPath = 'manifest.json'): PackageManifest
    {
        $res = $this->github->fetchRaw($repository, $ref, $manifestPath);

        $status = (int)($res['status'] ?? 0);
        $body = (string)($res['body'] ?? '');

        if ($status === 404) {
            throw new PackageException('ERROR__PACKAGE_NOT_COMPATIBLE');
        }

        if ($status < 200 || $status >= 300 || $body === '') {
            throw new PackageException('ERROR__MANIFEST_UNREADABLE');
        }

        return PackageManifest::fromJson($body);
    }
}
