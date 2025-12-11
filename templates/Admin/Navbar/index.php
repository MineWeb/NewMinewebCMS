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
                '<div class="alert alert-danger">' +
                '<b><?= __('GLOBAL__ERROR') ?> :</b> ' + message +
                '</div>';
        }

        function setButtonLoading() {
            saveButton.disabled = true;
            saveButton.textContent = '<?= __('NAVBAR__SAVE_IN_PROGRESS') ?>';
        }

        function setButtonSuccess() {
            saveButton.disabled = true;
            saveButton.textContent = '<?= __('NAVBAR__SAVE_SUCCESS') ?>';
        }

        function buildOrderString() {
            let rows = sortableBody.querySelectorAll('tr');
            let parts = [];
            rows.forEach(function (row, index) {
                let rawId = row.id || '';
                let idParts = rawId.split('-');
                let id = idParts[0];
                if (id) {
                    parts.push(id + '[]=' + (index + 1));
                }
            });
            return parts.join('&');
        }

        function saveOrder() {
            setButtonLoading();

            let params = new URLSearchParams();
            params.append('navbar_order', buildOrderString());

            fetch('<?= $this->Url->build(['_name' => 'admin_navbar_save_ajax']) ?>', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                body: params.toString()
            })
                .then(function (r) { return r.json(); })
                .then(function (data) {
                    if (data && data.statut) {
                        setButtonSuccess();
                    } else {
                        setButtonSuccess();
                        showError(data.msg || '<?= __('ERROR__INTERNAL_ERROR') ?>');
                    }
                })
                .catch(function () {
                    setButtonSuccess();
                    showError('<?= __('ERROR__INTERNAL_ERROR') ?>');
                });
        }

        Sortable.create(sortableBody, {
            animation: 150,
            onEnd: function () {
                saveOrder();
            }
        });
    });
</script>

