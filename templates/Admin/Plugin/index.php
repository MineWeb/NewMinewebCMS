<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="alert alert-info d-none" id="ajaxBox"></div>
            </div>

            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><?= __('PLUGIN__LIST') ?></h3>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $pluginList = $this->Plugin->pluginsLoaded();
                        if (!empty($pluginList)) {
                            $versions = $this->Plugin->getPluginsLastVersion(array_map(
                                static fn($plugin) => $plugin->slug,
                                (array)$pluginList
                            ));
                            ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                    <tr>
                                        <th><?= __('GLOBAL__NAME') ?></th>
                                        <th><?= __('GLOBAL__AUTHOR') ?></th>
                                        <th><?= __('GLOBAL__CREATED') ?></th>
                                        <th><?= __('GLOBAL__VERSION') ?></th>
                                        <th><?= __('PLUGIN__LOADED') ?></th>
                                        <th><?= __('GLOBAL__STATUS') ?></th>
                                        <th class="text-right"><?= __('GLOBAL__ACTIONS') ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($pluginList as $value) {
                                        $lastVersion = $versions[$value->slug] ?? null;
                                        $hasUpdate = is_string($lastVersion) && $lastVersion !== '' && $value->version !== $lastVersion;
                                        $isMajorUpdate = $hasUpdate
                                            && isset(explode('.', $lastVersion)[0], explode('.', $value->version)[0])
                                            && explode('.', $lastVersion)[0] > explode('.', $value->version)[0];
                                        ?>
                                        <tr>
                                            <td class="align-middle">
                                                <strong><?= h((string)$value->name) ?></strong>
                                                <div class="text-muted small"><?= h((string)$value->slug) ?></div>
                                            </td>
                                            <td class="align-middle"><?= h((string)$value->author) ?></td>
                                            <td class="align-middle"><?= $this->Lang->date($value->DBinstall) ?></td>
                                            <td class="align-middle">
                                                <?= h((string)$value->version) ?>
                                                <?php if ($hasUpdate) { ?>
                                                    <span class="badge badge-warning ml-2">
                                                        <?= __('GLOBAL__UPDATE') ?>: <?= h((string)$lastVersion) ?>
                                                    </span>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle">
                                                <?php if (!empty($value->loaded)) { ?>
                                                    <span class="badge badge-success"><?= __('GLOBAL__YES') ?></span>
                                                <?php } else { ?>
                                                    <span class="badge badge-danger"><?= __('GLOBAL__NO') ?></span>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle">
                                                <?php if (!empty($value->active)) { ?>
                                                    <span class="badge badge-success"><?= __('GLOBAL__ENABLED') ?></span>
                                                <?php } else { ?>
                                                    <span class="badge badge-danger"><?= __('GLOBAL__DISABLED') ?></span>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle text-right">
                                                <div class="btn-group">
                                                    <?php if (!empty($value->active)) { ?>
                                                        <a href="<?= $this->Url->build(['_name' => 'admin_plugin_disable', $value->DBid]) ?>"
                                                           class="btn btn-sm btn-outline-secondary">
                                                            <?= __('GLOBAL__DISABLE') ?>
                                                        </a>
                                                    <?php } else { ?>
                                                        <a href="<?= $this->Url->build(['_name' => 'admin_plugin_enable', $value->DBid]) ?>"
                                                           class="btn btn-sm btn-outline-success">
                                                            <?= __('GLOBAL__ENABLE') ?>
                                                        </a>
                                                    <?php } ?>

                                                    <?php if ($hasUpdate) { ?>
                                                        <a href="<?= $this->Url->build(['_name' => 'admin_plugin_update', $value->slug]) ?>"
                                                           class="btn btn-sm btn-warning <?= $isMajorUpdate ? 'js-major-update' : '' ?>"
                                                            <?= $isMajorUpdate ? 'data-warning-update' : '' ?>>
                                                            <?= __('GLOBAL__UPDATE') ?>
                                                        </a>
                                                    <?php } ?>

                                                    <a href="#"
                                                       class="btn btn-sm btn-outline-danger js-confirm-delete"
                                                       data-url="<?= h($this->Url->build(['_name' => 'admin_plugin_delete', $value->DBid])) ?>">
                                                        <?= __('GLOBAL__DELETE') ?>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <div class="p-3">
                                <div class="alert alert-warning mb-0">
                                    <?= __('PLUGIN__NONE_INSTALLED') ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>

            <div class="col-12">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><?= __('PLUGIN__AVAILABLE') ?></h3>
                    </div>
                    <div class="card-body p-0">
                        <?php
                        $pluginsAvailable = $this->Plugin->getFreePlugins(true, true);
                        if (!empty($pluginsAvailable)) {
                            ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0" id="plugin-not-installed">
                                    <thead>
                                    <tr>
                                        <th><?= __('GLOBAL__NAME') ?></th>
                                        <th><?= __('GLOBAL__AUTHOR') ?></th>
                                        <th><?= __('GLOBAL__VERSION') ?></th>
                                        <th class="text-right"><?= __('GLOBAL__ACTIONS') ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($pluginsAvailable as $value) {
                                        $slug = (string)($value['slug'] ?? '');
                                        $name = (string)($value['name'] ?? $slug);
                                        $author = (string)($value['author'] ?? '');
                                        $version = (string)($value['version'] ?? '');
                                        $isFree = isset($value['free']) ? (bool)$value['free'] : true;
                                        $purchaseUrl = isset($value['purchase_url']) && is_string($value['purchase_url']) ? trim($value['purchase_url']) : '';
                                        $market = isset($value['market']) && is_array($value['market']) ? $value['market'] : [];
                                        $marketName = isset($market['name']) ? (string)$market['name'] : '';
                                        ?>
                                        <tr data-plugin-slug="<?= h($slug) ?>">
                                            <td class="align-middle">
                                                <strong><?= h($name) ?></strong>
                                                <?php if ($marketName !== '') { ?>
                                                    <div class="text-muted small"><?= h($marketName) ?></div>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle">
                                                <?php if ($isFree) { ?>
                                                    <?= h($author) ?>
                                                <?php } else { ?>
                                                    <span class="badge badge-secondary"><?= __('PLUGIN__NEED_PURCHASE') ?></span>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle">
                                                <?php if ($isFree) { ?>
                                                    <?= h($version) ?>
                                                <?php } else { ?>
                                                    <span class="text-muted"><?= __('PLUGIN__NEED_PURCHASE') ?></span>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle text-right">
                                                <?php if ($isFree) { ?>
                                                    <button
                                                        type="button"
                                                        class="btn btn-sm btn-success js-install"
                                                        data-url="<?= h($this->Url->build(['_name' => 'admin_plugin_install', $slug])) ?>"
                                                    >
                                                        <?= __('PLUGIN__INSTALL') ?>
                                                    </button>
                                                <?php } elseif ($purchaseUrl !== '') { ?>
                                                    <a class="btn btn-sm btn-primary" target="_blank" rel="noopener noreferrer" href="<?= h($purchaseUrl) ?>">
                                                        <?= __('GLOBAL__BUY') ?>
                                                    </a>
                                                <?php } else { ?>
                                                    <span class="text-muted"><?= __('PLUGIN__NEED_PURCHASE') ?></span>
                                                <?php } ?>
                                            </td>
                                        </tr>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php } else { ?>
                            <div class="p-3">
                                <div class="alert alert-warning mb-0">
                                    <?= __('PLUGIN__NONE_AVAILABLE') ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        const ajaxBox = document.getElementById('ajaxBox');

        function setAjax(type, html) {
            ajaxBox.classList.remove('d-none', 'alert-info', 'alert-success', 'alert-danger', 'alert-warning');
            ajaxBox.classList.add('alert-' + type);
            ajaxBox.innerHTML = html;
        }

        document.querySelectorAll('[data-warning-update]').forEach(function (element) {
            element.addEventListener('click', function (event) {
                event.preventDefault();
                if (confirm("<?= addslashes(__('UPDATE__MAJOR_WARNING_EXTENSION')) ?>")) {
                    window.location = element.getAttribute('href');
                }
            });
        });

        document.querySelectorAll('.js-confirm-delete').forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                const url = btn.dataset.url || '';
                if (!url) return;
                if (confirm("<?= addslashes(__('GLOBAL__DELETE')) ?> ?")) {
                    window.location = url;
                }
            });
        });

        document.querySelectorAll('.js-install').forEach(function (button) {
            button.addEventListener('click', function (event) {
                event.preventDefault();

                const url = button.dataset.url || '';
                if (!url) return;

                document.querySelectorAll('.js-install, .js-confirm-delete, [data-warning-update]').forEach(function (b) {
                    b.classList.add('disabled');
                    b.setAttribute('aria-disabled', 'true');
                });

                button.textContent = '<?= addslashes(__('PLUGIN__INSTALL_LOADING')) ?>...';
                setAjax('info', '<b><?= addslashes(__('PLUGIN__INSTALL_LOADING')) ?></b>...');

                fetch(url)
                    .then(function (response) { return response.json(); })
                    .then(function (data) {
                        const status = data && (data.statut || data.status) ? (data.statut || data.status) : null;
                        const msg = data && (data.msg || data.messages) ? (data.msg || data.messages) : null;

                        if (status === 'success') {
                            setAjax('success', '<b><?= addslashes(__('GLOBAL__SUCCESS')) ?>:</b> <?= addslashes(__('PLUGIN__INSTALL_SUCCESS')) ?>');
                            window.location.reload();
                            return;
                        }

                        setAjax('danger', '<b><?= addslashes(__('GLOBAL__ERROR')) ?>:</b> ' + (msg || '<?= addslashes(__('ERROR__INTERNAL_ERROR')) ?>'));

                        document.querySelectorAll('.js-install, .js-confirm-delete, [data-warning-update]').forEach(function (b) {
                            b.classList.remove('disabled');
                            b.removeAttribute('aria-disabled');
                        });

                        button.textContent = '<?= addslashes(__('PLUGIN__INSTALL')) ?>';
                    })
                    .catch(function () {
                        setAjax('danger', '<b><?= addslashes(__('GLOBAL__ERROR')) ?>:</b> <?= addslashes(__('ERROR__INTERNAL_ERROR')) ?>');

                        document.querySelectorAll('.js-install, .js-confirm-delete, [data-warning-update]').forEach(function (b) {
                            b.classList.remove('disabled');
                            b.removeAttribute('aria-disabled');
                        });

                        button.textContent = '<?= addslashes(__('PLUGIN__INSTALL')) ?>';
                    });
            });
        });
    })();
</script>
