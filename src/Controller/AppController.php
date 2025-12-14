<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\AddonService;
use App\Service\ConfigurationService;
use App\Service\LangService;
use App\Service\PermissionService;
use App\Service\ServerBridgeService;
use App\Service\ThemeService;
use Cake\Event\EventInterface;

/**
 * @property \App\Controller\Component\AuthComponent $Auth
 * @property \App\Controller\Component\UtilComponent $Util
 */
class AppController extends BaseController
{
    public string $View = 'Theme';

    public array $paginate = [];

    protected ConfigurationService $config;
    protected PermissionService $permissions;
    protected AddonService $addons;
    protected ThemeService $themes;
    protected ServerBridgeService $serverBridge;


    public function initialize(): void
    {
        parent::initialize();

        $componentsDir = opendir(APP . DIRECTORY_SEPARATOR . 'Controller' . DIRECTORY_SEPARATOR . 'Component');
        if ($componentsDir === false) {
            return;
        }

        while (($entry = readdir($componentsDir)) !== false) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (!str_ends_with($entry, 'Component.php')) {
                continue;
            }

            if (
                str_starts_with($entry, 'API')
                || str_starts_with($entry, 'Captcha')
                || str_starts_with($entry, 'DataTable')
            ) {
                continue;
            }

            $componentName = str_replace('Component.php', '', $entry);

            if ($this->components()->has($componentName)) {
                continue;
            }

            $this->loadComponent($componentName);
        }

        closedir($componentsDir);

        $this->config = new ConfigurationService();
        $this->permissions = new PermissionService();
        $this->addons = new AddonService();
        $this->serverBridge = new ServerBridgeService();
        $this->themes = new ThemeService();
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $siteConfigLang = $this->config->get('lang');

        $localeService = new LangService();

        $locale = $localeService->resolveLocale($this->getRequest(), (string)$siteConfigLang);

        $localeService->apply($locale);

        $this->set('currentLocale', $locale);
        $this->set('isAdminPrefix', $this->getRequest()->getParam('prefix') === 'Admin');

        $identity = $this->Auth->identity();
        $this->set('user', $identity ? (array)$identity : []);
    }

    public function beforeRender(EventInterface $event): void
    {
        parent::beforeRender($event);

        if ($this->getRequest()->getParam('prefix') === 'Admin') {
            $this->viewBuilder()->setLayout('admin');
        }
    }
}
