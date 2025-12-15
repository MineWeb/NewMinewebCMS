<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title"><?= __('THEME__LIST') ?></h3>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover mb-0">
                                <thead>
                                <tr>
                                    <th><?= __('GLOBAL__NAME') ?></th>
                                    <th><?= __('GLOBAL__AUTHOR') ?></th>
                                    <th><?= __('GLOBAL__VERSION') ?></th>
                                    <th><?= __('GLOBAL__STATUS') ?></th>
                                    <th><?= __('THEME__SUPPORTED_STATUS') ?></th>
                                    <th class="text-right"><?= __('GLOBAL__ACTIONS') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php
                                $activeTheme = $this->Config->themeName();
                                $defaultActive = ($activeTheme === 'default');
                                ?>
                                <tr>
                                    <td class="align-middle">
                                        <strong>Bootstrap</strong>
                                        <div class="text-muted small">default</div>
                                    </td>
                                    <td class="align-middle">Eywek</td>
                                    <td class="align-middle">N/A</td>
                                    <td class="align-middle">
                                        <?php if ($defaultActive) { ?>
                                            <span class="badge badge-success"><?= __('GLOBAL__ENABLED') ?></span>
                                        <?php } else { ?>
                                            <span class="badge badge-secondary"><?= __('GLOBAL__DISABLED') ?></span>
                                        <?php } ?>
                                    </td>
                                    <td class="align-middle">
                                        <span class="badge badge-success"><?= __('GLOBAL__YES') ?></span>
                                    </td>
                                    <td class="align-middle text-right">
                                        <div class="btn-group">
                                            <?php if (!$defaultActive) { ?>
                                                <a href="<?= $this->Url->build(['_name' => 'admin_theme_enable', 'default']) ?>"
                                                   class="btn btn-sm btn-outline-success">
                                                    <?= __('GLOBAL__ENABLE') ?>
                                                </a>
                                            <?php } ?>
                                            <a href="<?= $this->Url->build(['_name' => 'admin_theme_custom', 'default']) ?>"
                                               class="btn btn-sm btn-outline-info">
                                                <?= __('THEME__CUSTOMIZATION') ?>
                                            </a>
                                            <a href="<?= $this->Url->build(['_name' => 'admin_theme_custom_files', 'default']) ?>"
                                               class="btn btn-sm btn-outline-primary">
                                                <?= __('THEME__CUSTOM_FILES') ?>
                                            </a>
                                        </div>
                                    </td>
                                </tr>

                                <?php if (!empty($themesInstalled)) { ?>
                                    <?php foreach ($themesInstalled as $value) {
                                        $slug = (string)$value->slug;
                                        $isActive = ($slug === $activeTheme);
                                        $supported = !empty($value->supported);
                                        $valid = !empty($value->valid);
                                        $hasUpdate = isset($value->lastVersion) && is_string($value->lastVersion) && $value->lastVersion !== '' && $value->version !== $value->lastVersion;
                                        $isMajorUpdate = $hasUpdate
                                            && isset(explode('.', $value->lastVersion)[0], explode('.', $value->version)[0])
                                            && explode('.', $value->lastVersion)[0] > explode('.', $value->version)[0];
                                        ?>
                                        <tr>
                                            <td class="align-middle">
                                                <strong><?= h((string)$value->name) ?></strong>
                                                <div class="text-muted small"><?= h($slug) ?></div>
                                            </td>
                                            <td class="align-middle"><?= h((string)$value->author) ?></td>
                                            <td class="align-middle">
                                                <?= h((string)$value->version) ?>
                                                <?php if ($hasUpdate) { ?>
                                                    <span class="badge badge-warning ml-2">
                                                        <?= __('GLOBAL__UPDATE') ?>: <?= h((string)$value->lastVersion) ?>
                                                    </span>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle">
                                                <?php if ($isActive) { ?>
                                                    <span class="badge badge-success"><?= __('GLOBAL__ENABLED') ?></span>
                                                <?php } else { ?>
                                                    <span class="badge badge-secondary"><?= __('GLOBAL__DISABLED') ?></span>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle">
                                                <?php if ($supported) { ?>
                                                    <span class="badge badge-success"><?= __('GLOBAL__YES') ?></span>
                                                <?php } else { ?>
                                                    <span class="badge badge-danger"><?= __('GLOBAL__NO') ?></span>
                                                    <div class="text-muted small"><?= __('THEME__SUPPORTED_EXPLAIN') ?></div>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle text-right">
                                                <div class="btn-group">
                                                    <?php if (!$isActive && $valid) { ?>
                                                        <a href="<?= $this->Url->build(['_name' => 'admin_theme_enable', $slug]) ?>"
                                                           class="btn btn-sm btn-outline-success">
                                                            <?= __('GLOBAL__ENABLE') ?>
                                                        </a>
                                                    <?php } ?>

                                                    <a href="#"
                                                       class="btn btn-sm btn-outline-danger js-confirm-delete"
                                                       data-url="<?= h($this->Url->build(['_name' => 'admin_theme_delete', $slug])) ?>">
                                                        <?= __('GLOBAL__DELETE') ?>
                                                    </a>

                                                    <?php if (file_exists(ROOT . '/templates/Themed/' . $slug . '/Config/view.php')) { ?>
                                                        <a href="<?= $this->Url->build(['_name' => 'admin_theme_custom', $slug]) ?>"
                                                           class="btn btn-sm btn-outline-info">
                                                            <?= __('THEME__CUSTOMIZATION') ?>
                                                        </a>
                                                    <?php } ?>

                                                    <a href="<?= $this->Url->build(['_name' => 'admin_theme_custom_files', $slug]) ?>"
                                                       class="btn btn-sm btn-outline-primary">
                                                        <?= __('THEME__CUSTOM_FILES') ?>
                                                    </a>

                                                    <?php if ($hasUpdate) { ?>
                                                        <a href="<?= $this->Url->build(['_name' => 'admin_theme_update', $slug]) ?>"
                                                           class="btn btn-sm btn-warning"
                                                            <?= $isMajorUpdate ? 'data-warning-update' : '' ?>>
                                                            <?= __('GLOBAL__UPDATE') ?>
                                                        </a>
                                                    <?php } ?>
                                                </div>
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

            <div class="col-12">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title"><?= __('THEME__AVAILABLE') ?></h3>
                    </div>

                    <div class="card-body p-0">
                        <?php if (!empty($themesAvailable)) { ?>
                            <div class="table-responsive">
                                <table class="table table-striped table-hover mb-0">
                                    <thead>
                                    <tr>
                                        <th><?= __('GLOBAL__NAME') ?></th>
                                        <th><?= __('GLOBAL__AUTHOR') ?></th>
                                        <th><?= __('GLOBAL__VERSION') ?></th>
                                        <th class="text-right"><?= __('GLOBAL__ACTIONS') ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ($themesAvailable as $value) {
                                        $slug = (string)($value['slug'] ?? '');
                                        $name = (string)($value['name'] ?? $slug);
                                        $author = (string)($value['author'] ?? '');
                                        $version = (string)($value['version'] ?? '');
                                        $isFree = isset($value['free']) ? (bool)$value['free'] : true;
                                        $purchaseUrl = isset($value['purchase_url']) && is_string($value['purchase_url']) ? trim($value['purchase_url']) : '';
                                        $market = isset($value['market']) && is_array($value['market']) ? $value['market'] : [];
                                        $marketName = isset($market['name']) ? (string)$market['name'] : '';
                                        ?>
                                        <tr>
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
                                                    <span class="badge badge-secondary"><?= __('THEME__NEED_PURCHASE') ?></span>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle">
                                                <?php if ($isFree) { ?>
                                                    <?= h($version) ?>
                                                <?php } else { ?>
                                                    <span class="text-muted"><?= __('THEME__NEED_PURCHASE') ?></span>
                                                <?php } ?>
                                            </td>
                                            <td class="align-middle text-right">
                                                <?php if ($isFree) { ?>
                                                    <a href="<?= $this->Url->build(['_name' => 'admin_theme_install', $slug]) ?>"
                                                       class="btn btn-sm btn-success">
                                                        <?= __('INSTALL__INSTALL') ?>
                                                    </a>
                                                <?php } elseif ($purchaseUrl !== '') { ?>
                                                    <a class="btn btn-sm btn-primary" target="_blank" rel="noopener noreferrer" href="<?= h($purchaseUrl) ?>">
                                                        <?= __('GLOBAL__BUY') ?>
                                                    </a>
                                                <?php } else { ?>
                                                    <span class="text-muted"><?= __('THEME__NEED_PURCHASE') ?></span>
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
                                    <?= __('THEME__NONE_AVAILABLE') ?>
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
    })();
</script>
