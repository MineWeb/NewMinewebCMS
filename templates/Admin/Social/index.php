<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __("SOCIAL__HOME") ?></h3>
                </div>
                <div class="card-body">
                    <a class="btn btn-large btn-block btn-primary"
                       href="<?= $this->Url->build(['_name' => 'admin_social_add']) ?>">
                        <?= __('SOCIAL__ADD') ?>
                    </a>
                    <hr>
                    <table class="table table-responsive-sm table-bordered">
                        <thead>
                        <tr>
                            <th><?= __("SOCIAL__BUTTON_TITLE") ?></th>
                            <th><?= __("SOCIAL__BUTTON_TYPE") ?></th>
                            <th><?= __("SOCIAL__BUTTON_URL") ?></th>
                            <th><?= __("SOCIAL__BUTTON_COLOR") ?></th>
                            <th class="right"><?= __("GLOBAL__ACTIONS") ?></th>
                        </tr>
                        </thead>

                        <tbody id="sortable">
                        <?php $i = 0; foreach ($social_buttons as $key => $value) { $i++; ?>
                            <tr class="item" style="cursor:move;" id="<?= $value["id"] ?>-<?= $i ?>">
                                <td><?= $value['title'] ?></td>
                                <td>
                                    <?php if (!empty($value['extra'])) { ?>
                                        <a href="#" class="m-auto text-dark" title="<?= $value['extra'] ?>">
                                            <?php if (strpos($value['extra'], 'fa-') !== false) { ?>
                                                <i class="<?= $value['extra'] ?> fa-3x m-auto"></i>
                                            <?php } else { ?>
                                                <img
                                                    src="<?= $value['extra'] ?>"
                                                    class="m-auto"
                                                    alt="<?= __("SOCIAL__BUTTON_IMG_ALT") . $value['title'] ?>"
                                                    style="height: 3em;"
                                                >
                                            <?php } ?>
                                        </a>
                                    <?php } else {
                                        echo __("SOCIAL__EMPTY_TYPE");
                                    } ?>
                                </td>
                                <td>
                                    <a href="<?= $value['url'] ?>"><?= $value['url'] ?></a>
                                </td>
                                <td>
                                    <div
                                        class="socialbutton-color p-2 text-center"
                                        style="border: 1px solid #ccc;background-color: <?= $value['color'] ?>"
                                    >
                                        <?= $value['color'] ?>
                                        <div></div>
                                    </div>
                                </td>
                                <td>
                                    <a href="<?= $this->Url->build(['_name' => 'admin_social_edit', $value['id']]) ?>"
                                       class="btn btn-info">
                                        <?= __('GLOBAL__EDIT') ?>
                                    </a>
                                    <a onClick="confirmDel('<?= $this->Url->build(['_name' => 'admin_social_delete', $value['id']]) ?>')"
                                       class="btn btn-danger">
                                        <?= __('GLOBAL__DELETE') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                    <br>
                    <div class="ajax-msg"></div>
                    <button
                        id="save"
                        class="btn btn-success pull-right active"
                        disabled="disabled"
                    >
                        <?= __('SOCIAL__SAVE_SUCCESS') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
</section>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        var sortableBody = document.getElementById('sortable');
        var saveButton = document.getElementById('save');
        var ajaxMsg = document.querySelector('.ajax-msg');
        var draggedRow = null;

        function enableSaveInProgress() {
            if (!saveButton) {
                return;
            }
            saveButton.disabled = false;
            saveButton.textContent = '<?= __('SOCIAL__SAVE_IN_PROGRESS') ?>';
        }

        function setSaveSuccess() {
            if (!saveButton) {
                return;
            }
            saveButton.disabled = true;
            saveButton.textContent = '<?= __('SOCIAL__SAVE_SUCCESS') ?>';
        }

        function showError(message) {
            if (!ajaxMsg) {
                return;
            }
            ajaxMsg.innerHTML =
                '<div class="alert alert-danger" style="margin-top:10px;margin-right:10px;margin-left:10px;">' +
                '<a class="close" data-dismiss="alert">×</a>' +
                '<i class="icon icon-warning-sign"></i> ' +
                '<b><?= __('GLOBAL__ERROR') ?> :</b> ' + message +
                '</div>';
        }

        function buildSerializedOrder() {
            if (!sortableBody) {
                return '';
            }
            var items = sortableBody.querySelectorAll('.item');
            var parts = [];
            var regexp = /^(.+)[\-=_](.+)$/;

            items.forEach(function (row) {
                var id = row.id || '';
                var match = id.match(regexp);
                if (match) {
                    var name = match[1] + '[]';
                    var value = match[2];
                    parts.push(encodeURIComponent(name) + '=' + encodeURIComponent(value));
                }
            });

            return parts.join('&');
        }

        function sendOrder() {
            var url = '<?= $this->Url->build(['_name' => 'admin_social_save_ajax']) ?>';
            var socialOrder = buildSerializedOrder();

            var params = new URLSearchParams();
            params.append('social_button_order', socialOrder);

            fetch(url, {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                },
                body: params.toString()
            })
                .then(function (response) {
                    return response.json();
                })
                .then(function (data) {
                    if (data && data.statut) {
                        setSaveSuccess();
                    } else if (data && data.statut === false) {
                        showError(data.msg || '');
                    } else {
                        showError('<?= __('ERROR__INTERNAL_ERROR') ?>');
                    }
                })
                .catch(function () {
                    showError('<?= __('ERROR__INTERNAL_ERROR') ?>');
                });
        }

        function makeSortable() {
            if (!sortableBody) {
                return;
            }

            var rows = sortableBody.querySelectorAll('.item:not(.fixed)');
            rows.forEach(function (row) {
                row.setAttribute('draggable', 'true');

                row.addEventListener('dragstart', function (e) {
                    draggedRow = row;
                    row.classList.add('dragging');
                    e.dataTransfer.effectAllowed = 'move';
                });

                row.addEventListener('dragend', function () {
                    row.classList.remove('dragging');
                    draggedRow = null;
                });

                row.addEventListener('dragover', function (e) {
                    e.preventDefault();
                    e.dataTransfer.dropEffect = 'move';

                    var target = row;
                    if (!draggedRow || draggedRow === target) {
                        return;
                    }

                    var bounding = target.getBoundingClientRect();
                    var offset = e.clientY - bounding.top;

                    if (offset > bounding.height / 2) {
                        if (target.nextSibling !== draggedRow) {
                            target.parentNode.insertBefore(draggedRow, target.nextSibling);
                        }
                    } else {
                        if (target !== draggedRow.nextSibling) {
                            target.parentNode.insertBefore(draggedRow, target);
                        }
                    }
                });

                row.addEventListener('drop', function (e) {
                    e.preventDefault();
                    enableSaveInProgress();
                    sendOrder();
                });
            });
        }

        makeSortable();
    });
</script>
