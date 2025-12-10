<?php

use App\Service\InstallState;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->setRouteClass(DashedRoute::class);

    $installed = InstallState::isInstalled();

    if ($installed) {
        $routes->scope('/', function (RouteBuilder $builder) {
            $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home']);

            $builder->connect('/robots.txt', ['controller' => 'Pages', 'action' => 'robots']);

            $builder->connect('/pages/*', ['controller' => 'Pages', 'action' => 'display']);

            $builder->connect('/blog', ['controller' => 'News', 'action' => 'blog']);
            $builder->connect('/blog/*', ['controller' => 'News', 'action' => 'index']);
            $builder->connect('/blog/', ['controller' => 'News', 'action' => 'blog']);

            $builder->connect('/p/*', ['controller' => 'Pages', 'action' => 'index']);

            $builder->connect('/maintenance/**', ['controller' => 'Maintenance', 'action' => 'index']);

            $builder->connect('/profile', ['controller' => 'User', 'action' => 'profile']);

            $builder->connect('/profile/modify', ['controller' => 'User', 'action' => 'modify_profile']);

            $builder->connect('/api/:action', ['controller' => 'API']);

            $builder->fallbacks();
        });

        $routes->prefix('Admin', function (RouteBuilder $adminBuilder) {
            $adminBuilder->connect('/', ['controller' => 'Admin', 'action' => 'index']);
            $adminBuilder->fallbacks();
        });

        $routes->connect('/theme', ['controller' => 'Pages', 'action' => 'themeAsset']);
    } else {
        $routes->scope('/', function (RouteBuilder $builder) {
            $builder->connect('/', ['controller' => 'Install', 'action' => 'index']);

            $builder->connect('/install', ['controller' => 'Install', 'action' => 'index']);
            $builder->connect('/install/database', ['controller' => 'Install', 'action' => 'database']);
            $builder->connect('/install/install', ['controller' => 'Install', 'action' => 'install']);
            $builder->connect('/install/user', ['controller' => 'Install', 'action' => 'user']);

            $builder->connect('/*', ['controller' => 'Install', 'action' => 'database']);

            $builder->fallbacks();
        });
    }
};
