
<!DOCTYPE html>
<html lang="fr">

<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="author" content="Eywek">

    <?= $this->element('seo') ?>

    <!-- Font Awesome 5 -->
    <script src="https://kit.fontawesome.com/fb032ab5a6.js" crossorigin="anonymous"></script>
    <?= $this->Html->css('bootstrap') ?>
    <?= $this->Html->css('modern-business') ?>
    <?= $this->Html->css('animate') ?>
    <?= $this->Html->css('flat') ?>
    <link href='https://fonts.googleapis.com/css?family=Roboto:400,300,900' rel='stylesheet' type='text/css'>
    <link href='https://fonts.googleapis.com/css?family=Open+Sans:300italic,400italic,700italic,400,300,700'
          rel='stylesheet' type='text/css'>
    <?= $this->Html->script('jquery-1.11.0') ?>
    <?= $this->Html->script('easy_paginate') ?>

    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/libs/html5shiv/3.7.0/html5shiv.js"></script>
    <script src="https://oss.maxcdn.com/libs/respond.js/1.4.2/respond.min.js"></script>
    <![endif]-->

</head>

<body><!-- grey.png -->
<nav class="navbar navbar-default navbar-fixed-top" role="navigation">
    <div class="mini-navbar mini-navbar-default">
        <div class="container">
            <div class="col-sm-12">
                <?= (isset($banner_server) && $banner_server) ? '<p>' . $banner_server . '</p>' : '<p class="text-center">' . __('SERVER__STATUS_OFF') . '</p>' ?>
            </div>
        </div>
    </div>
    <div class="container nav-content">
        <div class="navbar-header">
            <button type="button" class="navbar-toggle" data-toggle="collapse"
                    data-target="#bs-example-navbar-collapse-1">
                <span class="sr-only">Toggle navigation</span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
                <span class="icon-bar"></span>
            </button>
            <a class="navbar-brand"
               href="<?= $this->Url->build('/') ?>"><?= (isset($website_name)) ? $website_name : 'MineWeb' ?></a>
        </div>
        <div class="collapse navbar-collapse" id="bs-example-navbar-collapse-1">
            <ul class="nav navbar-nav navbar-right">
                <li class="li-nav">
                    <a href="<?= $this->Url->build('/') ?>"><?= __('GLOBAL__HOME') ?></a>
                </li>
                <?php
                if (!empty($nav)) {
                    $i = 0;
                    foreach ($nav as $key => $value) { ?>
                        <?php if (empty($value['submenu'])) { ?>
                            <li class="li-nav<?php if ($this->getRequest()->getParam('controller') == $value['name']) { ?> actived<?php } ?>">
                                <a href="<?= $value['url'] ?>"<?= ($value['open_new_tab']) ? ' target="_blank"' : '' ?>>
                                    <?php if (!empty($value['icon'])): ?>
                                        <i class="<?= ((strpos($value['icon'], "fa-")) ? $value['icon'] : "fa fa-" . $value['icon']) ?>"></i>
                                    <?php endif; ?>
                                    <?= $value['name'] ?>
                                </a>

                            </li>
                        <?php } else { ?>
                            <li class="dropdown">
                                <a href="#" class="dropdown-toggle" data-toggle="dropdown" role="button"
                                   aria-expanded="false"><?= $value['name'] ?> <span class="caret"></span></a>
                                <ul class="dropdown-menu" role="menu">
                                    <?php
                                    $submenu = json_decode($value['submenu']);
                                    foreach ($submenu as $k => $v) {
                                        ?>
                                        <li>
                                            <a href="<?= rawurldecode($v) ?>"<?= ($value['open_new_tab']) ? ' target="_blank"' : '' ?>><?= rawurldecode(str_replace('+', ' ', $k)) ?></a>
                                        </li>
                                    <?php } ?>
                                </ul>
                            </li>
                        <?php } ?>
                        <?php
                        $i++;
                    }
                } ?>
                <li class="button">
                    <div class="btn-group">
                        <?php if (isset($isConnected) && $isConnected) { ?>
                            <button type="button" class="btn btn-success"><?= $user['pseudo'] ?></button>
                        <?php } else { ?>
                            <button type="button" class="btn btn-success"><i class="fa fa-user"></i></button>
                        <?php } ?>
                        <button type="button" class="btn btn-success dropdown-toggle" data-toggle="dropdown">
                            <span class="caret"></span>
                            <span class="notification-indicator"></span>
                            <span class="sr-only">Toggle Dropdown</span>
                        </button>
                        <ul class="dropdown-menu" role="menu">
                            <?php if ($isConnected) { ?>
                                <li>
                                    <a href="<?= $this->Url->build(['_name' => 'user_profile']) ?>"><?= __('USER__PROFILE') ?></a>
                                </li>
                                <li style="position:relative;">
                                    <a href="#notifications_modal" onclick="notification.markAllAsSeen(2)"
                                       data-toggle="modal"><?= __('NOTIFICATIONS__LIST') ?></a>
                                    <span class="notification-indicator"></span>
                                </li>
                                <?php if ($Permissions->can('ACCESS_DASHBOARD')) { ?>
                                    <li class="divider"></li>
                                    <li>
                                        <a href="<?= $this->Url->build(['_name' => 'admin_index']) ?>"><?= __('GLOBAL__ADMIN_PANEL') ?></a>
                                    </li>
                                <?php } ?>
                                <li class="divider"></li>
                                <li>
                                    <a href="<?= $this->Url->build(['_name' => 'user_logout']) ?>"><?= __('USER__LOGOUT') ?></a>
                                </li>
                            <?php } else { ?>
                                <li><a href="#" data-toggle="modal"
                                       data-target="#login"><?= __('USER__LOGIN') ?></a></li>
                                <li><a href="#" data-toggle="modal"
                                       data-target="#register"><?= __('USER__REGISTER') ?></a></li>
                            <?php } ?>
                        </ul>
                    </div>
                </li>
            </ul>
        </div>
    </div>
