<?php
/**
 * Routes configuration.
 *
 * In this file, you set up routes to your controllers and their actions.
 * Routes are very important mechanism that allows you to freely connect
 * different URLs to chosen controllers and their actions (functions).
 *
 * It's loaded within the context of `Application::routes()` method which
 * receives a `RouteBuilder` instance `$routes` as method argument.
 *
 * CakePHP(tm) : Rapid Development Framework (https://cakephp.org)
 * Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 *
 * Licensed under The MIT License
 * For full copyright and license information, please see the LICENSE.txt
 * Redistributions of files must retain the above copyright notice.
 *
 * @copyright     Copyright (c) Cake Software Foundation, Inc. (https://cakefoundation.org)
 * @link          https://cakephp.org CakePHP(tm) Project
 * @license       https://opensource.org/licenses/mit-license.php MIT License
 */

use Cake\Core\Configure;
use Cake\Routing\Route\DashedRoute;
use Cake\Routing\Route\InflectedRoute;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes) {
    $routes->setRouteClass(DashedRoute::class);

    $routes->scope('/', function (RouteBuilder $builder) {
        if (file_exists(ROOT . '/config/installed.txt') and file_exists(ROOT . '/config/install.txt')) {
            $builder->connect('/', ['controller' => 'pages', 'action' => 'display', 'home']);

            $builder->connect('/robots.txt', ['controller' => 'pages', 'action' => 'robots']);

            $builder->connect('/pages/*', ['controller' => 'pages', 'action' => 'display']);

            $builder->connect('/blog', ['controller' => 'news', 'action' => 'blog']);
            $builder->connect('/blog/*', ['controller' => 'news', 'action' => 'index']);
            $builder->connect('/blog/', ['controller' => 'news', 'action' => 'blog']);

            $builder->connect('/p/*', ['controller' => 'pages', 'action' => 'index']);

            $builder->connect('/maintenance/**', ['controller' => 'maintenance', 'action' => 'index']);

            $builder->connect('/profile', ['controller' => 'user', 'action' => 'profile']);

            $builder->connect('/profile/modify', ['controller' => 'user', 'action' => 'modify_profile']);

            $builder->connect('/api/:action', ['controller' => 'API']);
        } else {
            $builder->connect('/', ['controller' => 'install', 'action' => 'index']);
        }

        $builder->fallbacks();
    });

    // Admin
    $routes->prefix('Admin', function(RouteBuilder $adminBuilder) {
        $adminBuilder->connect('/', ['controller' => 'admin', 'action' => 'index'])
            ->setPatterns(['admin' => true]);

        $adminBuilder->fallbacks();
    });

    // Themes assets
    $routes->connect('/theme', ['controller' => 'pages', 'action' => 'themeAsset']);
};
