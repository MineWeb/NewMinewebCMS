<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">

                            <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-share-alt mr-2"></i><?= __('SOCIAL__HOME') ?>
                                </h3>

                                <span class="badge badge-light border">
                                    <i class="fas fa-list mr-1"></i><?= is_countable($social_buttons) ? count($social_buttons) : 0 ?> <?= __('TABLE__ITEMS') ?>
                                </span>
                            </div>

                            <a class="btn btn-primary btn-sm" href="<?= $this->Url->build(['_name' => 'admin_social_add']) ?>">
                                <i class="fas fa-plus mr-2"></i><?= __('SOCIAL__ADD') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <div class="alert alert-light border d-flex align-items-start mb-3">
                                <i class="fas fa-info-circle mt-1 mr-2"></i>
                                <div class="text-sm mb-0"><?= __('SOCIAL__ORDER_HELP') ?></div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th style="width: 44px;"></th>
                                        <th><?= __('SOCIAL__BUTTON_TITLE') ?></th>
                                        <th><?= __('SOCIAL__BUTTON_TYPE') ?></th>
                                        <th><?= __('SOCIAL__BUTTON_URL') ?></th>
                                        <th><?= __('SOCIAL__BUTTON_COLOR') ?></th>
                                        <th class="text-right" style="width: 1%; white-space: nowrap;"><?= __('GLOBAL__ACTIONS') ?></th>
                                    </tr>
                                    </thead>

                                    <tbody id="sortable">
                                    <?php if (empty($social_buttons) || count($social_buttons) === 0) { ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="far fa-folder-open mr-2"></i><?= __('TABLE__NO_RESULT') ?>
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php $i = 0;
                                        foreach ($social_buttons as $value) {
                                            $i++; ?>
                                            <tr class="item" style="cursor: move;" id="<?= (int)$value['id'] ?>-<?= $i ?>">
                                                <td class="align-middle text-muted text-center">
                                                    <span class="js-drag-handle d-inline-flex align-items-center justify-content-center"
                                                          style="width: 28px; height: 28px; cursor: grab;"
                                                          title="<?= h(__('GLOBAL__MOVE')) ?>">
                                                        <i class="fas fa-grip-vertical"></i>
                                                    </span>
                                                </td>

                                                <td class="align-middle">
                                                    <div class="font-weight-bold"><?= h((string)$value['title']) ?></div>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if (!empty($value['extra'])) { ?>
                                                        <?php if (strpos((string)$value['extra'], 'fa-') !== false) { ?>
                                                            <span class="text-muted" title="<?= h((string)$value['extra']) ?>">
                                                                <i class="<?= h((string)$value['extra']) ?> fa-2x"></i>
                                                            </span>
                                                        <?php } else { ?>
                                                            <a href="<?= h((string)$value['extra']) ?>" target="_blank" rel="noopener" class="text-decoration-none">
                                                                <img
                                                                    src="<?= h((string)$value['extra']) ?>"
                                                                    alt="<?= h(__('SOCIAL__BUTTON_IMG_ALT') . (string)$value['title']) ?>"
                                                                    style="height: 2.2em;"
                                                                >
                                                            </a>
                                                        <?php } ?>
                                                    <?php } else { ?>
                                                        <span class="text-muted"><?= __('SOCIAL__EMPTY_TYPE') ?></span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle">
                                                    <?php $u = (string)($value['url'] ?? ''); ?>
                                                    <?php if ($u !== '') { ?>
                                                        <a href="<?= h($u) ?>" target="_blank" rel="noopener"
                                                           class="text-truncate d-inline-block"
                                                           style="max-width: 520px;"
                                                           title="<?= h($u) ?>">
                                                            <i class="fas fa-link mr-1"></i><?= h($u) ?>
                                                        </a>
                                                    <?php } else { ?>
                                                        <span class="text-muted">-</span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle">
                                                    <?php $c = (string)($value['color'] ?? ''); ?>
                                                    <div class="d-flex align-items-center">
                                                        <span class="d-inline-block mr-2 border"
                                                              style="width: 18px; height: 18px; border-radius: 4px; background: <?= h($c) ?>;"></span>
                                                        <span class="text-muted"><?= h($c) ?></span>
                                                    </div>
                                                </td>

                                                <td class="align-middle text-right text-nowrap">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="<?= h(__('GLOBAL__ACTIONS')) ?>">
                                                        <a href="<?= $this->Url->build(['_name' => 'admin_social_edit', (int)$value['id']]) ?>" class="btn btn-info">
                                                            <i class="fas fa-edit mr-1"></i><?= __('GLOBAL__EDIT') ?>
                                                        </a>
                                                        <a href="#"
                                                           class="btn btn-danger"
                                                           onclick="confirmDel('<?= $this->Url->build(['_name' => 'admin_social_delete', (int)$value['id']]) ?>'); return false;">
                                                            <i class="fas fa-trash mr-1"></i><?= __('GLOBAL__DELETE') ?>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3 d-flex align-items-center justify-content-between">
                                <div class="ajax-msg"></div>

                                <button id="save" class="btn btn-success btn-sm" disabled="disabled">
                                    <i class="fas fa-check mr-2"></i><?= __('SOCIAL__SAVE_SUCCESS') ?>
                                </button>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let sortableBody = document.getElementById('sortable');
        let saveButton = document.getElementById('save');
        let ajaxMsg = document.querySelector('.ajax-msg');

        if (!sortableBody) {
            return;
        }

        function showError(message) {
            if (!ajaxMsg) {
                return;
            }
            ajaxMsg.innerHTML =
                '<div class="alert alert-danger mb-0">' +
                '<i class="fas fa-times-circle mr-2"></i>' +
                '<b><?= addslashes(__('GLOBAL__ERROR')) ?> :</b> ' + String(message || '') +
                '</div>';
        }

        function clearMsg() {
            if (ajaxMsg) {
                ajaxMsg.innerHTML = '';
            }
        }

        function setButtonLoading() {
            if (!saveButton) return;
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><?= addslashes(__('SOCIAL__SAVE_IN_PROGRESS')) ?>';
        }

        function setButtonSuccess() {
            if (!saveButton) return;
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-check mr-2"></i><?= addslashes(__('SOCIAL__SAVE_SUCCESS')) ?>';
        }

        function buildOrderArray() {
            let rows = sortableBody.querySelectorAll('tr.item[id]');
            let ids = [];
            rows.forEach(function (row) {
                let rawId = row.id || '';
                let idParts = rawId.split('-');
                let id = idParts[0];
                if (id) {
                    ids.push(id);
                }
            });
            return ids;
        }

        function saveOrder() {
            if (sortableBody.querySelector('td[colspan]')) {
                return;
            }

            clearMsg();
            setButtonLoading();

            let ids = buildOrderArray();

            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

            fetch('<?= $this->Url->build(['_name' => 'admin_social_save_ajax']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify({ order: ids })
            })
                .then(function (r) {
                    return r.json().catch(function () {
                        return { status: false, messages: '<?= addslashes(__('ERROR__INTERNAL_ERROR')) ?>' };
                    });
                })
                .then(function (data) {
                    if (data && data.status) {
                        setButtonSuccess();
                    } else {
                        setButtonSuccess();
                        showError((data && data.messages) ? data.messages : '<?= addslashes(__('ERROR__INTERNAL_ERROR')) ?>');
                    }
                })
                .catch(function () {
                    setButtonSuccess();
                    showError('<?= addslashes(__('ERROR__INTERNAL_ERROR')) ?>');
                });
        }

        if (sortableBody.querySelectorAll('tr.item[id]').length > 0) {
            Sortable.create(sortableBody, {
                animation: 150,
                handle: '.js-drag-handle',
                filter: 'a,button,input,select,textarea,label',
                preventOnFilter: false,
                onEnd: function () {
                    saveOrder();
                }
            });

            sortableBody.addEventListener('mousedown', function (e) {
                let a = e.target && e.target.closest ? e.target.closest('a') : null;
                if (a) {
                    e.stopPropagation();
                }
            }, true);
        }
    });
</script>


