<?php
declare(strict_types=1);

return [
    'github' => [
        'tokenEnv' => '',
        'userAgent' => 'MineWebCMS',
    ],
    'cms' => [
        'repository' => 'MineWeb/MineWebCMS',
        'asset' => 'minewebcms-dist.zip',
        'cacheTtl' => '+6 hours',
        'preserve' => [
            'config/.env',
            'config/app_local.php',
            'config/databases.json',
            'config/install.lock',
            'tmp',
            'logs',
            'webroot/img/uploads',
            'webroot/files',
        ],
    ],
    'addons' => [
        'branch' => '2.X',
        'manifest' => 'manifest.json',
        'folder' => ROOT . DS . 'plugins' . DS . 'Addons',
        'market' => 'https://raw.githubusercontent.com/MineWeb/mineweb.org/gh-pages/market/plugins.json',
        'repoPattern' => 'MineWeb/Plugin-%s',
    ],
    'themes' => [
        'branch' => '2.X',
        'manifest' => 'manifest.json',
        'folder' => ROOT . DS . 'plugins' . DS . 'Themes',
        'market' => 'https://raw.githubusercontent.com/MineWeb/mineweb.org/gh-pages/market/themes.json',
        'repoPattern' => 'MineWeb/Theme-%s',
    ],
];
