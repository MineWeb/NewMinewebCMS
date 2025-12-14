<?php
declare(strict_types=1);

namespace App\Service\Package;

use App\Service\HttpService;
use App\Service\Package\Filesystem\FilesystemService;
use App\Service\Package\Manifest\ManifestLoader;
use App\Service\Package\Requirement\RequirementChecker;
use App\Service\Package\Source\GitHubSource;
use App\Service\PermissionSynchronizer;

final class PackageManagerFactory
{
    public function create(): PackageManager
    {
        $http = new HttpService();
        $fs = new FilesystemService();
        $github = new GitHubSource($http);
        $manifests = new ManifestLoader($github);
        $permSync = new PermissionSynchronizer();
        $state = new UpdateStateStore(ROOT . DS . 'tmp' . DS . 'update' . DS . 'state.json');

        $packages = null;

        $requirements = new RequirementChecker(
            static fn(): string => $packages instanceof PackageManager ? $packages->cmsCurrentVersion() : '0.0.0'
        );

        return new PackageManager(
            $http,
            $fs,
            $github,
            $manifests,
            $permSync,
            $requirements,
            $state
        );
    }
}
