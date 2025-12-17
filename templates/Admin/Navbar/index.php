<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">

                            <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-bars mr-2"></i><?= __('NAVBAR__TITLE') ?>
                                </h3>

                                <span class="badge badge-light border">
                                    <i class="fas fa-list mr-1"></i><?= is_countable($navbars) ? count($navbars) : 0 ?> <?= __('TABLE__ITEMS') ?>
                                </span>
                            </div>


                            <a class="btn btn-primary btn-sm"
                               href="<?= $this->Url->build(['_name' => 'admin_navbar_add']) ?>">
                                <i class="fas fa-plus mr-2"></i><?= __('NAVBAR__ADD_LINK') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <div class="alert alert-light border d-flex align-items-start mb-3">
                                <i class="fas fa-info-circle mt-1 mr-2"></i>
                                <div class="text-sm mb-0">
                                    <?= __('NAVBAR__ORDER_HELP') ?>
                                </div>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th style="width: 44px;"></th>
                                        <th><?= __('GLOBAL__NAME') ?></th>
                                        <th><?= __('URL') ?></th>
                                        <th class="text-right"
                                            style="width: 1%; white-space: nowrap;"><?= __('GLOBAL__ACTIONS') ?></th>
                                    </tr>
                                    </thead>

                                    <tbody id="sortable">
                                    <?php if (empty($navbars) || count($navbars) === 0) { ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="far fa-folder-open mr-2"></i><?= __('TABLE__NO_RESULT') ?>
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php $i = 0;
                                        foreach ($navbars as $value) :
                                            $i++; ?>
                                            <tr id="<?= (int)$value['id'] . '-' . $i ?>">
                                                <td class="align-middle text-muted text-center">
                                                    <span
                                                        class="js-drag-handle d-inline-flex align-items-center justify-content-center"
                                                        style="width: 28px; height: 28px; cursor: grab;"
                                                        title="<?= h(__('GLOBAL__MOVE')) ?>">
                                                        <i class="fas fa-grip-vertical"></i>
                                                    </span>
                                                </td>

                                                <td class="align-middle">
                                                    <div class="d-flex align-items-center">
                                                        <?php if (!empty($value['icon'])) { ?>
                                                            <span class="mr-2 text-muted">
                                                                <i class="<?= strpos((string)$value['icon'], 'fa-') !== false ? h((string)$value['icon']) : 'fa fa-' . h((string)$value['icon']) ?>"></i>
                                                            </span>
                                                        <?php } ?>
                                                        <span
                                                            class="font-weight-bold"><?= h((string)$value['name']) ?></span>

                                                        <?php if (!empty($value['open_new_tab'])) { ?>
                                                            <span class="badge badge-light ml-2"
                                                                  title="<?= h(__('NAVBAR__OPEN_IN_NEW_TAB')) ?>">
                                                                <i class="fas fa-external-link-alt"></i>
                                                            </span>
                                                        <?php } ?>
                                                    </div>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if ($value['url'] !== '#' && $value['url'] !== false) { ?>
                                                        <a href="<?= h((string)$value['url']) ?>" target="_blank"
                                                           rel="noopener"
                                                           class="text-truncate d-inline-block js-row-link"
                                                           style="max-width: 520px;"
                                                           title="<?= h((string)$value['url']) ?>">
                                                            <i class="fas fa-link mr-1"></i><?= h((string)$value['url']) ?>
                                                        </a>
                                                    <?php } elseif ($value['url'] === false) { ?>
                                                        <span class="badge badge-danger">
                                                            <i class="fas fa-exclamation-triangle mr-1"></i><?= __('PLUGIN__ERROR_UNINSTALLED') ?>
                                                        </span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-layer-group mr-1"></i><?= __('NAVBAR__LINK_TYPE_DROPDOWN') ?>
                                                        </span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle text-right text-nowrap">
                                                    <div class="btn-group btn-group-sm" role="group"
                                                         aria-label="<?= h(__('GLOBAL__ACTIONS')) ?>">
                                                        <a class="btn btn-info"
                                                           href="<?= $this->Url->build(['_name' => 'admin_navbar_edit', (int)$value['id']]) ?>">
                                                            <i class="fas fa-edit mr-1"></i><?= __('GLOBAL__EDIT') ?>
                                                        </a>

                                                        <a class="btn btn-danger"
                                                           onClick="confirmDel('<?= $this->Url->build(['_name' => 'admin_navbar_delete', (int)$value['id']]) ?>')">
                                                            <i class="fas fa-trash mr-1"></i><?= __('GLOBAL__DELETE') ?>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <div class="mt-3 d-flex align-items-center justify-content-between">
                                <div class="ajax-msg"></div>

                                <button id="save" class="btn btn-success btn-sm" disabled="disabled">
                                    <i class="fas fa-check mr-2"></i><?= __('NAVBAR__SAVE_SUCCESS') ?>
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
            saveButton.innerHTML = '<i class="fas fa-spinner fa-spin mr-2"></i><?= addslashes(__('NAVBAR__SAVE_IN_PROGRESS')) ?>';
        }

        function setButtonSuccess() {
            if (!saveButton) return;
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="fas fa-check mr-2"></i><?= addslashes(__('NAVBAR__SAVE_SUCCESS')) ?>';
        }

        function buildOrderArray() {
            let rows = sortableBody.querySelectorAll('tr[id]');
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

            fetch('<?= $this->Url->build(['_name' => 'admin_navbar_save_ajax']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-Token': csrf
                },
                body: JSON.stringify({order: ids})
            })
                .then(function (r) {
                    return r.json().catch(function () {
                        return {status: false, messages: '<?= addslashes(__('ERROR__INTERNAL_ERROR')) ?>'};
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

        if (sortableBody.querySelectorAll('tr[id]').length > 0) {
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
                if (a && a.classList.contains('js-row-link')) {
                    e.stopPropagation();
                }
            }, true);
        }
    });
</script>
