<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="card">

                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NAVBAR__TITLE') ?></h3>
                </div>

                <div class="card-body">

                    <a
                        class="btn btn-large btn-block btn-primary"
                        href="<?= $this->Url->build(['_name' => 'admin_navbar_add']) ?>"
                    >
                        <?= __('NAVBAR__ADD_LINK') ?>
                    </a>

                    <hr>

                    <table class="table table-responsive-sm table-bordered"
                           style="table-layout: fixed; word-wrap: break-word;">
                        <thead>
                        <tr>
                            <th><?= __('GLOBAL__NAME') ?></th>
                            <th><?= __('URL') ?></th>
                            <th><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody id="sortable">
                        <?php $i = 0;
                        foreach ($navbars as $value): $i++; ?>
                            <tr id="<?= (int)$value['id'] . '-' . $i ?>" style="cursor:move;">
                                <td>
                                    <?php if (!empty($value['icon'])): ?>
                                        <i class="<?= strpos($value['icon'], 'fa-') !== false ? h($value['icon']) : 'fa fa-' . h($value['icon']) ?>"></i>
                                    <?php endif; ?>
                                    <?= h($value['name']) ?>
                                </td>

                                <?php if ($value['url'] !== '#' && $value['url'] !== false): ?>
                                    <td>
                                        <a href="<?= h($value['url']) ?>"><?= h($value['url']) ?></a>
                                    </td>
                                <?php elseif ($value['url'] === false): ?>
                                    <td>
                                        <span class="label label-danger"><?= __('PLUGIN__ERROR_UNINSTALLED') ?></span>
                                    </td>
                                <?php else: ?>
                                    <td>
                                        <a href="#"><?= __('NAVBAR__LINK_TYPE_DROPDOWN') ?></a>
                                    </td>
                                <?php endif; ?>

                                <td>
                                    <a
                                        class="btn btn-info"
                                        href="<?= $this->Url->build(['_name' => 'admin_navbar_edit', (int)$value['id']]) ?>"
                                    >
                                        <?= __('GLOBAL__EDIT') ?>
                                    </a>

                                    <a
                                        class="btn btn-danger"
                                        onClick="confirmDel('<?= $this->Url->build(['_name' => 'admin_navbar_delete', (int)$value['id']]) ?>')"
                                    >
                                        <?= __('GLOBAL__DELETE') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>

                    <br>

                    <div class="ajax-msg"></div>

                    <button
                        id="save"
                        class="btn btn-success float-right active"
                        disabled="disabled"
                    >
                        <?= __('NAVBAR__SAVE_SUCCESS') ?>
                    </button>

                </div>

            </div>

        </div>
    </div>
</section>

<style>
    li {
        list-style-type: none;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sortable = document.getElementById('sortable');
        var saveButton = document.getElementById('save');
        var ajaxMsg = document.querySelector('.ajax-msg');
        var draggedRow = null;

        if (!sortable) {
            return;
        }

        function initDraggableRows() {
            var rows = sortable.querySelectorAll('tr');
            rows.forEach(function (row) {
                row.setAttribute('draggable', 'true');

                row.addEventListener('dragstart', function (event) {
                    draggedRow = row;
                    if (event.dataTransfer) {
                        event.dataTransfer.effectAllowed = 'move';
                    }
                });

                row.addEventListener('dragover', function (event) {
                    event.preventDefault();
                    if (!draggedRow || draggedRow === row) {
                        return;
                    }

                    var rect = row.getBoundingClientRect();
                    var offset = event.clientY - rect.top;
                    var middle = rect.height / 2;

                    if (offset > middle) {
                        sortable.insertBefore(draggedRow, row.nextSibling);
                    } else {
                        sortable.insertBefore(draggedRow, row);
                    }
                });

                row.addEventListener('drop', function (event) {
                    event.preventDefault();
                    draggedRow = null;
                    saveOrder();
                });

                row.addEventListener('dragend', function () {
                    draggedRow = null;
                });
            });
        }

        function buildOrderParams() {
            var params = new URLSearchParams();
            var rows = sortable.querySelectorAll('tr');

            rows.forEach(function (row) {
                var rawId = row.id || '';
                var idParts = rawId.split('-');
                var id = idParts[0] || '';
                if (id) {
                    params.append('navbar_order[]', id);
                }
            });

            return params;
        }

        function clearAjaxMsg() {
            if (ajaxMsg) {
                ajaxMsg.innerHTML = '';
            }
        }

        function showAjaxError(message) {
            if (!ajaxMsg) {
                return;
            }

            ajaxMsg.innerHTML =
                '<div class="alert alert-danger">' +
                '<b><?= __('GLOBAL__ERROR') ?> :</b> ' +
                message +
                '</div>';
        }

        function showAjaxSuccess(message) {
            if (!ajaxMsg) {
                return;
            }

            ajaxMsg.innerHTML =
                '<div class="alert alert-success">' +
                '<b><?= __('GLOBAL__SUCCESS') ?> :</b> ' +
                message +
                '</div>';
        }

        function saveOrder() {
            if (!saveButton) {
                return;
            }

            saveButton.textContent = '<?= __('NAVBAR__SAVE_IN_PROGRESS') ?>';

            var params = buildOrderParams();

            fetch('<?= $this->Url->build(['_name' => 'admin_navbar_save_ajax']) ?>', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    clearAjaxMsg();

                    if (data && data.statut) {
                        saveButton.textContent = '<?= __('NAVBAR__SAVE_SUCCESS') ?>';
                    } else if (data && data.statut === false) {
                        saveButton.textContent = '<?= __('NAVBAR__SAVE_SUCCESS') ?>';
                        showAjaxError(data.msg || '<?= __('ERROR__INTERNAL_ERROR') ?>');
                    } else {
                        saveButton.textContent = '<?= __('NAVBAR__SAVE_SUCCESS') ?>';
                        showAjaxError('<?= __('ERROR__INTERNAL_ERROR') ?>');
                    }
                })
                .catch(function () {
                    saveButton.textContent = '<?= __('NAVBAR__SAVE_SUCCESS') ?>';
                    showAjaxError('<?= __('ERROR__INTERNAL_ERROR') ?>');
                });
        }

        initDraggableRows();
    });
</script>
