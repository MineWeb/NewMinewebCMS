<?php
declare(strict_types=1);

namespace App\Service\Package\Manifest;

use App\Service\Package\PackageException;
use App\Service\Package\PackageType;
use PharIo\Version\Version;
use Throwable;

final class PackageManifest
{
    public function __construct(
        public readonly PackageType $type,
        public readonly string $slug,
        public readonly string $name,
        public readonly string $author,
        public readonly string $version,
        public readonly string $repository,
        public readonly string $branch,
        public readonly array $requirements,
        public readonly array $permissions,
        public readonly array $locales,
        public readonly array $preserve,
    ) {
    }

    public static function fromArray(array $data): self
    {
        $typeRaw = trim((string)($data['type'] ?? ''));
        $type = PackageType::tryFrom($typeRaw);
        if ($type === null) {
            throw new PackageException('ERROR__MANIFEST_INVALID');
        }

        $slug = trim((string)($data['slug'] ?? ''));
        $name = trim((string)($data['name'] ?? ''));
        $author = trim((string)($data['author'] ?? ''));
        $version = trim((string)($data['version'] ?? ''));

        $repository = trim((string)($data['repository'] ?? ''));
        $branch = trim((string)($data['branch'] ?? ''));

        if ($slug === '' || $name === '' || $author === '' || $version === '') {
            throw new PackageException('ERROR__MANIFEST_INVALID');
        }

        if ($type !== PackageType::Cms && $repository === '') {
            throw new PackageException('ERROR__MANIFEST_INVALID');
        }

        if ($type !== PackageType::Cms && $branch === '') {
            $branch = '2.X';
        }

        try {
            new Version($version);
        } catch (Throwable) {
            throw new PackageException('ERROR__MANIFEST_INVALID_VERSION');
        }

        $requirements = is_array($data['requirements'] ?? null) ? (array)$data['requirements'] : [];
        $permissions = is_array($data['permissions'] ?? null) ? (array)$data['permissions'] : [];
        $locales = is_array($data['locales'] ?? null) ? (array)$data['locales'] : [];
        $preserve = is_array($data['preserve'] ?? null) ? (array)$data['preserve'] : [];

        $locales = array_values(array_filter(array_map(
            static fn($v) => str_replace('-', '_', trim((string)$v)),
            $locales
        ), static fn($v) => $v !== ''));

        $preserve = array_values(array_filter(array_map(
            static fn($v) => ltrim(str_replace('\\', '/', trim((string)$v)), '/'),
            $preserve
        ), static fn($v) => $v !== ''));

        $normalizedPermissions = [
            'available' => [],
            'defaults' => [],
        ];

        if (isset($permissions['available']) && is_array($permissions['available'])) {
            $normalizedPermissions['available'] = array_values(array_filter(array_map(
                static fn($v) => trim((string)$v),
                $permissions['available']
            ), static fn($v) => $v !== ''));
        }

        if (isset($permissions['defaults']) && is_array($permissions['defaults'])) {
            $normalizedPermissions['defaults'] = $permissions['defaults'];
        }

        return new self(
            $type,
            $slug,
            $name,
            $author,
            $version,
            $repository,
            $branch,
            $requirements,
            $normalizedPermissions,
            $locales,
            $preserve
        );
    }

    public static function fromJson(string $json): self
    {
        $decoded = json_decode($json, true);
        if (!is_array($decoded)) {
            throw new PackageException('ERROR__MANIFEST_INVALID');
        }

        return self::fromArray($decoded);
    }

    public function id(): string
    {
        return strtolower($this->author . '.' . $this->slug);
    }
}
