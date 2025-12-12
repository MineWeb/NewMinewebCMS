<?php
declare(strict_types=1);

namespace App\Controller;

use App\Service\ConfigurationService;
use App\Service\LocaleService;
use Cake\Event\EventInterface;

/**
 * @property \App\Controller\Component\AuthComponent $Auth
 * @property \App\Controller\Component\EyPluginComponent $EyPlugin
 * @property \App\Controller\Component\ThemeComponent $Theme
 * @property \App\Controller\Component\UtilComponent $Util
 */
class AppController extends BaseController
{
    public string $View = 'Theme';

    public array $paginate = [];

    protected ConfigurationService $config;

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
    }

    public function beforeFilter(EventInterface $event): void
    {
        parent::beforeFilter($event);

        $localeService = new LocaleService();
        $locale = $localeService->resolveLocale($this->getRequest());
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

    public function sendGetRequest(string $url): string
    {
        $ch = curl_init();
        if ($ch === false) {
            return '';
        }

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_HTTPHEADER => ['User-Agent: MineWebCMS'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_SSL_VERIFYPEER => false,
        ]);

        $result = curl_exec($ch);
        curl_close($ch);

        return is_string($result) ? $result : '';
    }

    public function sendMultipleGetRequests(array|string $urls): array
    {
        if (!is_array($urls)) {
            $urls = [$urls];
        }

        $multi = curl_multi_init();
        if ($multi === false) {
            return [];
        }

        $channels = [];
        $results = [];

        foreach ($urls as $url) {
            if (!is_string($url) || $url === '') {
                continue;
            }

            $ch = curl_init();
            if ($ch === false) {
                continue;
            }

            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_HTTPHEADER => ['User-Agent: MineWebCMS'],
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_SSL_VERIFYPEER => false,
            ]);

            curl_multi_add_handle($multi, $ch);
            $channels[] = $ch;
        }

        do {
            $status = curl_multi_exec($multi, $active);
        } while ($status === CURLM_CALL_MULTI_PERFORM);

        while ($active && $status === CURLM_OK) {
            if (curl_multi_select($multi) === -1) {
                usleep(100);
                continue;
            }

            do {
                $status = curl_multi_exec($multi, $active);
            } while ($status === CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($channels as $ch) {
            $content = curl_multi_getcontent($ch);
            $results[] = is_string($content) ? $content : '';
            curl_multi_remove_handle($multi, $ch);
        }

        curl_multi_close($multi);

        return $results;
    }
}
