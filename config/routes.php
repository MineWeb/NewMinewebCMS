<?php
declare(strict_types=1);

use App\Middleware\RequireAdminMiddleware;
use App\Middleware\RequireAuthMiddleware;
use App\Middleware\RequireGuestMiddleware;
use App\Service\InstallState;
use Cake\Routing\RouteBuilder;

return static function (RouteBuilder $routes): void {
    $routes->registerMiddleware('auth', new RequireAuthMiddleware());
    $routes->registerMiddleware('guest', new RequireGuestMiddleware());
    $routes->registerMiddleware('admin', new RequireAdminMiddleware());

    $installed = InstallState::isInstalled();

    if (!$installed) {
        $routes->scope('/', function (RouteBuilder $builder): void {
            $builder->connect('/', ['controller' => 'Install', 'action' => 'index'], ['_name' => 'install_index']);
            $builder->connect('/install', ['controller' => 'Install', 'action' => 'index'], ['_name' => 'install_index_alias']);
            $builder->connect('/install/database', ['controller' => 'Install', 'action' => 'database'], ['_name' => 'install_database']);
            $builder->connect('/install/install', ['controller' => 'Install', 'action' => 'install'], ['_name' => 'install_run']);
            $builder->connect('/install/user', ['controller' => 'Install', 'action' => 'user'], ['_name' => 'install_user']);
            $builder->connect('/*', ['controller' => 'Install', 'action' => 'database'], ['_name' => 'install_catch_all']);
        });

        return;
    }

    // Public
    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->connect('/', ['controller' => 'Pages', 'action' => 'display', 'home'], ['_name' => 'home']);
        $builder->connect('/robots.txt', ['controller' => 'Pages', 'action' => 'robots'], ['_name' => 'robots']);

        $builder->scope('/api', function (RouteBuilder $b): void {
            $b->connect('/get-head-skin/*', ['controller' => 'API', 'action' => 'getHeadSkin'], ['_name' => 'api_get_head_skin']);
            $b->connect('/get-skin/*', ['controller' => 'API', 'action' => 'getSkin'], ['_name' => 'api_get_skin']);
            $b->connect('/launcher/*', ['controller' => 'API', 'action' => 'launcher'], ['_name' => 'api_launcher']);
        });

        $builder->scope('/ban', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Ban', 'action' => 'index'], ['_name' => 'ban_index']);
            $b->connect('/ip', ['controller' => 'Ban', 'action' => 'ip'], ['_name' => 'ban_ip']);
        });

        $builder->scope('/maintenance', function (RouteBuilder $b): void {
            $b->connect('/*', ['controller' => 'Maintenance', 'action' => 'index'], ['_name' => 'maintenance_index']);
        });

        $builder->scope('/news', function (RouteBuilder $b): void {
            $b->connect('/api', ['controller' => 'News', 'action' => 'api'], ['_name' => 'news_api']);
            $b->connect('/blog', ['controller' => 'News', 'action' => 'blog'], ['_name' => 'news_blog']);
            $b->connect('/*', ['controller' => 'News', 'action' => 'index'], ['_name' => 'news_index']);
        });

        $builder->connect('/blog', ['controller' => 'News', 'action' => 'blog'], ['_name' => 'blog_index']);
        $builder->connect('/blog/*', ['controller' => 'News', 'action' => 'index'], ['_name' => 'blog_view']);

        $builder->scope('/pages', function (RouteBuilder $b): void {
            $b->connect('/display/*', ['controller' => 'Pages', 'action' => 'display'], ['_name' => 'pages_display']);
            $b->connect('/robots', ['controller' => 'Pages', 'action' => 'robots'], ['_name' => 'pages_robots']);
            $b->connect('/theme-asset/*', ['controller' => 'Pages', 'action' => 'themeAsset'], ['_name' => 'pages_theme_asset']);
            $b->connect('/*', ['controller' => 'Pages', 'action' => 'index'], ['_name' => 'pages_index']);
        });
    });

    // Guest only
    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->applyMiddleware('guest');

        $builder->scope('/auth', function (RouteBuilder $b): void {
            $b->connect('/login', ['controller' => 'Auth', 'action' => 'login'], ['_name' => 'auth_login']);
            $b->connect('/register', ['controller' => 'Auth', 'action' => 'register'], ['_name' => 'auth_register']);
            $b->connect('/lost-password', ['controller' => 'Auth', 'action' => 'lostPassword'], ['_name' => 'auth_lost_password']);
            $b->connect('/reset-password', ['controller' => 'Auth', 'action' => 'resetPassword'], ['_name' => 'auth_reset_password']);
            $b->connect('/confirm/*', ['controller' => 'Auth', 'action' => 'confirm'], ['_name' => 'auth_confirm']);
            $b->connect('/resend-confirmation', ['controller' => 'Auth', 'action' => 'resendConfirmation'], ['_name' => 'auth_resend_confirmation']);
            $b->connect('/captcha', ['controller' => 'Auth', 'action' => 'getCaptcha'], ['_name' => 'auth_captcha']);
            $b->connect('/two-factor/validate', ['controller' => 'Auth', 'action' => 'twoFactorValidate'], ['_name' => 'auth_2fa_validate']);
        });
    });

    // Auth only
    $routes->scope('/', function (RouteBuilder $builder): void {
        $builder->applyMiddleware('auth');

        $builder->scope('/auth', function (RouteBuilder $b): void {
            $b->connect('/logout', ['controller' => 'Auth', 'action' => 'logout'], ['_name' => 'auth_logout']);
            $b->connect('/two-factor/disable', ['controller' => 'Auth', 'action' => 'twoFactorDisable'], ['_name' => 'auth_2fa_disable']);
            $b->connect('/two-factor/generate-secret', ['controller' => 'Auth', 'action' => 'twoFactorGenerateSecret'], ['_name' => 'auth_2fa_generate_secret']);
            $b->connect('/two-factor/enable', ['controller' => 'Auth', 'action' => 'twoFactorEnable'], ['_name' => 'auth_2fa_enable']);
        });

        $builder->scope('/user', function (RouteBuilder $b): void {
            $b->connect('/change-email', ['controller' => 'User', 'action' => 'changeEmail'], ['_name' => 'user_change_email']);
            $b->connect('/change-pw', ['controller' => 'User', 'action' => 'changePw'], ['_name' => 'user_change_pw']);
            $b->connect('/profile', ['controller' => 'User', 'action' => 'profile'], ['_name' => 'user_profile']);
            $b->connect('/upload-cape', ['controller' => 'User', 'action' => 'uploadCape'], ['_name' => 'user_upload_cape']);
            $b->connect('/upload-skin', ['controller' => 'User', 'action' => 'uploadSkin'], ['_name' => 'user_upload_skin']);
        });

        $builder->scope('/notifications', function (RouteBuilder $b): void {
            $b->connect('/clear/*', ['controller' => 'Notifications', 'action' => 'clear'], ['_name' => 'notifications_clear']);
            $b->connect('/clear-all', ['controller' => 'Notifications', 'action' => 'clearAll'], ['_name' => 'notifications_clear_all']);
            $b->connect('/get-all/*', ['controller' => 'Notifications', 'action' => 'getAll'], ['_name' => 'notifications_get_all']);
            $b->connect('/mark-all-as-seen', ['controller' => 'Notifications', 'action' => 'markAllAsSeen'], ['_name' => 'notifications_mark_all_as_seen']);
            $b->connect('/mark-as-seen/*', ['controller' => 'Notifications', 'action' => 'markAsSeen'], ['_name' => 'notifications_mark_as_seen']);
        });

        $builder->scope('/news', function (RouteBuilder $b): void {
            $b->connect('/add-comment', ['controller' => 'News', 'action' => 'addComment'], ['_name' => 'news_add_comment']);
            $b->connect('/ajax-comment-delete', ['controller' => 'News', 'action' => 'ajaxCommentDelete'], ['_name' => 'news_ajax_comment_delete']);
            $b->connect('/dislike', ['controller' => 'News', 'action' => 'dislike'], ['_name' => 'news_dislike']);
            $b->connect('/get-news', ['controller' => 'News', 'action' => 'getNews'], ['_name' => 'news_get_news']);
            $b->connect('/like', ['controller' => 'News', 'action' => 'like'], ['_name' => 'news_like']);
        });
    });

    // Admin only
    $routes->prefix('Admin', function (RouteBuilder $builder): void {
        $builder->applyMiddleware('admin');

        $builder->connect('/', ['controller' => 'Admin', 'action' => 'index'], ['_name' => 'admin_index']);
        $builder->connect('/switch-admin-dark-mode', ['controller' => 'Admin', 'action' => 'switchAdminDarkMode'], ['_name' => 'admin_switch_dark_mode']);

        $builder->connect('/api', ['controller' => 'API', 'action' => 'index'], ['_name' => 'admin_api_index']);

        $builder->scope('/ban', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Ban', 'action' => 'index'], ['_name' => 'admin_ban_index']);
            $b->connect('/add', ['controller' => 'Ban', 'action' => 'add'], ['_name' => 'admin_ban_add']);
            $b->connect('/get-users-not-ban', ['controller' => 'Ban', 'action' => 'getUsersNotBan'], ['_name' => 'admin_ban_get_users_not_ban']);
            $b->connect('/live-search/*', ['controller' => 'Ban', 'action' => 'liveSearch'], ['_name' => 'admin_ban_live_search']);
            $b->connect('/unban/*', ['controller' => 'Ban', 'action' => 'unban'], ['_name' => 'admin_ban_unban']);
        });

        $builder->scope('/configuration', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Configuration', 'action' => 'index'], ['_name' => 'admin_configuration_index']);
            $b->connect('/edit-lang', ['controller' => 'Configuration', 'action' => 'editLang'], ['_name' => 'admin_configuration_edit_lang']);
        });

        $builder->scope('/history', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'History', 'action' => 'index'], ['_name' => 'admin_history_index']);
            $b->connect('/get-all', ['controller' => 'History', 'action' => 'getAll'], ['_name' => 'admin_history_get_all']);
        });

        $builder->scope('/log', function (RouteBuilder $b): void {
            $b->connect('/debug', ['controller' => 'Log', 'action' => 'debug'], ['_name' => 'admin_log_debug']);
            $b->connect('/error', ['controller' => 'Log', 'action' => 'error'], ['_name' => 'admin_log_error']);
        });

        $builder->scope('/maintenance', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Maintenance', 'action' => 'index'], ['_name' => 'admin_maintenance_index']);
            $b->connect('/add', ['controller' => 'Maintenance', 'action' => 'add'], ['_name' => 'admin_maintenance_add']);
            $b->connect('/delete/*', ['controller' => 'Maintenance', 'action' => 'delete'], ['_name' => 'admin_maintenance_delete']);
            $b->connect('/disable/*', ['controller' => 'Maintenance', 'action' => 'disable'], ['_name' => 'admin_maintenance_disable']);
            $b->connect('/edit/*', ['controller' => 'Maintenance', 'action' => 'edit'], ['_name' => 'admin_maintenance_edit']);
            $b->connect('/enable/*', ['controller' => 'Maintenance', 'action' => 'enable'], ['_name' => 'admin_maintenance_enable']);
        });

        $builder->scope('/motd', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Motd', 'action' => 'index'], ['_name' => 'admin_motd_index']);
            $b->connect('/edit/*', ['controller' => 'Motd', 'action' => 'edit'], ['_name' => 'admin_motd_edit']);
            $b->connect('/edit-ajax/*', ['controller' => 'Motd', 'action' => 'editAjax'], ['_name' => 'admin_motd_edit_ajax']);
            $b->connect('/reset/*', ['controller' => 'Motd', 'action' => 'reset'], ['_name' => 'admin_motd_reset']);
        });

        $builder->scope('/navbar', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Navbar', 'action' => 'index'], ['_name' => 'admin_navbar_index']);
            $b->connect('/add', ['controller' => 'Navbar', 'action' => 'add'], ['_name' => 'admin_navbar_add']);
            $b->connect('/add-ajax', ['controller' => 'Navbar', 'action' => 'addAjax'], ['_name' => 'admin_navbar_add_ajax']);
            $b->connect('/delete/*', ['controller' => 'Navbar', 'action' => 'delete'], ['_name' => 'admin_navbar_delete']);
            $b->connect('/edit/*', ['controller' => 'Navbar', 'action' => 'edit'], ['_name' => 'admin_navbar_edit']);
            $b->connect('/edit-ajax', ['controller' => 'Navbar', 'action' => 'editAjax'], ['_name' => 'admin_navbar_edit_ajax']);
            $b->connect('/save-ajax', ['controller' => 'Navbar', 'action' => 'saveAjax'], ['_name' => 'admin_navbar_save_ajax']);
        });

        $builder->scope('/news', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'News', 'action' => 'index'], ['_name' => 'admin_news_index']);
            $b->connect('/add', ['controller' => 'News', 'action' => 'add'], ['_name' => 'admin_news_add']);
            $b->connect('/add-ajax', ['controller' => 'News', 'action' => 'addAjax'], ['_name' => 'admin_news_add_ajax']);
            $b->connect('/delete/*', ['controller' => 'News', 'action' => 'delete'], ['_name' => 'admin_news_delete']);
            $b->connect('/edit/*', ['controller' => 'News', 'action' => 'edit'], ['_name' => 'admin_news_edit']);
            $b->connect('/edit-ajax', ['controller' => 'News', 'action' => 'editAjax'], ['_name' => 'admin_news_edit_ajax']);
        });

        $builder->scope('/notifications', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Notifications', 'action' => 'index'], ['_name' => 'admin_notifications_index']);
            $b->connect('/clear-all-from-all-users', ['controller' => 'Notifications', 'action' => 'clearAllFromAllUsers'], ['_name' => 'admin_notifications_clear_all_from_all_users']);
            $b->connect('/clear-all-from-group', ['controller' => 'Notifications', 'action' => 'clearAllFromGroup'], ['_name' => 'admin_notifications_clear_all_from_group']);
            $b->connect('/clear-all-from-user/*', ['controller' => 'Notifications', 'action' => 'clearAllFromUser'], ['_name' => 'admin_notifications_clear_all_from_user']);
            $b->connect('/clear-from-all-users/*', ['controller' => 'Notifications', 'action' => 'clearFromAllUsers'], ['_name' => 'admin_notifications_clear_from_all_users']);
            $b->connect('/clear-from-user/*', ['controller' => 'Notifications', 'action' => 'clearFromUser'], ['_name' => 'admin_notifications_clear_from_user']);
            $b->connect('/get-all', ['controller' => 'Notifications', 'action' => 'getAll'], ['_name' => 'admin_notifications_get_all']);
            $b->connect('/mark-all-as-seen-from-all-users', ['controller' => 'Notifications', 'action' => 'markAllAsSeenFromAllUsers'], ['_name' => 'admin_notifications_mark_all_as_seen_from_all_users']);
            $b->connect('/mark-all-as-seen-from-user/*', ['controller' => 'Notifications', 'action' => 'markAllAsSeenFromUser'], ['_name' => 'admin_notifications_mark_all_as_seen_from_user']);
            $b->connect('/mark-as-seen-from-all-users/*', ['controller' => 'Notifications', 'action' => 'markAsSeenFromAllUsers'], ['_name' => 'admin_notifications_mark_as_seen_from_all_users']);
            $b->connect('/mark-as-seen-from-user/*', ['controller' => 'Notifications', 'action' => 'markAsSeenFromUser'], ['_name' => 'admin_notifications_mark_as_seen_from_user']);
            $b->connect('/set-to', ['controller' => 'Notifications', 'action' => 'setTo'], ['_name' => 'admin_notifications_set_to']);
        });

        $builder->scope('/pages', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Pages', 'action' => 'index'], ['_name' => 'admin_pages_index']);
            $b->connect('/add', ['controller' => 'Pages', 'action' => 'add'], ['_name' => 'admin_pages_add']);
            $b->connect('/add-ajax', ['controller' => 'Pages', 'action' => 'addAjax'], ['_name' => 'admin_pages_add_ajax']);
            $b->connect('/delete/*', ['controller' => 'Pages', 'action' => 'delete'], ['_name' => 'admin_pages_delete']);
            $b->connect('/edit/*', ['controller' => 'Pages', 'action' => 'edit'], ['_name' => 'admin_pages_edit']);
            $b->connect('/edit-ajax', ['controller' => 'Pages', 'action' => 'editAjax'], ['_name' => 'admin_pages_edit_ajax']);
        });

        $builder->scope('/permissions', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Permissions', 'action' => 'index'], ['_name' => 'admin_permissions_index']);
            $b->connect('/add-rank', ['controller' => 'Permissions', 'action' => 'addRank'], ['_name' => 'admin_permissions_add_rank']);
            $b->connect('/delete-rank/*', ['controller' => 'Permissions', 'action' => 'deleteRank'], ['_name' => 'admin_permissions_delete_rank']);
        });

        $builder->scope('/plugin', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Plugin', 'action' => 'index'], ['_name' => 'admin_plugin_index']);
            $b->connect('/admin-delete/*', ['controller' => 'Plugin', 'action' => 'admin_delete'], ['_name' => 'admin_plugin_delete']);
            $b->connect('/admin-disable/*', ['controller' => 'Plugin', 'action' => 'admin_disable'], ['_name' => 'admin_plugin_disable']);
            $b->connect('/admin-enable/*', ['controller' => 'Plugin', 'action' => 'admin_enable'], ['_name' => 'admin_plugin_enable']);
            $b->connect('/admin-update/*', ['controller' => 'Plugin', 'action' => 'admin_update'], ['_name' => 'admin_plugin_update']);
            $b->connect('/install/*', ['controller' => 'Plugin', 'action' => 'install'], ['_name' => 'admin_plugin_install']);
        });

        $builder->scope('/seo', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Seo', 'action' => 'index'], ['_name' => 'admin_seo_index']);
            $b->connect('/add', ['controller' => 'Seo', 'action' => 'add'], ['_name' => 'admin_seo_add']);
            $b->connect('/delete/*', ['controller' => 'Seo', 'action' => 'delete'], ['_name' => 'admin_seo_delete']);
            $b->connect('/edit/*', ['controller' => 'Seo', 'action' => 'edit'], ['_name' => 'admin_seo_edit']);
            $b->connect('/edit-default', ['controller' => 'Seo', 'action' => 'editDefault'], ['_name' => 'admin_seo_edit_default']);
        });

        $builder->scope('/server', function (RouteBuilder $b): void {
            $b->connect('/add-cmd', ['controller' => 'Server', 'action' => 'addCmd'], ['_name' => 'admin_server_add_cmd']);
            $b->connect('/banlist/*', ['controller' => 'Server', 'action' => 'banlist'], ['_name' => 'admin_server_banlist']);
            $b->connect('/cmd', ['controller' => 'Server', 'action' => 'cmd'], ['_name' => 'admin_server_cmd']);
            $b->connect('/config', ['controller' => 'Server', 'action' => 'config'], ['_name' => 'admin_server_config']);
            $b->connect('/delete/*', ['controller' => 'Server', 'action' => 'delete'], ['_name' => 'admin_server_delete']);
            $b->connect('/delete-cmd/*', ['controller' => 'Server', 'action' => 'deleteCmd'], ['_name' => 'admin_server_delete_cmd']);
            $b->connect('/edit-banner-msg', ['controller' => 'Server', 'action' => 'editBannerMsg'], ['_name' => 'admin_server_edit_banner_msg']);
            $b->connect('/execute-cmd', ['controller' => 'Server', 'action' => 'executeCmd'], ['_name' => 'admin_server_execute_cmd']);
            $b->connect('/link', ['controller' => 'Server', 'action' => 'link'], ['_name' => 'admin_server_link']);
            $b->connect('/link-ajax', ['controller' => 'Server', 'action' => 'linkAjax'], ['_name' => 'admin_server_link_ajax']);
            $b->connect('/online/*', ['controller' => 'Server', 'action' => 'online'], ['_name' => 'admin_server_online']);
            $b->connect('/switch-banner/*', ['controller' => 'Server', 'action' => 'switchBanner'], ['_name' => 'admin_server_switch_banner']);
            $b->connect('/switch-cache-state', ['controller' => 'Server', 'action' => 'switchCacheState'], ['_name' => 'admin_server_switch_cache_state']);
            $b->connect('/switch-state', ['controller' => 'Server', 'action' => 'switchState'], ['_name' => 'admin_server_switch_state']);
            $b->connect('/whitelist/*', ['controller' => 'Server', 'action' => 'whitelist'], ['_name' => 'admin_server_whitelist']);
        });

        $builder->scope('/slider', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Slider', 'action' => 'index'], ['_name' => 'admin_slider_index']);
            $b->connect('/add', ['controller' => 'Slider', 'action' => 'add'], ['_name' => 'admin_slider_add']);
            $b->connect('/add-ajax', ['controller' => 'Slider', 'action' => 'addAjax'], ['_name' => 'admin_slider_add_ajax']);
            $b->connect('/delete/*', ['controller' => 'Slider', 'action' => 'delete'], ['_name' => 'admin_slider_delete']);
            $b->connect('/edit/*', ['controller' => 'Slider', 'action' => 'edit'], ['_name' => 'admin_slider_edit']);
            $b->connect('/edit-ajax', ['controller' => 'Slider', 'action' => 'editAjax'], ['_name' => 'admin_slider_edit_ajax']);
        });

        $builder->scope('/social', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Social', 'action' => 'index'], ['_name' => 'admin_social_index']);
            $b->connect('/add', ['controller' => 'Social', 'action' => 'add'], ['_name' => 'admin_social_add']);
            $b->connect('/delete/*', ['controller' => 'Social', 'action' => 'delete'], ['_name' => 'admin_social_delete']);
            $b->connect('/edit/*', ['controller' => 'Social', 'action' => 'edit'], ['_name' => 'admin_social_edit']);
            $b->connect('/save-ajax', ['controller' => 'Social', 'action' => 'saveAjax'], ['_name' => 'admin_social_save_ajax']);
        });

        $builder->scope('/statistics', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Statistics', 'action' => 'index'], ['_name' => 'admin_statistics_index']);
            $b->connect('/get-visits', ['controller' => 'Statistics', 'action' => 'getVisits'], ['_name' => 'admin_statistics_get_visits']);
            $b->connect('/reset', ['controller' => 'Statistics', 'action' => 'reset'], ['_name' => 'admin_statistics_reset']);
        });

        $builder->scope('/theme', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Theme', 'action' => 'index'], ['_name' => 'admin_theme_index']);
            $b->connect('/custom/*', ['controller' => 'Theme', 'action' => 'custom'], ['_name' => 'admin_theme_custom']);
            $b->connect('/custom-files/*', ['controller' => 'Theme', 'action' => 'customFiles'], ['_name' => 'admin_theme_custom_files']);
            $b->connect('/delete/*', ['controller' => 'Theme', 'action' => 'delete'], ['_name' => 'admin_theme_delete']);
            $b->connect('/enable/*', ['controller' => 'Theme', 'action' => 'enable'], ['_name' => 'admin_theme_enable']);
            $b->connect('/get-custom-file/*', ['controller' => 'Theme', 'action' => 'getCustomFile'], ['_name' => 'admin_theme_get_custom_file']);
            $b->connect('/install/*', ['controller' => 'Theme', 'action' => 'install'], ['_name' => 'admin_theme_install']);
            $b->connect('/save-custom-file/*', ['controller' => 'Theme', 'action' => 'saveCustomFile'], ['_name' => 'admin_theme_save_custom_file']);
            $b->connect('/update/*', ['controller' => 'Theme', 'action' => 'update'], ['_name' => 'admin_theme_update']);
        });

        $builder->scope('/update', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'Update', 'action' => 'index'], ['_name' => 'admin_update_index']);
            $b->connect('/check', ['controller' => 'Update', 'action' => 'check'], ['_name' => 'admin_update_check']);
            $b->connect('/clear-cache', ['controller' => 'Update', 'action' => 'clearCache'], ['_name' => 'admin_update_clear_cache']);
            $b->connect('/update/*', ['controller' => 'Update', 'action' => 'update'], ['_name' => 'admin_update_update']);
        });

        $builder->scope('/user', function (RouteBuilder $b): void {
            $b->connect('/', ['controller' => 'User', 'action' => 'index'], ['_name' => 'admin_user_index']);
            $b->connect('/confirm/*', ['controller' => 'User', 'action' => 'confirm'], ['_name' => 'admin_auth_confirm']);
            $b->connect('/delete/*', ['controller' => 'User', 'action' => 'delete'], ['_name' => 'admin_user_delete']);
            $b->connect('/edit/*', ['controller' => 'User', 'action' => 'edit'], ['_name' => 'admin_user_edit']);
            $b->connect('/edit-ajax', ['controller' => 'User', 'action' => 'editAjax'], ['_name' => 'admin_user_edit_ajax']);
            $b->connect('/get-users', ['controller' => 'User', 'action' => 'getUsers'], ['_name' => 'admin_user_get_users']);
            $b->connect('/live-search/*', ['controller' => 'User', 'action' => 'liveSearch'], ['_name' => 'admin_user_live_search']);
        });
    });
};
