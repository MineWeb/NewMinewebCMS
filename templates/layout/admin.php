<!DOCTYPE html>
<html lang="<?= h($this->Seo->htmlLang()) ?>">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title><?= h($this->Seo->getTitle((string)($title ?? $title_for_layout ?? 'MineWeb'))) ?> | Admin</title>
    <?= $this->Seo->favicon() ?>
    <meta name="viewport" content="width=device-width, initial-scale=1">

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

    <style>
        footer li { display:inline; padding:0 2px }
    </style>

    <?= $this->Html->meta('csrf-token', $this->request->getAttribute('csrfToken')) ?>
</head>

<body class="hold-transition sidebar-mini layout-fixed <?= h($this->AdminUi->bodyClass()) ?> ">
<div class="wrapper">

    <nav class="main-header navbar navbar-expand navbar-dark navbar-lightblue">
        <ul class="navbar-nav">
            <li class="nav-item">
                <a class="nav-link" data-widget="pushmenu" href="#" role="button"><i class="fas fa-bars"></i></a>
            </li>
        </ul>

        <ul class="navbar-nav ml-auto">
            <li class="nav-item">
                <div class="nav-link custom-control custom-switch custom-switch-off-light custom-switch-on-success" data-children-count="1">
                    <input type="checkbox" class="custom-control-input switchAdminDarkMode"
                           id="customSwitch3" <?= $this->AdminUi->darkModeEnabled() ? 'checked' : '' ?>>
                    <label class="custom-control-label" for="customSwitch3">Dark-Mode</label>
                </div>
            </li>

            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function () {
                    let btn = document.querySelector('.switchAdminDarkMode');
                    if (!btn) return;

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
                <a class="nav-link" data-toggle="dropdown" href="#" aria-haspopup="true" aria-expanded="false" id="notifDropdownBtn">
                    <i class="far fa-bell"></i>
                    <span id="notification-indicator-badge" class="badge badge-warning navbar-badge" style="display:none;"></span>
                </a>

                <div id="notification-container" class="dropdown-menu dropdown-menu-right p-0" style="min-width:360px;">
                </div>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="#">
                    <span class="hidden-xs"><?= h($this->Auth->username()) ?></span>
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link" href="<?= $this->Url->build(['_name' => 'auth_logout']); ?>">
                    <i class="fa fa-power-off"></i> <?= __('USER__LOGOUT') ?>
                </a>
            </li>
        </ul>

        <?= $this->Html->script('notification') ?>

        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                window.notification = new window.Notification({
                    type: 'admin',
                    limit: 5,
                    urls: {
                        get: '<?= $this->Url->build(['_name' => 'notifications_get_all']) ?>',
                        clear: '<?= $this->Url->build(['_name' => 'notifications_clear', 'NOTIF_ID']) ?>',
                        clearAll: '<?= $this->Url->build(['_name' => 'notifications_clear_all']) ?>',
                        markAsSeen: '<?= $this->Url->build(['_name' => 'notifications_mark_as_seen', 'NOTIF_ID']) ?>',
                        markAllAsSeen: '<?= $this->Url->build(['_name' => 'notifications_mark_all_as_seen']) ?>'
                    },
                    selectors: {
                        indicator: '#notification-indicator-badge',
                        list: '#notification-container'
                    },
                    texts: {
                        list: <?= json_encode(__('NOTIFICATIONS__LIST'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        clearAll: <?= json_encode(__('NOTIFICATIONS__CLEAR_ALL'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        markAsSeen: <?= json_encode(__('NOTIFICATION__MARK_AS_SEEN'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
                        empty: <?= json_encode(__('NOTIFICATIONS__EMPTY'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
                    },
                    templates: {
                        header: function (t) {
                            return ''
                                + '<div class="dropdown-header d-flex align-items-center justify-content-between px-3 py-2">'
                                + '  <strong>' + t.list + '</strong>'
                                + '</div>'
                                + '<div class="dropdown-divider m-0"></div>';
                        },
                        footer: function (t) {
                            return ''
                                + '<div class="dropdown-divider m-0"></div>'
                                + '<div class="px-3 py-2">'
                                + '  <div class="btn-group btn-group-sm w-100">'
                                + '    <button type="button" class="btn btn-outline-secondary" data-notif-action="mark-all">'
                                + '      <i class="fas fa-check mr-1"></i>' + t.markAsSeen
                                + '    </button>'
                                + '    <button type="button" class="btn btn-outline-danger" data-notif-action="clear-all">'
                                + '      <i class="fas fa-trash mr-1"></i>' + t.clearAll
                                + '    </button>'
                                + '  </div>'
                                + '</div>';
                        },
                        empty: function (t) {
                            return ''
                                + '<div class="p-3 text-muted text-center">'
                                + '  <small>' + t.empty + '</small>'
                                + '</div>';
                        },
                        item: function (n, id) {
                            const content = (window.Notification && window.Notification.__escapeHtml)
                                ? window.Notification.__escapeHtml(n && n.content)
                                : String(n && n.content ? n.content : '');
                            const time = (window.Notification && window.Notification.__escapeHtml)
                                ? window.Notification.__escapeHtml(n && n.time)
                                : String(n && n.time ? n.time : '');

                            const seenClass = n && n.seen ? 'text-muted' : '';
                            const bg = n && n.seen ? '' : 'bg-light';

                            return ''
                                + '<div class="dropdown-item ' + bg + ' ' + seenClass + '" style="white-space:normal;">'
                                + '  <div class="d-flex align-items-start">'
                                + '    <div class="flex-grow-1" style="min-width:0;">'
                                + '      <div class="text-truncate" style="max-width: 260px;">' + content + '</div>'
                                + '      <div class="small text-muted">' + time + '</div>'
                                + '    </div>'
                                + '    <div class="btn-group btn-group-sm ml-2">'
                                + '      <button type="button" class="btn btn-light" data-notif-action="mark" data-notif-id="' + id + '" title="✓">'
                                + '        <i class="fas fa-check"></i>'
                                + '      </button>'
                                + '      <button type="button" class="btn btn-light text-danger" data-notif-action="clear" data-notif-id="' + id + '" title="×">'
                                + '        <i class="fas fa-times"></i>'
                                + '      </button>'
                                + '    </div>'
                                + '  </div>'
                                + '</div>';
                        }
                    }

                });
                if (window.notification) {
                    window.notification.startAutoRefresh(15);
                }

                const btn = document.getElementById('notifDropdownBtn');
                if (btn) {
                    btn.addEventListener('click', function () {
                        if (window.notification) window.notification.markAllAsSeen(1);
                    });
                }

                const container = document.getElementById('notification-container');
                if (container) {
                    container.addEventListener('click', function (e) {
                        e.stopPropagation();
                    });
                }
            });
        </script>
    </nav>

    <?= $this->cell('AdminNavbar') ?>

    <div class="content-wrapper">
        <section class="content-header">
            <?= $this->Update->cmsAvailableHtml() ?>
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

    <aside class="control-sidebar control-sidebar-dark"></aside>
</div>

<?= $this->Html->script('adminlte-3/plugins/jquery/jquery.min') ?>
<?= $this->Html->script('adminlte-3/plugins/jquery-ui/jquery-ui.min') ?>
<script type="text/javascript">
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
<?= $this->Html->script('admin/app') ?>
<?= $this->Html->script('form') ?>

<script type="text/javascript">
    let LOADING_MSG = <?= json_encode(__('GLOBAL__LOADING'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let ERROR_MSG = <?= json_encode(__('GLOBAL__ERROR'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let INTERNAL_ERROR_MSG = <?= json_encode(__('ERROR__INTERNAL_ERROR'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let FORBIDDEN_ERROR_MSG = <?= json_encode(__('ERROR__FORBIDDEN'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
    let SUCCESS_MSG = <?= json_encode(__('GLOBAL__SUCCESS'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;
</script>

<script type="text/javascript">
    function confirmDel(url) {
        if (confirm("<?= __('GLOBAL__CONFIRM_DELETE') ?>")) window.location.href = '' + url + '';
        else return false;
    }
</script>
</body>
</html>
