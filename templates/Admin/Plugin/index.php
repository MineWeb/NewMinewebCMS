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

                            foreach ($pluginList as $value) {
                                $lastVersion = $versions[$value->slug] ?? false;
                                ?>
                                <tr>
                                    <td><?= h($value->name) ?></td>
                                    <td><?= h($value->author) ?></td>
                                    <td><?= $this->Lang->date($value->DBinstall) ?></td>
                                    <td><?= h($value->version) ?></td>

                                    <td>
                                        <?=
                                        $value->loaded
                                            ? '<span class="label label-success">' . __('GLOBAL__YES') . '</span>'
                                            : '<span class="label label-danger">' . __('GLOBAL__NO') . '</span>'
                                        ?>
                                    </td>

                                    <td>
                                        <?=
                                        $value->active
                                            ? '<span class="label label-success">' . __('GLOBAL__ENABLED') . '</span>'
                                            : '<span class="label label-danger">' . __('GLOBAL__DISABLED') . '</span>'
                                        ?>
                                    </td>

                                    <td>
                                        <?php if ($value->active) { ?>
                                            <a
                                                href="<?= $this->Url->build(['_name' => 'admin_plugin_disable', $value->DBid]) ?>"
                                                class="btn btn-info disable"
                                            >
                                                <?= __('GLOBAL__DISABLE') ?>
                                            </a>
                                        <?php } else { ?>
                                            <a
                                                href="<?= $this->Url->build(['_name' => 'admin_plugin_enable', $value->DBid]) ?>"
                                                class="btn btn-info enable"
                                            >
                                                <?= __('GLOBAL__ENABLE') ?>
                                            </a>
                                        <?php } ?>

                                        <a
                                            onClick="confirmDel('<?= $this->Url->build(['_name' => 'admin_plugin_delete', $value->DBid]) ?>')"
                                            class="btn btn-danger delete"
                                        >
                                            <?= __('GLOBAL__DELETE') ?>
                                        </a>

                                        <?php if ($lastVersion && $value->version !== $lastVersion) { ?>
                                            <a
                                                <?php if (explode('.', $lastVersion)[0] > explode('.', $value->version)[0]) { ?>
                                                    data-warning-update
                                                <?php } ?>
                                                href="<?= $this->Url->build(['_name' => 'admin_plugin_update', $value->slug]) ?>"
                                                class="btn btn-warning update"
                                            >
                                                <?= __('GLOBAL__UPDATE') ?>
                                            </a>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>
                    <?php } else { ?>
                        <div class="alert alert-danger">
                            <?= __('PLUGIN__NONE_INSTALLED') ?>
                        </div>
                    <?php } ?>
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

                    if (!empty($free_plugins)) {
                        ?>
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
                            <?php foreach ($free_plugins as $value) { ?>
                                <tr plugin-slug="<?= h($value['slug']) ?>">
                                    <td><?= h($value['name']) ?></td>

                                    <td>
                                        <?php
                                        if ($value['free']) {
                                            echo isset($value['author']) ? h($value['author']) : '';
                                        } else {
                                            foreach ($value['contact'] as $contact) {
                                                if ($contact['type'] === 'discord') {
                                                    echo '<button class="btn btn-info" style="background-color: #7289da;border-color: #7289da;">Discord  ' . h($contact['value']) . '</button>';
                                                } elseif ($contact['type'] === 'email') {
                                                    echo '<button class="btn btn-info">Email  ' . h($contact['value']) . '</button>';
                                                } else {
                                                    echo '<button class="btn btn-warn">' . h($contact['value']) . '</button>';
                                                }
                                                echo '&nbsp;&nbsp;';
                                            }
                                        }
                                        ?>
                                    </td>

                                    <td>
                                        <?= isset($value['version']) ? h($value['version']) : __('PLUGIN__NEED_PURCHASE') ?>
                                    </td>

                                    <td>
                                        <?php if ($value['free']) { ?>
                                            <button
                                                class="btn btn-success install"
                                                data-slug="<?= h($value['slug']) ?>"
                                                data-url="<?= h($this->Url->build(['_name' => 'admin_plugin_install', $value['slug']])) ?>"
                                            >
                                                <?= __('PLUGIN__INSTALL') ?>
                                            </button>
                                        <?php } ?>
                                    </td>
                                </tr>
                            <?php } ?>
                            </tbody>
                        </table>

                    <?php } else { ?>
                        <div class="alert alert-danger">
                            <b><?= __('GLOBAL__ERROR') ?>:</b> <?= __('PLUGIN__NONE_AVAILABLE') ?>
                        </div>
                    <?php } ?>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
    document.querySelectorAll('a[data-warning-update]').forEach(function(element) {
        element.addEventListener('click', function(event) {
            event.preventDefault();
            if (confirm("<?= __('UPDATE__MAJOR_WARNING_EXTENSION') ?>")) {
                window.location = element.getAttribute('href');
            }
        });
    });

    document.querySelectorAll('.install').forEach(function(button) {
        button.addEventListener('click', function(event) {
            event.preventDefault();

            let url = button.dataset.url;
            let ajaxBox = document.querySelector('.ajax');

            if (!url) {
                return;
            }

            document.querySelectorAll('.install, .update, .delete, .enable, .disable').forEach(function(btn) {
                btn.classList.add('disabled');
            });

            button.textContent = '<?= __('PLUGIN__INSTALL_LOADING') ?>...';

            ajaxBox.innerHTML = '<div class="alert alert-info"><?= __('PLUGIN__INSTALL_LOADING') ?>...</div>';

            fetch(url)
                .then(function(response) {
                    return response.json();
                })
                .then(function(data) {
                    if (data && data.statut === 'success') {
                        ajaxBox.innerHTML = '<div class="alert alert-success"><b><?= __('GLOBAL__SUCCESS') ?>:</b> <?= __('PLUGIN__INSTALL_SUCCESS') ?></div>';
                        window.location.reload();
                        return;
                    }

                    if (data && data.statut === 'error') {
                        ajaxBox.innerHTML = '<div class="alert alert-danger"><b><?= __('GLOBAL__ERROR') ?>:</b> ' + data.msg + '</div>';
                    } else {
                        ajaxBox.innerHTML = '<div class="alert alert-danger"><b><?= __('GLOBAL__ERROR') ?>:</b> <?= addslashes(__('ERROR__INTERNAL_ERROR')) ?></div>';
                    }

                    document.querySelectorAll('.install, .update, .delete, .enable, .disable').forEach(function(btn) {
                        btn.classList.remove('disabled');
                    });

                    button.textContent = '<?= __('PLUGIN__INSTALL') ?>';
                })
                .catch(function() {
                    ajaxBox.innerHTML = '<div class="alert alert-danger"><b><?= __('GLOBAL__ERROR') ?>:</b> <?= addslashes(__('ERROR__INTERNAL_ERROR')) ?></div>';

                    document.querySelectorAll('.install, .update, .delete, .enable, .disable').forEach(function(btn) {
                        btn.classList.remove('disabled');
                    });

                    button.textContent = '<?= __('PLUGIN__INSTALL') ?>';
                });
        });
    });
</script>
