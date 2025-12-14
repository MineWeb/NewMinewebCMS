<?php
declare(strict_types=1);

return [
    'Update' => [
        'github' => [
            'tokenEnv' => (string)env('UPDATE_GITHUB_TOKEN_ENV', ''),
            'userAgent' => (string)env('UPDATE_GITHUB_USER_AGENT', 'MineWebCMS'),
        ],
        'cms' => [
            'repository' => (string)env('UPDATE_CMS_REPOSITORY', 'MineWeb/MineWebCMS'),
            'asset' => (string)env('UPDATE_CMS_ASSET', 'minewebcms-dist.zip'),
            'cacheTtl' => (string)env('UPDATE_CMS_CACHE_TTL', '+2 hours'),
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
            'branch' => (string)env('UPDATE_ADDONS_BRANCH', '2.X'),
            'manifest' => (string)env('UPDATE_ADDONS_MANIFEST', 'manifest.json'),
            'folder' => (string)env('UPDATE_ADDONS_FOLDER', ROOT . DS . 'plugins' . DS . 'Addons'),
            'market' => [
                (string)env('UPDATE_ADDONS_MARKET', 'https://raw.githubusercontent.com/MineWeb/mineweb.org/gh-pages/market/plugins.json'),
                (string)env('UPDATE_ADDONS_MARKET_2', ''),
                (string)env('UPDATE_ADDONS_MARKET_3', ''),
                (string)env('UPDATE_ADDONS_MARKET_4', ''),
                (string)env('UPDATE_ADDONS_MARKET_LOCAL', ''),
            ],
            'repoPattern' => (string)env('UPDATE_ADDONS_REPO_PATTERN', 'MineWeb/Plugin-%s'),
            'channel' => (string)env('UPDATE_ADDONS_CHANNEL', 'release'),
            'cacheEnabled' => (bool)env('UPDATE_ADDONS_CACHE_ENABLED', true),
            'cacheTtl' => (string)env('UPDATE_ADDONS_CACHE_TTL', '+10 minutes'),
        ],
        'themes' => [
            'branch' => (string)env('UPDATE_THEMES_BRANCH', '2.X'),
            'manifest' => (string)env('UPDATE_THEMES_MANIFEST', 'manifest.json'),
            'folder' => (string)env('UPDATE_THEMES_FOLDER', ROOT . DS . 'plugins' . DS . 'Themes'),
            'market' => [
                (string)env('UPDATE_THEMES_MARKET', 'https://raw.githubusercontent.com/MineWeb/mineweb.org/gh-pages/market/themes.json'),
                (string)env('UPDATE_THEMES_MARKET_2', ''),
                (string)env('UPDATE_THEMES_MARKET_3', ''),
                (string)env('UPDATE_THEMES_MARKET_4', ''),
                (string)env('UPDATE_THEMES_MARKET_LOCAL', ''),
            ],
            'repoPattern' => (string)env('UPDATE_THEMES_REPO_PATTERN', 'MineWeb/Theme-%s'),
            'channel' => (string)env('UPDATE_THEMES_CHANNEL', 'release'),
            'cacheEnabled' => (bool)env('UPDATE_THEMES_CACHE_ENABLED', true),
            'cacheTtl' => (string)env('UPDATE_THEMES_CACHE_TTL', '+10 minutes'),
        ],
    ],
];
