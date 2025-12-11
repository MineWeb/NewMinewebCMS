<?php

use Cake\Routing\Router;

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= $title_for_layout ?> | Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="icon" type="image/png" href="<?= $seo_config['favicon_url'] ?>"/>
    <?= $this->Html->css('fontawesome-5/css/all'); ?>
    <?= $this->Html->css('bootstrap-4/plugins/tempusdominus/tempusdominus-bootstrap-4.min'); ?>
    <?= $this->Html->css('bootstrap-4/plugins/icheck/icheck-bootstrap.min'); ?>
    <?= $this->Html->css('adminlte-3/adminlte.min'); ?>
    <?= $this->Html->css('datatables/2.3.5/dataTables.bootstrap4.min'); ?>

    <?= $this->Html->css('admin'); ?>
    <?= $this->Html->css('adminlte-3/plugins/overlayScrollbars/OverlayScrollbars.min'); ?>
    <?= $this->Html->css('adminlte-3/plugins/daterangepicker/daterangepicker'); ?>
    <?= $this->Html->script('adminlte-3/plugins/jquery/jquery.min') ?>
    <?= $this->Html->script('chart.js/Chart.min') ?>
    <link href="https://fonts.googleapis.com/css?family=Source+Sans+Pro:300,400,400i,700" rel="stylesheet">
</head>
<body class="hold-transition sidebar-mini layout-fixed <?= $admin_dark_mode ? "dark-mode" : "" ?> ">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-dark navbar-lightblue">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>
        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <div class="nav-link custom-control custom-switch custom-switch-off-danger custom-switch-on-success"
                     data-children-count="1">
                    <input type="checkbox" class="custom-control-input switchAdminDarkMode"
                           id="customSwitch3" <?= (isset($admin_dark_mode) && $admin_dark_mode) ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="customSwitch3">Dark-Mode</label>
                </div>
            </li>
            <script>
                document.addEventListener('DOMContentLoaded', function () {
                    let btn = document.querySelector('.switchAdminDarkMode');
                    if (!btn) {
                        return;
                    }

                    btn.addEventListener('change', function (e) {
                        e.preventDefault();

                        if (btn.checked) {
                            document.body.classList.add('dark-mode');
                        } else {
                            document.body.classList.remove('dark-mode');
                        }

                        fetch('<?= $this->Url->build(['_name' => 'admin_switch_dark_mode']) ?>');

                        return false;
                    });
                });
            </script>
            <li class="nav-item dropdown">
                <a class="nav-link" onclick="notification.markAllAsSeen(1)" data-toggle="dropdown" href="#">
                    <i class="far fa-bell"></i>
                </a>
                <div id="notification-container" class="dropdown-menu dropdown-menu-right">

                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span class="hidden-xs"><?= $user['pseudo'] ?></span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link"
                   href="<?= $this->Url->build(['_name' => 'user_logout']); ?>"><i
                        class="fa fa-power-off"></i> <?= __('USER__LOGOUT') ?></a>
            </li>
        </ul>

        <?= $this->Html->script('notification') ?>

        <script type="text/javascript">
            let notification = new $.Notification({
                'notification_type': 'admin',
                'limit': 5,
                'url': {
                    'get': '<?= $this->Url->build(['_name' => 'notifications_get_all']) ?>',
                    'clear': '<?= $this->Url->build(['_name' => 'notifications_clear', 'NOTIF_ID']) ?>',
                    'clearAll': '<?= $this->Url->build(['_name' => 'notifications_clear_all']) ?>',
                    'markAsSeen': '<?= $this->Url->build(['_name' => 'notifications_mark_as_seen', 'NOTIF_ID']) ?>',
                    'markAllAsSeen': '<?= $this->Url->build(['_name' => 'notifications_mark_all_as_seen']) ?>'
                },
                'messages': {
                    'markAsSeen': '<?= __('NOTIFICATION__MARK_AS_SEEN') ?>',
                    'notifiedBy': '<?= __('NOTIFICATION__NOTIFIED_BY') ?>'
                },
                'indicator': {
                    'element': '#notification-indicator',
                    'class': 'label label-warning',
                    'style': {},
                    'defaultContent': '<i class="fa fa-bell-o"></i>'
                },
                'list': {
                    'element': '#notification-container',
                    'container': {
                        'type': '',
                        'class': '',
                        'style': ''
                    },
                    'notification': {
                        'type': 'a',
                        'class': 'dropdown-item',
                        'style': '',
                        'content': '{CONTENT}',
                        'from': {
                            'type': '',
                            'class': '',
                            'style': '',
                            'content': ''
                        },
                        'seen': {
                            'element': {
                                'style': '',
                                'class': ''
                            },
                            'btn': {
                                'element': '.mark-as-seen',
                                'style': '',
                                'class': 'hidden',
                                'attr': [{'onclick': ''}]
                            }
                        }
                    }
                }
            });
        </script>
    </nav>

    <aside class="main-sidebar sidebar-dark-lightblue elevation-4">
        <a href="<?= $this->Url->build(['_name' => 'home']) ?>" class="brand-link navbar-lightblue text-center text-white">
            <span class="brand-text font-weight-light"><?= __('GLOBAL__ADMINISTRATION'); ?></span>
        </a>
        <div class="sidebar">
            <nav class="mt-2">
                <ul class="nav nav-pills nav-sidebar flex-column nav-flat nav-child-indent" data-widget="treeview"
                    role="menu"
                    data-accordion="false">
                    <?php

                    function checkCurrent($nav)
                    {
                        if (is_array($nav)) {
                            foreach ($nav as $value) {
                                if (isset($v['menu'])) {
                                    return checkCurrent($v['menu']);
                                }
                                $route = (isset($value['route']) ? Router::url($value['route']) : '#');
                                $current = $route == Router::url();
                                if ($current == $route) {
                                    return true;
                                }
                            }
                        }
                        return false;
                    }

                    function displayNav($nav, $context)
                    {
                        foreach ($nav as $name => $value) {
                            if (!isset($value['menu']) && !isset($value['route'])) {
                                continue;
                            }
                            if (!isset($value['menu']) && isset($value['permission']) && !$context->Permissions->can($value['permission'])) {
                                continue;
                            }
                            $currentMenu = false;
                            if (isset($value['menu'])) {
                                $currentMenu = checkCurrent($value['menu']) ? "menu-open" : "";
                                echo '<li class="nav-item has-treeview ' . ($currentMenu ? "menu-open" : "") . '">';
                            } else {
                                echo '<li class="nav-item">';
                            }
                            $route = (isset($value['route']) ? Router::url($value['route']) : '#');
                            $current = $route == Router::url();
                            echo '<a class="nav-link' . ($current || $currentMenu ? " active" : "") . '" href="' . $route . '">';
                            echo '<i class="' . (strpos($value['icon'], "fa-") ? $value['icon'] : "fa fa-" . $value['icon']) . ' nav-icon"></i>  <p>' . __($name);
                            if (isset($value['menu'])) {
                                echo '<i class="fas fa-angle-left right"></i></p>';
                            } else {
                                echo '</p>';
                            }
                            echo '</a>';
                            if (isset($value['menu'])) {
                                echo '<ul class="nav nav-treeview">';
                                displayNav($value['menu'], $context);
                                echo '</ul>';
                            }
                            echo '</li>';
                        }
                    }

                    displayNav($adminNavbar, (object)['Permissions' => $Permissions]);
                    ?>
                </ul>
            </nav>
        </div>
    </aside>
    <div class="content-wrapper">
        <section class="content-header">
            <?= $Update->available() ?>
            <?= (isset($admin_custom_message['messageHTML'])) ? $admin_custom_message['messageHTML'] : '' ?>
            <?php echo $this->Flash->render(); ?>
        </section>

        <?php echo $this->fetch('content'); ?>
    </div>

    <footer class="main-footer text-center">
        <?= __('GLOBAL__FOOTER_ADMIN') ?>
        <p>CakePhP version : <a href="https://cakephp.org/"><?= \Cake\Core\Configure::version(); ?></a></p>
        Credits <a href="http://almsaeedstudio.com">Almsaeed Studio</a>.</strong>
    </footer>

    <style>
        footer li {
            display: inline;
            padding: 0 2px
        }
    </style>

    <aside class="control-sidebar control-sidebar-dark">
    </aside>
