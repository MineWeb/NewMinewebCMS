<?php
declare(strict_types=1);

namespace App\Service\Package\Requirement;

use App\Service\Package\Manifest\PackageManifest;
use App\Service\Package\PackageException;
use Cake\Core\Configure;
use Closure;
use PharIo\Version\Version;
use PharIo\Version\VersionConstraintParser;
use Throwable;

final class RequirementChecker
{
    private Closure $cmsVersionResolver;

    public function __construct(callable $cmsVersionResolver)
    {
        $this->cmsVersionResolver = $cmsVersionResolver(...);
    }

    public function assertSatisfied(PackageManifest $manifest, callable $addonVersionResolver, callable $themeVersionResolver): void
    {
        $req = $manifest->requirements;

        $parser = new VersionConstraintParser();

        if (isset($req['php']) && is_string($req['php']) && $req['php'] !== '') {
            $this->assertConstraint($parser, 'php', PHP_VERSION, $req['php'], 'ERROR__PACKAGE_REQUIREMENTS');
        }

        if (isset($req['cms']) && is_string($req['cms']) && $req['cms'] !== '') {
            $cmsVersion = (string)($this->cmsVersionResolver)();
            $this->assertConstraint($parser, 'cms', $cmsVersion, $req['cms'], 'ERROR__PACKAGE_REQUIREMENTS');
        }

        if (isset($req['cakephp']) && is_string($req['cakephp']) && $req['cakephp'] !== '') {
            $cake = Configure::version();
            $this->assertConstraint($parser, 'cakephp', $cake, $req['cakephp'], 'ERROR__PACKAGE_REQUIREMENTS');
        }

        if (isset($req['extensions']) && is_array($req['extensions'])) {
            foreach ($req['extensions'] as $ext) {
                $ext = trim((string)$ext);
                if ($ext === '') {
                    continue;
                }
                if (!extension_loaded($ext)) {
                    throw new PackageException('ERROR__PACKAGE_REQUIREMENTS');
                }
            }
        }

        if (isset($req['addons']) && is_array($req['addons'])) {
            foreach ($req['addons'] as $addonKey => $constraint) {
                $addonKey = trim((string)$addonKey);
                $constraint = trim((string)$constraint);
                if ($addonKey === '' || $constraint === '') {
                    continue;
                }

                $installed = (string)call_user_func($addonVersionResolver, $addonKey);
                if ($installed === '') {
                    throw new PackageException('ERROR__PACKAGE_REQUIREMENTS');
                }

                $this->assertConstraint($parser, 'addon:' . $addonKey, $installed, $constraint, 'ERROR__PACKAGE_REQUIREMENTS');
            }
        }

        if (isset($req['themes']) && is_array($req['themes'])) {
            foreach ($req['themes'] as $themeKey => $constraint) {
                $themeKey = trim((string)$themeKey);
                $constraint = trim((string)$constraint);
                if ($themeKey === '' || $constraint === '') {
                    continue;
                }

                $installed = (string)call_user_func($themeVersionResolver, $themeKey);
                if ($installed === '') {
                    throw new PackageException('ERROR__PACKAGE_REQUIREMENTS');
                }

                $this->assertConstraint($parser, 'theme:' . $themeKey, $installed, $constraint, 'ERROR__PACKAGE_REQUIREMENTS');
            }
        }
    }

    private function assertConstraint(
        VersionConstraintParser $parser,
        string $label,
        string $installed,
        string $constraint,
        string $errorKey,
    ): void {
        try {
            $needed = $parser->parse($constraint);
            $installedV = new Version($installed);
            if (!$needed->complies($installedV)) {
                throw new PackageException($errorKey);
            }
        } catch (PackageException $e) {
            throw $e;
        } catch (Throwable) {
            throw new PackageException($errorKey);
        }
    }
}
