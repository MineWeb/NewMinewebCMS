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

<script>
    document.addEventListener('DOMContentLoaded', function () {
        var sortableBody = document.getElementById('sortable');
        var saveButton = document.getElementById('save');
        var ajaxMsg = document.querySelector('.ajax-msg');

        if (!sortableBody) {
            return;
        }

        function showError(message) {
            if (!ajaxMsg) {
                return;
            }
            ajaxMsg.innerHTML =
                '<div class="alert alert-danger">' +
                '<b><?= __('GLOBAL__ERROR') ?> :</b> ' + message +
                '</div>';
        }

        function setButtonLoading() {
            saveButton.disabled = true;
            saveButton.textContent = '<?= __('SOCIAL__SAVE_IN_PROGRESS') ?>';
        }

        function setButtonSuccess() {
            saveButton.disabled = true;
            saveButton.textContent = '<?= __('SOCIAL__SAVE_SUCCESS') ?>';
        }

        function buildOrderString() {
            var items = sortableBody.querySelectorAll('.item');
            var parts = [];
            var regexp = /^(.+)[\-=_](.+)$/;

            items.forEach(function (row) {
                var match = row.id.match(regexp);
                if (match) {
                    parts.push(match[1] + '[]=' + match[2]);
                }
            });

            return parts.join('&');
        }

        function sendOrder() {
            setButtonLoading();

            var params = new URLSearchParams();
            params.append('social_button_order', buildOrderString());

            fetch('<?= $this->Url->build(['_name' => 'admin_social_save_ajax']) ?>', {
                method: 'POST',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: params.toString()
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.statut) {
                        setButtonSuccess();
                    } else {
                        setButtonSuccess();
                        showError(data.msg || '');
                    }
                })
                .catch(function () {
                    setButtonSuccess();
                    showError('<?= __('ERROR__INTERNAL_ERROR') ?>');
                });
        }

        Sortable.create(sortableBody, {
            animation: 150,
            handle: '.item',
            onEnd: function () {
                sendOrder();
            }
        });
    });
</script>