</div>

<?= $this->Html->script('adminlte-3/plugins/jquery/jquery.min') ?>
<?= $this->Html->script('adminlte-3/plugins/jquery-ui/jquery-ui.min') ?>
<script>
    $.widget.bridge('uibutton', $.ui.button)
</script>
<?= $this->Html->script('bootstrap-4/bootstrap.bundle.min') ?>

<?= $this->Html->script('datatables/2.3.5/dataTables.min') ?>
<?= $this->Html->script('datatables/2.3.5/dataTables.bootstrap4.min') ?>


<?= $this->Html->script('adminlte-3/plugins/sparklines/sparkline') ?>
<?= $this->Html->script('adminlte-3/plugins/jquery-knob/jquery.knob.min') ?>
<?= $this->Html->script('bootstrap-4/plugins/moment/moment.min') ?>
<?= $this->Html->script('bootstrap-4/plugins/tempusdominus/tempusdominus-bootstrap-4.min') ?>
<?= $this->Html->script('adminlte-3/plugins/daterangepicker/daterangepicker'); ?>
<?= $this->Html->script('adminlte-3/plugins/overlayScrollbars/jquery.overlayScrollbars.min'); ?>
<?= $this->Html->script('adminlte-3/adminlte') ?>
<?= $this->Html->script('sortablejs/1.15.6/Sortable.min') ?>
<?= $this->Html->script('mineweb_admin') ?>
<?= $this->Html->script('form') ?>
<script type="text/javascript">
    let LOADING_MSG = "<?= __('GLOBAL__LOADING') ?>";
    let ERROR_MSG = "<?= __('GLOBAL__ERROR') ?>";
    let INTERNAL_ERROR_MSG = "<?= __('ERROR__INTERNAL_ERROR') ?>";
    let FORBIDDEN_ERROR_MSG = "<?= __('ERROR__FORBIDDEN') ?>";
    let SUCCESS_MSG = "<?= __('GLOBAL__SUCCESS') ?>";
    let CSRF_TOKEN = "<?= $csrfToken ?>";
</script>

<?= $this->element('mineweb_admin_js'); ?>
</body>
</html>
