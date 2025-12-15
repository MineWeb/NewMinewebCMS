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
            'repoPattern' => (string)env('UPDATE_ADDONS_REPO_PATTERN', 'MineWeb/Plugin-%s'),
            'channel' => (string)env('UPDATE_ADDONS_CHANNEL', 'release'),
            'cacheEnabled' => (bool)env('UPDATE_ADDONS_CACHE_ENABLED', true),
            'cacheTtl' => (string)env('UPDATE_ADDONS_CACHE_TTL', '+10 minutes'),
            'markets' => [
                [
                    'source' => (string)env('UPDATE_ADDONS_MARKET_1', 'https://raw.githubusercontent.com/MineWeb/mineweb.org/gh-pages/market/plugins.json'),
                    'repoPattern' => (string)env('UPDATE_ADDONS_MARKET_1_REPO_PATTERN', 'MineWeb/Plugin-%s'),
                    'channel' => (string)env('UPDATE_ADDONS_MARKET_1_CHANNEL', 'release'),
                    'branch' => (string)env('UPDATE_ADDONS_MARKET_1_BRANCH', '2.X'),
                ],
                [
                    'source' => (string)env('UPDATE_ADDONS_MARKET_2', ''),
                    'repoPattern' => (string)env('UPDATE_ADDONS_MARKET_2_REPO_PATTERN', 'MineWeb/Plugin-%s'),
                    'channel' => (string)env('UPDATE_ADDONS_MARKET_2_CHANNEL', 'release'),
                    'branch' => (string)env('UPDATE_ADDONS_MARKET_2_BRANCH', '2.X'),
                ],
                [
                    'source' => (string)env('UPDATE_ADDONS_MARKET_LOCAL', ''),
                    'repoPattern' => (string)env('UPDATE_ADDONS_MARKET_LOCAL_REPO_PATTERN', 'MineWeb/Plugin-%s'),
                    'channel' => (string)env('UPDATE_ADDONS_MARKET_LOCAL_CHANNEL', 'branch'),
                    'branch' => (string)env('UPDATE_ADDONS_MARKET_LOCAL_BRANCH', '2.X'),
                ],
            ],
        ],
        'themes' => [
            'branch' => (string)env('UPDATE_THEMES_BRANCH', '2.X'),
            'manifest' => (string)env('UPDATE_THEMES_MANIFEST', 'manifest.json'),
            'folder' => (string)env('UPDATE_THEMES_FOLDER', ROOT . DS . 'plugins' . DS . 'Themes'),
            'repoPattern' => (string)env('UPDATE_THEMES_REPO_PATTERN', 'MineWeb/Theme-%s'),
            'channel' => (string)env('UPDATE_THEMES_CHANNEL', 'release'),
            'cacheEnabled' => (bool)env('UPDATE_THEMES_CACHE_ENABLED', true),
            'cacheTtl' => (string)env('UPDATE_THEMES_CACHE_TTL', '+10 minutes'),
            'markets' => [
                [
                    'source' => (string)env('UPDATE_THEMES_MARKET_1', 'https://raw.githubusercontent.com/MineWeb/mineweb.org/gh-pages/market/themes.json'),
                    'repoPattern' => (string)env('UPDATE_THEMES_MARKET_1_REPO_PATTERN', 'MineWeb/Theme-%s'),
                    'channel' => (string)env('UPDATE_THEMES_MARKET_1_CHANNEL', 'release'),
                    'branch' => (string)env('UPDATE_THEMES_MARKET_1_BRANCH', '2.X'),
                ],
                [
                    'source' => (string)env('UPDATE_THEMES_MARKET_2', ''),
                    'repoPattern' => (string)env('UPDATE_THEMES_MARKET_2_REPO_PATTERN', 'MineWeb/Theme-%s'),
                    'channel' => (string)env('UPDATE_THEMES_MARKET_2_CHANNEL', 'release'),
                    'branch' => (string)env('UPDATE_THEMES_MARKET_2_BRANCH', '2.X'),
                ],
                [
                    'source' => (string)env('UPDATE_THEMES_MARKET_LOCAL', ''),
                    'repoPattern' => (string)env('UPDATE_THEMES_MARKET_LOCAL_REPO_PATTERN', 'MineWeb/Theme-%s'),
                    'channel' => (string)env('UPDATE_THEMES_MARKET_LOCAL_CHANNEL', 'branch'),
                    'branch' => (string)env('UPDATE_THEMES_MARKET_LOCAL_BRANCH', '2.X'),
                ],
            ],
        ],
    ],
];
