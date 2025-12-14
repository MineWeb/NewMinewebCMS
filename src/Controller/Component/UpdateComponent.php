<?php
declare(strict_types=1);

namespace App\Controller\Component;

use App\Service\Package\PackageException;
use App\Service\Package\PackageManager;
use App\Service\Package\PackageManagerFactory;
use Cake\Controller\Component;
use Cake\Log\Log;
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

        $this->packages = (new PackageManagerFactory())->create();

        $this->errorUpdate = __('UPDATE__FAILED');

        $this->cmsVersion = $this->packages->cmsCurrentVersion();
        $this->lastVersion = $this->packages->cmsLatestVersion() ?: $this->cmsVersion;
    }

    public function clearLatestCache(): void
    {
        $this->packages->clearCmsLatestCache();
        $this->lastVersion = $this->packages->cmsLatestVersion() ?: $this->cmsVersion;
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
