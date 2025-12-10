<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="ajax"></div>

            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('PLUGIN__LIST') ?></h3>
                </div>

                <div class="card-body">

                    <?php
                    $pluginList = $EyPlugin->pluginsLoaded;
                    if (!empty($pluginList)) {
                        ?>
                        <table class="table table-bordered" id="plugin-installed">
                            <thead>
                            <tr>
                                <th><?= __('GLOBAL__NAME') ?></th>
                                <th><?= __('GLOBAL__AUTHOR') ?></th>
                                <th><?= __('GLOBAL__CREATED') ?></th>
                                <th><?= __('GLOBAL__VERSION') ?></th>
                                <th><?= __('PLUGIN__LOADED') ?></th>
                                <th><?= __('GLOBAL__STATUS') ?></th>
                                <th><?= __('GLOBAL__ACTIONS') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php
                            $versions = $EyPlugin->getPluginsLastVersion(array_map(function ($plugin) {
                                return $plugin->slug;
                            }, (array)$pluginList));
                            foreach ($pluginList as $key => $value) {
                                ?>
                                <tr>
                                    <td><?= $value->name ?></td>
                                    <td><?= $value->author ?></td>
                                    <td><?= $this->Lang->date($value->DBinstall) ?></td>
                                    <td><?= $value->version ?></td>
                                    <td>
                                        <?= ($value->loaded) ? '<span class="label label-success">' . __('GLOBAL__YES') . '</span>' : '<span class="label label-danger">' . __('GLOBAL__NO') . '</span>' ?>
                                    </td>
                                    <td>
                                        <?= ($value->active) ? '<span class="label label-success">' . __('GLOBAL__ENABLED') . '</span>' : '<span class="label label-danger">' . __('GLOBAL__DISABLED') . '</span>' ?>
                                    </td>
                                    <td>
                                        <?php if ($value->active) { ?>
                                            <a href="<?= Router::url(['controller' => 'plugin', 'action' => 'disable/' . $value->DBid, 'admin' => true]) ?>"
                                               class="btn btn-info disable"><?= __('GLOBAL__DISABLE') ?></a>
                                        <?php } else { ?>
                                            <a href="<?= Router::url(['controller' => 'plugin', 'action' => 'enable/' . $value->DBid, 'admin' => true]) ?>"
                                               class="btn btn-info enable"><?= __('GLOBAL__ENABLE') ?></a>
                                        <?php } ?>
                                        <a onClick="confirmDel('<?= $this->Html->url(['controller' => 'plugin', 'action' => 'delete/' . $value->DBid, 'admin' => true]) ?>')"
                                           class="btn btn-danger delete"><?= __('GLOBAL__DELETE') ?></a>
                                        <?php
                                        $lastVersion = (isset($versions[$value->slug])) ? $versions[$value->slug] : false;
                                        if ($lastVersion && $value->version != $lastVersion) { ?>
                                            <a <?= (explode('.', $lastVersion)[0] > explode('.', $value->version)[0] ? 'data-warning-update' : '') ?>
                                                    href="<?= Router::url(['controller' => 'plugin', 'action' => 'update', $value->slug, 'admin' => true]) ?>"
                                                    class="btn btn-warning update"><?= __('GLOBAL__UPDATE') ?></a> <!-- ICI -->
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    <?php } else {
                        echo '<div class="alert alert-danger">' . __('PLUGIN__NONE_INSTALLED') . '</div>';
                    } ?>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('PLUGIN__AVAILABLE') ?></h3>
                </div>
                <div class="card-body">
                    <?php
                    $free_plugins = $EyPlugin->getFreePlugins(true, true);
                    if (!empty($free_plugins)) { ?>
                        <table class="table table-bordered" id="plugin-not-installed">
                            <thead>
                            <tr>
                                <th><?= __('GLOBAL__NAME') ?></th>
                                <th><?= __('GLOBAL__AUTHOR') ?></th>
                                <th><?= __('GLOBAL__VERSION') ?></th>
                                <th><?= __('GLOBAL__ACTIONS') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            <?php foreach ($free_plugins as $key => $value) {
                                ?>
                                <tr plugin-slug="<?= $value['slug'] ?>">
                                    <td><?= $value['name'] ?></td>
                                    <td>
                                        <?php if ($value['free']) {
                                            echo isset($value['author']) ? $value['author'] : '';
                                        } else { // display contact
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
                                    <td><?= isset($value['version']) ? $value['version'] : __('PLUGIN__NEED_PURCHASE') ?></td>
                                    <td>
                                        <?php if ($value['free']): ?>
                                            <btn class="btn btn-success install"
                                                 slug="<?= $value['slug'] ?>"><?= __('PLUGIN__INSTALL') ?></btn>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    <?php } else { ?>
                        <div class="alert alert-danger"><b><?= __('GLOBAL__ERROR') ?>
                                : </b><?= __('PLUGIN__NONE_AVAILABLE') ?></div>
                    <?php } ?>

                </div>
            </div>
        </div>
    </div>
</section>
<script type="text/javascript">

    $('a[data-warning-update]').on('click', function (e) {
        e.preventDefault();
        if (confirm("<?= __('UPDATE__MAJOR_WARNING_EXTENSION') ?>"))
            window.location = $(this).attr('href');
    });

    $('.install').click(function (e) {
        e.preventDefault();

        var slug = $(this).attr('slug');

        var btn = $(this);

        if (slug !== undefined) {

            // Désactivation de toute action
            $('.install').each(function (e) {
                $(this).addClass('disabled');
            });
            $('.update').each(function (e) {
                $(this).addClass('disabled');
            });
            $('.delete').each(function (e) {
                $(this).addClass('disabled');
            });
            $('.enable').each(function (e) {
                $(this).addClass('disabled');
            });
            $('.disable').each(function (e) {
                $(this).addClass('disabled');
            });

            // Mise à jour du texte sur le bouton
            $(this).html('<?= __('PLUGIN__INSTALL_LOADING') ?>...');

            // On préviens l'utilisateur avec un message plus clair
            $('.ajax').empty().html('<div class="alert alert-info"><?= __('PLUGIN__INSTALL_LOADING') ?>...</b></div>').fadeIn(500);

            // On lance la requête
            $.get('<?= Router::url(['action' => 'install', 'admin' => true]) ?>/' + slug, function (data) {
                if (typeof data != 'object') {
                    data = JSON.parse(data);
                }
                if (data !== false) {

                    if (data.statut == "success") {
                        // on met le message
                        $('.ajax').empty().html('<div class="alert alert-success"><b><?= __('GLOBAL__SUCCESS') ?> :</b> <?= __('PLUGIN__INSTALL_SUCCESS') ?></div>').fadeIn(500);

                        // on bouge le plugin dans le tableau dans les plugins installés
                        $('table#plugin-not-installed').find('tr[plugin-slug="' + slug + '"]').slideUp(250);

                        var tr = '';
                        tr += '<tr>';
                        tr += '<td>' + data.plugin.name + '</td>';
                        tr += '<td>' + data.plugin.author + '</td>';
                        tr += '<td>' + data.plugin.dateformatted + '</td>';
                        tr += '<td>' + data.plugin.version + '</td>';
                        tr += '<td><span class="label label-success"><?= __('GLOBAL__YES') ?></span></td>';
                        tr += '<td><span class="label label-success"><?= __('GLOBAL__ENABLED') ?></span></td>';
                        tr += '<td>';
                        tr += '<a href="<?= Router::url(['action' => 'disable', 'admin' => true]) ?>/' + data.plugin.DBid + '" class="btn btn-info"><?= __('GLOBAL__DISABLED') ?></a>';
                        tr += "\n";
                        tr += '<a onClick="confirmDel(\'<?= Router::url(['controller' => 'plugin', 'action' => 'delete', 'admin' => true]) ?>/' + data.plugin.DBid + '\')" class="btn btn-danger"><?= __('GLOBAL__DELETE') ?></a>';
                        tr += '</td>';
                        tr += '</tr>';

                        $('table#plugin-installed tr:last').after(tr);

                    } else if (data.statut == "error") {
                        $('.ajax').empty().html('<div class="alert alert-danger"><b><?= __('GLOBAL__ERROR') ?> : </b>' + data.msg + '</div>').fadeIn(500);
                    } else {
                        $('.ajax').empty().html('<div class="alert alert-danger"><b><?= __('GLOBAL__ERROR') ?> : </b><?= addslashes(__('ERROR__INTERNAL_ERROR')) ?></div>').fadeIn(500);
                    }

                }

                // On annule les désactivations
                $('.install').each(function (e) {
                    $(this).removeClass('disabled');
                });
                $('.update').each(function (e) {
                    $(this).removeClass('disabled');
                });
                $('.delete').each(function (e) {
                    $(this).removeClass('disabled');
                });
                $('.enable').each(function (e) {
                    $(this).removeClass('disabled');
                });
                $('.disable').each(function (e) {
                    $(this).removeClass('disabled');
                });

                // On remet le texte par défaut
                btn.html('<?= __('PLUGIN__INSTALL') ?>');


            });


        }


    });
</script>
