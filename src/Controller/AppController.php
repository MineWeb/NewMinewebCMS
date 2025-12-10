<?php
declare(strict_types=1);

namespace App\Controller;

use Cake\Controller\Controller;
use Cake\Core\Configure;
use Cake\Event\Event;
use Cake\Event\EventInterface;
use Cake\Http\Response;
use Cake\I18n\I18n;
use Cake\Routing\Router;

define('TIMESTAMP_DEBUT', microtime(true));

class AppController extends BaseController
{
    public string $View = 'Theme';

    protected bool $isConnected = false;
    protected mixed $isBanned = false;

    public array $paginate = [];

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
            if (str_starts_with($entry, 'API') || str_starts_with($entry, 'Captcha') || str_starts_with($entry, 'DataTable')) {
                continue;
            }

            $componentName = str_replace('Component.php', '', $entry);
            $this->loadComponent($componentName);
        }

        closedir($componentsDir);
    }

    public function beforeFilter(EventInterface $event): ?Response
    {
        $response = parent::beforeFilter($event);
        if ($response instanceof Response) {
            return $response;
        }

        $this->setLocale();

        $loginCondition = $this->getRequest()->getRequestTarget() !== '/login' || !$this->EyPlugin->isInstalled('phpierre.signinup');

        if ($this->request->getParam('controller') !== 'User' && $loginCondition) {
            if ($this->isIPBan($this->Util->getIP()) && $this->request->getParam('controller') !== 'Ban' && !$this->Permissions->can('BYPASS_BAN')) {
                return $this->redirect([
                    'controller' => 'Ban',
                    'action' => 'ip',
                    'plugin' => false,
                ]);
            }

            $this->Maintenance = $this->fetchTable('Maintenance');
            if ($this->request->getParam('controller') !== 'Maintenance' && !$this->Permissions->can('BYPASS_MAINTENANCE')) {
                $maintenance = $this->Maintenance->checkMaintenance($this->getRequest()->getRequestTarget(), $this->Util);
                if ($maintenance) {
                    return $this->redirect([
                        'controller' => 'Maintenance',
                        'action' => $maintenance['url'],
                        'plugin' => false,
                    ]);
                }
            }
        }

        if ($this->request->getParam('plugin')) {
            $plugin = $this->EyPlugin->findPlugin('slugLower', $this->request->getParam('plugin'));
            if (!empty($plugin) && !$plugin->loaded) {
                return $this->redirect('/');
            }
        }

        $this->__initConfiguration();
        $this->__initUser();
        $this->__initWebsiteInfos();

        if ($this->getRequest()->getParam('prefix') === 'Admin') {
            $this->viewBuilder()->setLayout('admin');
            if (!$this->request->is('ajax')) {
                $this->__initAdminNavbar();
            }
        } else {
            if (!$this->request->is('ajax')) {
                $this->__initNavbar();
                $this->__initServerInfos();
            }
        }

        $this->EyPlugin->initEventsListeners($this);

        $requestEvent = new Event('requestPage', $this, $this->request->getData());
        $this->getEventManager()->dispatch($requestEvent);
        if ($requestEvent->isStopped()) {
            return $requestEvent->getResult();
        }

        if ($this->request->is('post')) {
            $postEvent = new Event('onPostRequest', $this, $this->request->getData());
            $this->getEventManager()->dispatch($postEvent);
            if ($postEvent->isStopped()) {
                return $postEvent->getResult();
            }
        }

        return null;
    }

    protected function setLocale(): void
    {
        $cookie = $this->request->getCookie('language');
        $bddLang = isset($this->Configuration) ? $this->Configuration->getKey('lang') : null;

        $header = $this->request->getHeaderLine('Accept-Language');
        $headerLocale = $header ? substr($header, 0, 5) : null;

        if ($headerLocale) {
            $headerLocale = str_replace('-', '_', $headerLocale);
        }

        if ($cookie) {
            $cookie = str_replace('-', '_', $cookie);
        }

        if ($bddLang) {
            $bddLang = str_replace('-', '_', $bddLang);
        }

        $locale = $cookie ?: $bddLang ?: $headerLocale ?: 'fr_FR';

        I18n::setLocale($locale);
        $this->set('currentLocale', $locale);
    }

    protected function __initConfiguration(): void
    {
        $this->Configuration = $this->fetchTable('Configuration');
        $this->set('Configuration', $this->Configuration);

        $website_name = $this->Configuration->getKey('name');
        [$theme_name, $theme_config] = $this->Theme->getCurrentTheme();
        Configure::write('theme', $theme_name);
        $this->__setTheme();

        $session_type = $this->Configuration->getKey('session_type');
        if ($session_type) {
            Configure::write('Session', [
                'defaults' => $session_type,
            ]);
        }

        $facebook_link = $this->Configuration->getKey('facebook');
        $skype_link = $this->Configuration->getKey('skype');
        $youtube_link = $this->Configuration->getKey('youtube');
        $twitter_link = $this->Configuration->getKey('twitter');

        $google_analytics = $this->Configuration->getKey('google_analytics');
        $configuration_end_code = $this->Configuration->getKey('end_layout_code');
        $condition = $this->Configuration->getKey('condition');

        $this->SocialButton = $this->fetchTable('Social');
        $findSocialButtons = $this->SocialButton->find()
            ->orderBy('order')
            ->all();

        $type = '';
        switch ($this->Configuration->getKey('captcha_type')) {
            case '1':
                $type = 'default';
                break;
            case '2':
                $type = 'google';
                break;
            case '3':
                $type = 'hcaptcha';
                break;
        }

        $captcha = [
            'type' => $type,
            'siteKey' => $this->Configuration->getKey('captcha_sitekey'),
        ];
        $reCaptcha = $captcha;

        $this->set(compact(
            'reCaptcha',
            'captcha',
            'condition',
            'website_name',
            'theme_config',
            'facebook_link',
            'skype_link',
            'youtube_link',
            'twitter_link',
            'findSocialButtons',
            'google_analytics',
            'configuration_end_code'
        ));
    }

    protected function __setTheme(): void
    {
        $prefix = $this->getRequest()->getParam('prefix');
        if ($prefix === null || $prefix !== 'Admin' || ($prefix !== null && $prefix === 'Admin' && $this->response->getStatusCode() >= 400)) {
            $this->theme = Configure::read('theme');
        }
    }

    private function __initUser(): void
    {
        $this->User = $this->fetchTable('User');

        if (
            !$this->User->isConnected()
            && ($cookie = $this->getRequest()->getCookie('remember_me'))
            && isset($cookie['pseudo'], $cookie['password'])
        ) {
            $user = $this->User->find('first', conditions: ['pseudo' => $cookie['pseudo']]);
            if (!empty($user) && $user['User']['password'] === $cookie['password']) {
                $this->getRequest()->getSession()->write('user', $user['User']['id']);
            }
        }

        $this->isConnected = $this->User->isConnected();
        $this->set('isConnected', $this->isConnected);

        if ($this->isConnected) {
            $loginCondition = $this->getRequest()->getRequestTarget() !== '/login' || !$this->EyPlugin->isInstalled('phpierre.signinup');
            if (
                $this->getRequest()->getParam('controller') !== 'User'
                && $this->getRequest()->getParam('controller') !== 'Ban'
                && $this->User->isBanned()
                && $loginCondition
            ) {
                $this->redirect([
                    'controller' => 'Ban',
                    'action' => 'index',
                    'plugin' => false,
                    'admin' => false,
                ]);
            }
        }

        $user = $this->isConnected ? $this->User->getAllFromCurrentUser() : [];
        if (!empty($user)) {
            $user['isAdmin'] = $this->User->isAdmin();
        }

        $this->set(compact('user'));
    }

    public function __initWebsiteInfos(): void
    {
        $this->Visit = $this->fetchTable('Visit');

        $users_count = $this->User->find()->count();
        $users_last = $this->User->find()
            ->orderByDesc('created')
            ->first();

        $users_count_today = $this->User->find()
            ->where(['created LIKE' => date('Y-m-d') . '%'])
            ->count();

        $visits_count = $this->Visit->getVisitsCount();
        $visits_count_today = $this->Visit->getVisitsByDay(date('Y-m-d'))['count'];
        $admin_dark_mode = (bool)$this->getRequest()->getCookie('use_admin_dark_mode');
        $csrfToken = $this->getRequest()->getAttribute('csrfToken');

        $this->set(compact(
            'users_count',
            'users_last',
            'users_count_today',
            'visits_count',
            'visits_count_today',
            'admin_dark_mode',
            'csrfToken'
        ));
    }

    public function __initAdminNavbar(): void
    {
        $nav = [
            'Dashboard' => [
                'icon' => 'fas fa-tachometer-alt',
                'route' => ['controller' => 'admin', 'action' => 'index', 'admin' => true, 'plugin' => false],
            ],
            'GLOBAL__ADMIN_GENERAL' => [
                'icon' => 'cogs',
                'menu' => [
                    'USER__MEMBERS_REGISTERED' => [
                        'icon' => 'users',
                        'permission' => 'MANAGE_USERS',
                        'route' => ['controller' => 'user', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'BAN__MEMBERS' => [
                        'icon' => 'ban',
                        'permission' => 'MANAGE_BAN',
                        'route' => ['controller' => 'ban', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'PERMISSIONS__LABEL' => [
                        'icon' => 'user',
                        'permission' => 'MANAGE_PERMISSIONS',
                        'route' => ['controller' => 'permissions', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'CONFIG__GENERAL_PREFERENCES' => [
                        'icon' => 'cog',
                        'permission' => 'MANAGE_CONFIGURATION',
                        'route' => ['controller' => 'configuration', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'STATS__TITLE' => [
                        'icon' => 'far fa-chart-bar',
                        'permission' => 'VIEW_STATISTICS',
                        'route' => ['controller' => 'statistics', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'MAINTENANCE__TITLE' => [
                        'icon' => 'fas fa-hand-paper',
                        'permission' => 'MANAGE_MAINTENANCE',
                        'route' => ['controller' => 'maintenance', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                ],
            ],
            'GLOBAL__CUSTOMIZE' => [
                'icon' => 'fas fa-copy',
                'menu' => [
                    'NEWS__TITLE' => [
                        'icon' => 'fas fa-pencil-ruler',
                        'permission' => 'MANAGE_NEWS',
                        'route' => ['controller' => 'news', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'PAGE__TITLE' => [
                        'icon' => 'fas fa-file-alt',
                        'permission' => 'MANAGE_PAGE',
                        'route' => ['controller' => 'pages', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'NAVBAR__TITLE' => [
                        'icon' => 'fas fa-bars',
                        'permission' => 'MANAGE_NAV',
                        'route' => ['controller' => 'navbar', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'SEO__TITLE' => [
                        'icon' => 'fab fa-google',
                        'permission' => 'MANAGE_SEO',
                        'route' => ['controller' => 'seo', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'SOCIAL__TITLE' => [
                        'icon' => 'fas fa-share-alt',
                        'permission' => 'MANAGE_SOCIAL',
                        'route' => ['controller' => 'social', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'MOTD__TITLE' => [
                        'icon' => 'fas fa-sort-amount-up-alt',
                        'permission' => 'MANAGE_MOTD',
                        'route' => ['controller' => 'motd', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                ],
            ],
            'SERVER__TITLE' => [
                'icon' => 'server',
                'permission' => 'MANAGE_SERVERS',
                'menu' => [
                    'SERVER__LINK' => [
                        'icon' => 'fas fa-arrows-alt-h',
                        'permission' => 'MANAGE_SERVERS',
                        'route' => ['controller' => 'server', 'action' => 'link', 'admin' => true, 'plugin' => false],
                    ],
                    'SERVER__BANLIST' => [
                        'icon' => 'ban',
                        'permission' => 'MANAGE_SERVERS',
                        'route' => ['controller' => 'server', 'action' => 'banlist', 'admin' => true, 'plugin' => false],
                    ],
                    'SERVER__WHITELIST' => [
                        'icon' => 'list',
                        'permission' => 'MANAGE_SERVERS',
                        'route' => ['controller' => 'server', 'action' => 'whitelist', 'admin' => true, 'plugin' => false],
                    ],
                    'SERVER__ONLINE_PLAYERS' => [
                        'icon' => 'list-ul',
                        'permission' => 'MANAGE_SERVERS',
                        'route' => ['controller' => 'server', 'action' => 'online', 'admin' => true, 'plugin' => false],
                    ],
                    'SERVER__CMD' => [
                        'icon' => 'key',
                        'permission' => 'MANAGE_SERVERS',
                        'route' => ['controller' => 'server', 'action' => 'cmd', 'admin' => true, 'plugin' => false],
                    ],
                ],
            ],
            'GLOBAL__ADMIN_PLUGINS' => [
                'icon' => 'puzzle-piece',
            ],
            'GLOBAL__ADMIN_LOGS_TITLE' => [
                'icon' => 'scroll',
                'menu' => [
                    'LOG__VIEW_ERROR' => [
                        'icon' => 'exclamation-circle',
                        'permission' => 'VIEW_WEBSITE_LOGS',
                        'route' => ['controller' => 'log', 'action' => 'error', 'admin' => true, 'plugin' => false],
                    ],
                    'LOG__VIEW_DEBUG' => [
                        'icon' => 'exclamation-triangle',
                        'permission' => 'VIEW_WEBSITE_LOGS',
                        'route' => ['controller' => 'log', 'action' => 'debug', 'admin' => true, 'plugin' => false],
                    ],
                ],
            ],
            'GLOBAL__ADMIN_OTHER_TITLE' => [
                'icon' => 'fas fa-folder-open',
                'menu' => [
                    'PLUGIN__TITLE' => [
                        'icon' => 'plus',
                        'permission' => 'MANAGE_PLUGINS',
                        'route' => ['controller' => 'plugin', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'THEME__TITLE' => [
                        'icon' => 'mobile',
                        'permission' => 'MANAGE_THEMES',
                        'route' => ['controller' => 'theme', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'API__LABEL' => [
                        'icon' => 'sitemap',
                        'permission' => 'MANAGE_API',
                        'route' => ['controller' => 'api', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'NOTIFICATION__TITLE' => [
                        'icon' => 'flag',
                        'permission' => 'MANAGE_NOTIFICATIONS',
                        'route' => ['controller' => 'notifications', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                    'HISTORY__VIEW_GLOBAL' => [
                        'icon' => 'table',
                        'permission' => 'VIEW_WEBSITE_HISTORY',
                        'route' => ['controller' => 'history', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                ],
            ],
            'GLOBAL__UPDATE' => [
                'icon' => 'wrench',
                'permission' => 'MANAGE_UPDATE',
                'route' => ['controller' => 'update', 'action' => 'index', 'admin' => true, 'plugin' => false],
            ],
        ];

        if (!function_exists('addToArrayAt')) {
            function addToArrayAt(array $where, int $index, array $array): array
            {
                return array_slice($where, 0, $index, true)
                    + $array
                    + array_slice($where, $index, count($where) - $index, true);
            }
        }

        if (!function_exists('addToNav')) {
            function addToNav(array $menus, array $nav, int $index = 0): array
            {
                foreach ($menus as $name => $menu) {
                    if (isset($nav[$name])) {
                        $nav[$name] = addToNav($menu, $nav[$name], $index + 1);
                    } else {
                        if (!isset($nav['menu']) && $index !== 0) {
                            $nav['menu'] = [];
                        }

                        if ($index === 0) {
                            $nav = addToArrayAt($nav, $menu['index'] ?? count($nav), [$name => $menu]);
                        } else {
                            $nav['menu'] = addToArrayAt($nav['menu'], $menu['index'] ?? count($nav['menu']), [$name => $menu]);
                        }
                    }
                }

                return $nav;
            }
        }

        $themeConfig = $this->Theme->getConfig(Configure::read('theme'));
        if (isset($themeConfig->slider) && $themeConfig->slider) {
            $nav['GLOBAL__CUSTOMIZE']['menu'] = addToArrayAt(
                $nav['GLOBAL__CUSTOMIZE']['menu'],
                count($nav['GLOBAL__CUSTOMIZE']['menu']),
                [
                    'SLIDER__TITLE' => [
                        'icon' => 'far fa-image',
                        'permission' => 'MANAGE_SLIDER',
                        'route' => ['controller' => 'slider', 'action' => 'index', 'admin' => true, 'plugin' => false],
                    ],
                ]
            );
        }

        $nav['GLOBAL__CUSTOMIZE']['menu'] = addToArrayAt(
            $nav['GLOBAL__CUSTOMIZE']['menu'],
            count($nav['GLOBAL__CUSTOMIZE']['menu']),
            [
                'SLIDER__TITLE' => [
                    'icon' => 'far fa-image',
                    'permission' => 'MANAGE_SLIDER',
                    'route' => ['controller' => 'slider', 'action' => 'index', 'admin' => true, 'plugin' => false],
                ],
            ]
        );

        $plugins = $this->EyPlugin->pluginsLoaded;
        foreach ($plugins as $plugin) {
            if (!isset($plugin->admin_menus) || !$plugin->active) {
                continue;
            }

            $menus = json_decode(json_encode($plugin->admin_menus), true);
            $nav = addToNav($menus, $nav);
        }

        $this->set('adminNavbar', $nav);
    }

    public function __initNavbar(): void
    {
        $this->Navbar = $this->fetchTable('Navbar');
        $nav = $this->Navbar->find()->orderBy(['Navbar.order_by' => 'ASC'])->toArray();
        if (empty($nav)) {
            $this->set('nav', false);
            return;
        }

        $this->Page = $this->fetchTable('Page');
        $pages = $this->Page->find('all', fields: ['id', 'slug'])->all();

        $pages_listed = [];
        foreach ($pages as $value) {
            $pages_listed[$value['id']] = $value['slug'];
        }

        foreach ($nav as $key => $value) {
            if (!isset($value['urlData']['type'])) {
                continue;
            }

            if ($value['urlData']['type'] === 'plugin') {
                if (isset($value['urlData']['route'])) {
                    $plugin = $this->EyPlugin->findPlugin('slug', $value['urlData']['id']);
                } else {
                    $plugin = $this->EyPlugin->findPlugin('DBid', $value['urlData']['id']);
                }

                if (is_object($plugin)) {
                    $nav[$key]['url'] = isset($value['urlData']['route'])
                        ? Router::url($value['urlData']['route'])
                        : Router::url('/' . strtolower($plugin->slug));
                } else {
                    $nav[$key]['url'] = '#';
                }
            } elseif ($value['urlData']['type'] === 'page') {
                if (isset($pages_listed[$value['urlData']['id']])) {
                    $nav[$key]['url'] = Router::url('/p/' . $pages_listed[$value['urlData']['id']]);
                } else {
                    $nav[$key]['url'] = '#';
                }
            } elseif ($value['urlData']['type'] === 'custom') {
                $nav[$key]['url'] = $value['urlData']['url'];
            }
        }

        $this->set(compact('nav'));
    }

    public function __initServerInfos(): void
    {
        $raw = $this->Configuration->getKey('banner_server');

        $configuration = [];
        if (is_string($raw) && $raw !== '') {
            $decoded = @unserialize($raw);
            if (is_array($decoded)) {
                $configuration = $decoded;
            }
        }

        if (!isset($this->Server)) {
            $this->set([
                'banner_server' => false,
                'server_infos' => false,
            ]);
            return;
        }

        try {
            if (!empty($configuration)) {
                $server_infos = $this->Server->banner_infos($configuration);
            } else {
                $server_infos = $this->Server->banner_infos();
            }
        } catch (\Throwable $e) {
            $this->set([
                'banner_server' => false,
                'server_infos' => false,
            ]);
            return;
        }

        if (
            !isset($server_infos['GET_MAX_PLAYERS'])
            || !isset($server_infos['GET_PLAYER_COUNT'])
            || $server_infos['GET_MAX_PLAYERS'] === 0
        ) {
            $this->set([
                'banner_server' => false,
                'server_infos' => $server_infos,
            ]);
            return;
        }

        $this->set([
            'banner_server' => __('SERVER__STATUS_MESSAGE', [
                '{MOTD}' => $server_infos['getMOTD'] ?? null,
                '{VERSION}' => $server_infos['getVersion'] ?? null,
                '{ONLINE}' => $server_infos['GET_PLAYER_COUNT'] ?? null,
                '{ONLINE_LIMIT}' => $server_infos['GET_MAX_PLAYERS'] ?? null,
            ]),
            'server_infos' => $server_infos,
        ]);
    }

    public function beforeRender(EventInterface $event): Response|null
    {
        $response = parent::beforeRender($event);
        if ($response instanceof Response) {
            return $response;
        }

        $this->__initSeoConfiguration();

        $pageEvent = new Event('onLoadPage', $this, $this->request->getData());
        $this->getEventManager()->dispatch($pageEvent);
        if ($pageEvent->isStopped()) {
            $this->__setTheme();
            return $pageEvent->getResult();
        }

        if ($this->getRequest()->getParam('prefix') === 'admin') {
            $adminEvent = new Event('onLoadAdminPanel', $this, $this->request->getData());
            $this->getEventManager()->dispatch($adminEvent);
            if ($adminEvent->isStopped()) {
                $this->__setTheme();
                return $adminEvent->getResult();
            }
        }

        $this->__setTheme();

        return null;
    }

    public function __initSeoConfiguration(): void
    {
        if (!Configure::read('Install.dbConfigured')) {
            return;
        }

        $this->Seo = $this->fetchTable('Seo');
        $default = $this->Seo->find('all', conditions: ['Seo.page IS NULL'])->first();

        $current_url = $this->getRequest()->getRequestTarget();
        $get_page = [];
        $condition = ["'" . $current_url . "' LIKE CONCAT(page, '%')"];

        $use_sqlite = $this->Util->useSqlite();
        if ($use_sqlite) {
            $condition = ["'" . $current_url . "' LIKE 'page' || '%' "];
        }

        $check = $this->Seo->find('all', conditions: $condition)->toArray();
        if ($check && ($check = max($check)) && ($check['page'] === $current_url || $current_url !== '/')) {
            $get_page = $check;
        }

        $seo_config = [];
        $seo_config['title'] = !empty($default['title']) ? $default['title'] : '{TITLE} - {WEBSITE_NAME}';
        $seo_config['title'] = !empty($get_page['title']) ? $get_page['title'] : $seo_config['title'];
        $seo_config['description'] = !empty($get_page['description'])
            ? $get_page['description']
            : (!empty($default['description']) ? $default['description'] : '');
        $seo_config['img_url'] = !empty($get_page['img_url'])
            ? $get_page['img_url']
            : (!empty($default['img_url']) ? $default['img_url'] : '');
        $seo_config['favicon_url'] = !empty($get_page['favicon_url'])
            ? $get_page['favicon_url']
            : (!empty($default['favicon_url']) ? $default['favicon_url'] : '');
        $seo_config['favicon_url'] = Router::url($seo_config['favicon_url'], true);
        $seo_config['img_url'] = empty($seo_config['img_url'])
            ? $seo_config['favicon_url']
            : Router::url($seo_config['img_url'], true);

        $title_for_layout = $this->viewBuilder()->getVar('title_for_layout') ?: __('GLOBAL__ERROR');
        $website_name = $this->viewBuilder()->getVar('website_name') ?: 'MineWeb';

        $seo_config['title'] = str_replace(
            ['{TITLE}', '{WEBSITE_NAME}'],
            [$title_for_layout, $website_name],
            $seo_config['title']
        );

        $seo_config['theme_color'] = !empty($get_page['theme_color'])
            ? $get_page['theme_color']
            : (!empty($default['theme_color']) ? $default['theme_color'] : '');
        $seo_config['twitter_site'] = !empty($get_page['twitter_site'])
            ? $get_page['twitter_site']
            : (!empty($default['twitter_site']) ? $default['twitter_site'] : '');

        $this->set(compact('seo_config'));
    }

    public function afterFilter(EventInterface $event): Response|null
    {
        $response = parent::afterFilter($event);
        if ($response instanceof Response) {
            return $response;
        }

        $afterEvent = new Event('beforePageDisplay', $this, $this->request->getData());
        $this->getEventManager()->dispatch($afterEvent);
        if ($afterEvent->isStopped()) {
            $this->__setTheme();
            return $afterEvent->getResult();
        }

        return null;
    }

    public function sendGetRequest(string $url): string
    {
        $ch = curl_init();
        if ($ch === false) {
            return '';
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: MineWebCMS']);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

        $result = curl_exec($ch);
        curl_close($ch);

        return $result !== false ? $result : '';
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
        $result = [];

        foreach ($urls as $url) {
            $ch = curl_init();
            if ($ch === false) {
                continue;
            }

            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_HTTPHEADER, ['User-Agent: MineWebCMS']);
            curl_setopt($ch, CURLOPT_HEADER, false);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);

            curl_multi_add_handle($multi, $ch);

            $channels[(string)$url] = $ch;
        }

        $active = null;
        do {
            $mrc = curl_multi_exec($multi, $active);
        } while ($mrc === CURLM_CALL_MULTI_PERFORM);

        while ($active && $mrc === CURLM_OK) {
            if (curl_multi_select($multi) === -1) {
                continue;
            }

            do {
                $mrc = curl_multi_exec($multi, $active);
            } while ($mrc === CURLM_CALL_MULTI_PERFORM);
        }

        foreach ($channels as $ch) {
            $content = curl_multi_getcontent($ch);
            $result[] = $content !== false ? $content : '';
            curl_multi_remove_handle($multi, $ch);
        }

        curl_multi_close($multi);

        return $result;
    }

    public function isIPBan(string $ip): bool
    {
        $this->Ban = $this->fetchTable('Ban');
        $ipIsBan = $this->Ban->find('all', conditions: ['ip' => $ip])->first();

        if ($ipIsBan !== null) {
            $this->isBanned = $ipIsBan['reason'];

            return true;
        }

        return false;
    }
}
