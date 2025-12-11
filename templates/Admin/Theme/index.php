<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('THEME__LIST') ?></h3>
                </div>
                <div class="card-body">

                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th><?= __('GLOBAL__NAME') ?></th>
                            <th><?= __('GLOBAL__AUTHOR') ?></th>
                            <th><?= __('GLOBAL__VERSION') ?></th>
                            <th><?= __('GLOBAL__STATUS') ?></th>
                            <th><?= __('THEME__SUPPORTED_STATUS') ?></th>
                            <th><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <tr>
                            <td>Bootstrap</td>
                            <td>Eywek</td>
                            <td>N/A</td>
                            <td>
                                <?php
                                if ('default' == $Configuration->getKey('theme')) {
                                    echo '<span class="label label-success">' . __('GLOBAL__ENABLED') . '</span>';
                                } else {
                                    echo '<span class="label label-danger">' . __('GLOBAL__DISABLED') . '</span>';
                                }
                                ?>
                            </td>
                            <td>
                                <span class="label label-success"><?= __('GLOBAL__YES') ?></span>
                            </td>
                            <td>
                                <?php if ('default' != $Configuration->getKey('theme')) { ?>
                                    <a href="<?= Router::url(['_name' => 'admin_theme_enable', 'pass' => ['default']]) ?>"
                                       class="btn btn-success"><?= __('GLOBAL__ENABLE') ?></a>
                                <?php } ?>
                                <a href="<?= Router::url(['_name' => 'admin_theme_custom', 'pass' => ['default']]) ?>"
                                   class="btn btn-info"><?= __('THEME__CUSTOMIZATION') ?></a>
                                <a href="<?= Router::url(['_name' => 'admin_theme_custom_files', 'pass' => ['default']]) ?>"
                                   class="btn btn-primary"><?= __('THEME__CUSTOM_FILES') ?></a>
                            </td>
                        </tr>
                        <?php if (!empty($themesInstalled)) { ?>
                            <?php foreach ($themesInstalled as $key => $value) { ?>
                                <tr>
                                    <td><?= $value->name ?></td>
                                    <td><?= $value->author ?></td>
                                    <td><?= $value->version ?></td>
                                    <td>
                                        <?php
                                        if ($value->slug == $Configuration->getKey('theme')) {
                                            echo '<span class="label label-success">' . __('GLOBAL__ENABLED') . '</span>';
                                        } else {
                                            echo '<span class="label label-danger">' . __('GLOBAL__DISABLED') . '</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($value->supported) {
                                            echo '<span class="label label-success">' . __('GLOBAL__YES') . '</span>';
                                        } else {
                                            echo '<span class="label label-danger">' . __('GLOBAL__NO') . '</span><br>';
                                            echo '<small><i>' . __('THEME__SUPPORTED_EXPLAIN') . '</i></small>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <?php if ($value->slug != $Configuration->getKey('theme') && $value->valid) { ?>
                                            <a href="<?= Router::url(['_name' => 'admin_theme_enable', 'pass' => [$value->slug]]) ?>"
                                               class="btn btn-success"><?= __('GLOBAL__ENABLE') ?></a>
                                        <?php } ?>
                                        <a onClick="confirmDel('<?= Router::url(['_name' => 'admin_theme_delete', 'pass' => [$value->slug]]) ?>')"
                                           class="btn btn-danger"><?= __('GLOBAL__DELETE') ?></a>
                                        <?php if (file_exists(ROOT . '/templates/Themed/' . $value->slug . '/Config/view.php')) { ?>
                                            <a href="<?= Router::url(['_name' => 'admin_theme_custom', 'pass' => [$value->slug]]) ?>"
                                               class="btn btn-info"><?= __('THEME__CUSTOMIZATION') ?></a>
                                        <?php } ?>
                                        <a href="<?= Router::url(['_name' => 'admin_theme_custom_files', 'pass' => [$value->slug]]) ?>"
                                           class="btn btn-primary"><?= __('THEME__CUSTOM_FILES') ?></a>
                                        <?php if (isset($value->lastVersion)) { ?>
                                            <?php if ($value->version !== $value->lastVersion) { ?>
                                                <a <?= (explode('.', $value->lastVersion)[0] > explode('.', $value->version)[0] ? 'data-warning-update' : '') ?>
                                                    href="<?= Router::url(['_name' => 'admin_theme_update', 'pass' => [$value->slug]]) ?>"
                                                    class="btn btn-warning"><?= __('GLOBAL__UPDATE') ?></a>
                                            <?php } ?>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                        <?php } ?>
                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('THEME__AVAILABLE') ?></h3>
                </div>
                <div class="card-body">

                    <?php if (!empty($themesAvailable)) { ?>
                        <table class="table table-bordered">
                            <thead>
                            <tr>
                                <th><?= __('GLOBAL__NAME') ?></th>
                                <th><?= __('GLOBAL__AUTHOR') ?></th>
                                <th><?= __('GLOBAL__VERSION') ?></th>
                                <th><?= __('GLOBAL__ACTIONS') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($themesAvailable as $key => $value) { ?>
                                <tr>
                                    <td><?= $value['name'] ?></td>
                                    <td>
                                        <?php if ($value['free']) {
                                            echo isset($value['author']) ? $value['author'] : '';
                                        } else {
                                            foreach ($value['contact'] as $contact) {
                                                if ($contact['type'] == 'discord') {
                                                    echo '<button class="btn btn-info" style="background-color: #7289da;border-color: #7289da;">Discord - ' . $contact['value'] . '</button>';
                                                } else if ($contact['type'] === 'email') {
                                                    echo '<button class="btn btn-info">Email - ' . $contact['value'] . '</button>';
                                                } else {
                                                    echo '<button class="btn btn-warn">' . $contact['value'] . '</button>';
                                                }
                                                echo '&nbsp;&nbsp;';
                                            }
                                        }
                                        ?>
                                    </td>
                                    <td><?= isset($value['version']) ? $value['version'] : __('THEME__NEED_PURCHASE') ?></td>
                                    <td>
                                        <?php if ($value['free']): ?>
                                            <a href="<?= Router::url(['_name' => 'admin_theme_install', 'pass' => [$value['slug']]]) ?>"
                                               class="btn btn-success"><?= __('INSTALL__INSTALL') ?></a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    <?php } else { ?>
                        <div class="alert alert-danger"><b><?= __('GLOBAL__ERROR') ?>
                                : </b><?= __('THEME__NONE_AVAILABLE') ?></div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</section>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        let links = document.querySelectorAll('a[data-warning-update]')
        if (!links.length) {
            return
        }
        for (let i = 0; i < links.length; i++) {
            links[i].addEventListener('click', function (e) {
                e.preventDefault()
                let href = this.getAttribute('href')
                if (!href) {
                    return
                }
                if (confirm("<?= __('UPDATE__MAJOR_WARNING_EXTENSION') ?>")) {
                    window.location.href = href
                }
            })
        }
    })
</script>
