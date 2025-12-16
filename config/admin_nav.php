<?php
declare(strict_types=1);

return [
    'Dashboard' => [
        'icon' => 'fas fa-tachometer-alt',
        'route' => ['_name' => 'admin_index'],
    ],

    'GLOBAL__ADMIN_USERS' => [
        'icon' => 'fas fa-users-cog',
        'menu' => [
            'USER__MEMBERS_REGISTERED' => [
                'icon' => 'fas fa-users',
                'permission' => 'MANAGE_USERS',
                'route' => ['_name' => 'admin_user_index'],
            ],
            'BAN__MEMBERS' => [
                'icon' => 'fas fa-user-slash',
                'permission' => 'MANAGE_BAN',
                'route' => ['_name' => 'admin_ban_index'],
            ],
            'PERMISSIONS__LABEL' => [
                'icon' => 'fas fa-user-shield',
                'permission' => 'MANAGE_PERMISSIONS',
                'route' => ['_name' => 'admin_roles_index'],
            ],
        ],
    ],

    'GLOBAL__ADMIN_SETTINGS' => [
        'icon' => 'fas fa-sliders-h',
        'menu' => [
            'CONFIG__GENERAL_PREFERENCES' => [
                'icon' => 'fas fa-cog',
                'permission' => 'MANAGE_CONFIGURATION',
                'route' => ['_name' => 'admin_configuration_index'],
            ],
            'MAINTENANCE__TITLE' => [
                'icon' => 'fas fa-tools',
                'permission' => 'MANAGE_MAINTENANCE',
                'route' => ['_name' => 'admin_maintenance_index'],
            ],
            'API__LABEL' => [
                'icon' => 'fas fa-sitemap',
                'permission' => 'MANAGE_API',
                'route' => ['_name' => 'admin_api_index'],
            ],
        ],
    ],

    'GLOBAL__ADMIN_CONTENT' => [
        'icon' => 'fas fa-edit',
        'menu' => [
            'NEWS__TITLE' => [
                'icon' => 'fas fa-newspaper',
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
            'NOTIFICATION__TITLE' => [
                'icon' => 'fas fa-bell',
                'permission' => 'MANAGE_NOTIFICATIONS',
                'route' => ['_name' => 'admin_notifications_index'],
            ],
        ],
    ],

    'SERVER__TITLE' => [
        'icon' => 'fas fa-server',
        'permission' => 'MANAGE_SERVERS',
        'menu' => [
            'SERVER__LINK' => [
                'icon' => 'fas fa-link',
                'permission' => 'MANAGE_SERVERS',
                'route' => ['_name' => 'admin_server_link'],
            ],
            'SERVER__ONLINE_PLAYERS' => [
                'icon' => 'fas fa-users',
                'permission' => 'MANAGE_SERVERS',
                'route' => ['_name' => 'admin_server_online'],
            ],
            'SERVER__WHITELIST' => [
                'icon' => 'fas fa-user-check',
                'permission' => 'MANAGE_SERVERS',
                'route' => ['_name' => 'admin_server_whitelist'],
            ],
            'SERVER__BANLIST' => [
                'icon' => 'fas fa-ban',
                'permission' => 'MANAGE_SERVERS',
                'route' => ['_name' => 'admin_server_banlist'],
            ],
            'SERVER__CMD' => [
                'icon' => 'fas fa-terminal',
                'permission' => 'MANAGE_SERVERS',
                'route' => ['_name' => 'admin_server_cmd'],
            ],
        ],
    ],

    'GLOBAL__ADMIN_EXTENSIONS' => [
        'icon' => 'fas fa-puzzle-piece',
        'menu' => [
            'PLUGIN__TITLE' => [
                'icon' => 'fas fa-plug',
                'permission' => 'MANAGE_PLUGINS',
                'route' => ['_name' => 'admin_plugin_index'],
            ],
            'THEME__TITLE' => [
                'icon' => 'fas fa-palette',
                'permission' => 'MANAGE_THEMES',
                'route' => ['_name' => 'admin_theme_index'],
            ],
        ],
    ],

    'GLOBAL__ADMIN_SYSTEM' => [
        'icon' => 'fas fa-shield-alt',
        'menu' => [
            'STATS__TITLE' => [
                'icon' => 'far fa-chart-bar',
                'permission' => 'VIEW_STATISTICS',
                'route' => ['_name' => 'admin_statistics_index'],
            ],
            'GLOBAL__ADMIN_LOGS_TITLE' => [
                'icon' => 'fas fa-scroll',
                'menu' => [
                    'LOG__VIEW_ERROR' => [
                        'icon' => 'fas fa-times-circle',
                        'permission' => 'VIEW_WEBSITE_LOGS',
                        'route' => ['_name' => 'admin_log_error'],
                    ],
                    'LOG__VIEW_DEBUG' => [
                        'icon' => 'fas fa-exclamation-triangle',
                        'permission' => 'VIEW_WEBSITE_LOGS',
                        'route' => ['_name' => 'admin_log_debug'],
                    ],
                ],
            ],
            'HISTORY__VIEW_GLOBAL' => [
                'icon' => 'fas fa-history',
                'permission' => 'VIEW_WEBSITE_HISTORY',
                'route' => ['_name' => 'admin_history_index'],
            ],
        ],
    ],
];