</nav>
<div class="nav-hop"></div>
<?php
$flash_messages = $this->Flash->render();
if (!empty($flash_messages)) {
    echo '<div class="container">' . $flash_messages . '</div>';
} ?>
<?= $this->fetch('content'); ?>
<!-- Footer -->
<footer style="height: 50px;">
    <div class="container">
        <div class="row">
            <div class="col-lg-12">
                <p><?= __('GLOBAL__FOOTER', ["{%year}" => date("Y")]) ?></p>
            </div>
        </div>
    </div>
</footer>


<?= $this->element('modals') ?>

<?= $this->Html->script('jquery-1.11.0') ?>
<?= $this->Html->script('bootstrap') ?>

<?= $this->Html->script('app') ?>
<?= $this->Html->script('form') ?>
<?= $this->Html->script('notification') ?>
<script>
    <?php if($isConnected) { ?>
    // Notifications
    let notification = new $.Notification({
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
        }
    });
    <?php } ?>

    // Config FORM/APP.JS

    let LIKE_URL = "<?= $this->Url->build(['_name' => 'news_like']) ?>";
    let DISLIKE_URL = "<?= $this->Url->build(['_name' => 'news_dislike']) ?>";

    let LOADING_MSG = "<?= __('GLOBAL__LOADING') ?>";
    let ERROR_MSG = "<?= __('GLOBAL__ERROR') ?>";
    let INTERNAL_ERROR_MSG = "<?= __('ERROR__INTERNAL_ERROR') ?>";
    let FORBIDDEN_ERROR_MSG = "<?= __('ERROR__FORBIDDEN') ?>"
    let SUCCESS_MSG = "<?= __('GLOBAL__SUCCESS') ?>";

    let CSRF_TOKEN = "<?= $csrfToken ?>";

    $(".navbar-collapse").css({maxHeight: ($(window).height() - 130) - $(".navbar-header").height() + "px"});
</script>

<?php if (isset($google_analytics) && !empty($google_analytics)) { ?>
    <script>
        (function (i, s, o, g, r, a, m) {
            i['GoogleAnalyticsObject'] = r;
            i[r] = i[r] || function () {
                (i[r].q = i[r].q || []).push(arguments)
            }, i[r].l = 1 * new Date();
            a = s.createElement(o),
                m = s.getElementsByTagName(o)[0];
            a.async = 1;
            a.src = g;
            m.parentNode.insertBefore(a, m)
        })(window, document, 'script', '//www.google-analytics.com/analytics.js', 'ga');

        ga('create', '<?= $google_analytics ?>', 'auto');
        ga('send', 'pageview');
    </script>
<?php } ?>
<?= (isset($configuration_end_code)) ? $configuration_end_code : '' ?>
</body>

</html>
