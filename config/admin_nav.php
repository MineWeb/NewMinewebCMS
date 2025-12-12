<?php
declare(strict_types=1);

return [
    'Dashboard' => [
        'icon' => 'fas fa-tachometer-alt',
        'route' => ['_name' => 'admin_index'],
    ],
    'GLOBAL__ADMIN_GENERAL' => [
        'icon' => 'cogs',
        'menu' => [
            'USER__MEMBERS_REGISTERED' => [
                'icon' => 'users',
                'permission' => 'MANAGE_USERS',
                'route' => ['_name' => 'admin_user_index'],
            ],
            'BAN__MEMBERS' => [
                'icon' => 'ban',
                'permission' => 'MANAGE_BAN',
                'route' => ['_name' => 'admin_ban_index'],
            ],
            'PERMISSIONS__LABEL' => [
                'icon' => 'user',
                'permission' => 'MANAGE_PERMISSIONS',
                'route' => ['_name' => 'admin_permissions_index'],
            ],
            'CONFIG__GENERAL_PREFERENCES' => [
                'icon' => 'cog',
                'permission' => 'MANAGE_CONFIGURATION',
                'route' => ['_name' => 'admin_configuration_index'],
            ],
            'STATS__TITLE' => [
                'icon' => 'far fa-chart-bar',
                'permission' => 'VIEW_STATISTICS',
                'route' => ['_name' => 'admin_statistics_index'],
            ],
            'MAINTENANCE__TITLE' => [
                'icon' => 'fas fa-hand-paper',
                'permission' => 'MANAGE_MAINTENANCE',
                'route' => ['_name' => 'admin_maintenance_index'],
            ],
        ],
    ],
    'GLOBAL__CUSTOMIZE' => [
        'icon' => 'fas fa-copy',
        'menu' => [
            'NEWS__TITLE' => [
                'icon' => 'fas fa-pencil-ruler',
                'permission' => 'MANAGE_NEWS',
                'route' => ['_name' => 'admin_news_index'],
            ],
            'PAGE__TITLE' => [
                'icon' => 'fas fa-file-alt',
                'permission' => 'MANAGE_PAGE',
                'route' => ['_name' => 'admin_pages_index'],
            ],
            'NAVBAR__TITLE' => [
                'icon' => 'fas fa-bars',
                'permission' => 'MANAGE_NAV',
                'route' => ['_name' => 'admin_navbar_index'],
            ],
            'SEO__TITLE' => [
                'icon' => 'fab fa-google',
                'permission' => 'MANAGE_SEO',
                'route' => ['_name' => 'admin_seo_index'],
            ],
            'SOCIAL__TITLE' => [
                'icon' => 'fas fa-share-alt',
                'permission' => 'MANAGE_SOCIAL',
                'route' => ['_name' => 'admin_social_index'],
            ],
            'MOTD__TITLE' => [
                'icon' => 'fas fa-sort-amount-up-alt',
                'permission' => 'MANAGE_MOTD',
                'route' => ['_name' => 'admin_motd_index'],
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
                'route' => ['_name' => 'admin_server_link'],
            ],
            'SERVER__BANLIST' => [
                'icon' => 'ban',
                'permission' => 'MANAGE_SERVERS',
                'route' => ['_name' => 'admin_server_banlist'],
            ],
            'SERVER__WHITELIST' => [
                'icon' => 'list',
                'permission' => 'MANAGE_SERVERS',
                'route' => ['_name' => 'admin_server_whitelist'],
            ],
            'SERVER__ONLINE_PLAYERS' => [
                'icon' => 'list-ul',
                'permission' => 'MANAGE_SERVERS',
                'route' => ['_name' => 'admin_server_online'],
            ],
            'SERVER__CMD' => [
                'icon' => 'key',
                'permission' => 'MANAGE_SERVERS',
                'route' => ['_name' => 'admin_server_cmd'],
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
                'route' => ['_name' => 'admin_log_error'],
            ],
            'LOG__VIEW_DEBUG' => [
                'icon' => 'exclamation-triangle',
                'permission' => 'VIEW_WEBSITE_LOGS',
                'route' => ['_name' => 'admin_log_debug'],
            ],
        ],
    ],
    'GLOBAL__ADMIN_OTHER_TITLE' => [
        'icon' => 'fas fa-folder-open',
        'menu' => [
            'PLUGIN__TITLE' => [
                'icon' => 'plus',
                'permission' => 'MANAGE_PLUGINS',
                'route' => ['_name' => 'admin_plugin_index'],
            ],
            'THEME__TITLE' => [
                'icon' => 'mobile',
                'permission' => 'MANAGE_THEMES',
                'route' => ['_name' => 'admin_theme_index'],
            ],
            'API__LABEL' => [
                'icon' => 'sitemap',
                'permission' => 'MANAGE_API',
                'route' => ['_name' => 'admin_api_index'],
            ],
            'NOTIFICATION__TITLE' => [
                'icon' => 'flag',
                'permission' => 'MANAGE_NOTIFICATIONS',
                'route' => ['_name' => 'admin_notifications_index'],
            ],
            'HISTORY__VIEW_GLOBAL' => [
                'icon' => 'table',
                'permission' => 'VIEW_WEBSITE_HISTORY',
                'route' => ['_name' => 'admin_history_index'],
            ],
        ],
    ],
    'GLOBAL__UPDATE' => [
        'icon' => 'wrench',
        'permission' => 'MANAGE_UPDATE',
        'route' => ['_name' => 'admin_update_index'],
    ],
];
