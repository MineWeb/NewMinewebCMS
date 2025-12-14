<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Service\HttpService;
use App\Service\Package\Filesystem\FilesystemService;
use App\Service\Package\Manifest\ManifestLoader;
use App\Service\Package\PackageException;
use App\Service\Package\PackageManager;
use App\Service\Package\Requirement\RequirementChecker;
use App\Service\Package\Source\GitHubSource;
use App\Service\Package\UpdateStateStore;
use App\Service\PermissionSynchronizer;
use Cake\Controller\Component;
use Cake\Log\Log;
use Cake\Routing\Router;
use Throwable;

final class UpdateComponent extends Component
{
    public string $cmsVersion = '0.0.0';
    public string $lastVersion = '0.0.0';
    public string $errorUpdate = '';

    private PackageManager $packages;

    public function initialize(array $config): void
    {
        parent::initialize($config);

        $controller = $this->getController();
        if ($controller) {
            $controller->set('Update', $this);
        }

        $http = new HttpService();
        $fs = new FilesystemService();
        $github = new GitHubSource($http);
        $manifests = new ManifestLoader($github);
        $permSync = new PermissionSynchronizer();
        $state = new UpdateStateStore(ROOT . DS . 'tmp' . DS . 'update' . DS . 'state.json');
        $requirements = new RequirementChecker(fn() => $this->cmsVersion);

        $this->packages = new PackageManager(
            $http,
            $fs,
            $github,
            $manifests,
            $permSync,
            $requirements,
            $state
        );

        $this->errorUpdate = __('UPDATE__FAILED');

        $this->cmsVersion = $this->packages->cmsCurrentVersion();
        $this->lastVersion = $this->packages->cmsLatestVersion() ?: $this->cmsVersion;
    }

    public function clearLatestCache(): void
    {
        $this->packages->clearCmsLatestCache();
        $this->lastVersion = $this->packages->cmsLatestVersion() ?: $this->cmsVersion;
    }

    public function available(): string
    {
        if (version_compare($this->cmsVersion, $this->lastVersion, '<')) {
            $url = Router::url(['_name' => 'admin_update_index']);

            return "<div class='alert alert-secondary'>"
                . __('UPDATE__AVAILABLE_TYPE_CMS') . ' '
                . __('UPDATE__AVAILABLE') . ' '
                . __('UPDATE__CMS_VERSION') . ' : '
                . $this->cmsVersion . ', '
                . __('UPDATE__LAST_VERSION') . ' : '
                . $this->lastVersion . ' '
                . "<a href='" . $url . "' style='margin-top: -6px;' class='btn float-right'>"
                . __('GLOBAL__UPDATE')
                . '</a>'
                . '</div>';
        }

        return '';
    }

    public function updateCMS(bool $applyStep = false): bool
    {
        try {
            if (!$applyStep) {
                $this->packages->prepareCmsUpdate();
                $this->cmsVersion = $this->packages->cmsCurrentVersion();
                $this->lastVersion = $this->packages->cmsLatestVersion() ?: $this->cmsVersion;

                return true;
            }

            $this->packages->applyCmsUpdate();

            $this->cmsVersion = $this->packages->cmsCurrentVersion();
            $this->lastVersion = $this->packages->cmsLatestVersion() ?: $this->cmsVersion;

            return true;
        } catch (PackageException $e) {
            $this->errorUpdate = __($e->messageKey);
            Log::error('[Update] ' . $e->messageKey);

            return false;
        } catch (Throwable $e) {
            $this->errorUpdate = __('UPDATE__FAILED');
            Log::error('[Update] ' . $e->getMessage());

            return false;
        }
    }
}
