<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-user-lock mr-2"></i><?= __('BAN__ADD') ?>
                            </h3>

                            <a href="<?= $this->Url->build(['_name' => 'admin_ban_index']) ?>"
                               class="btn btn-default btn-sm">
                                <i class="fas fa-arrow-left mr-2"></i><?= __('GLOBAL__BACK') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body">
                        <?= $this->Form->create(null, [
                            'method' => 'post',
                            'data-ajax' => 'true',
                            'data-upload-image' => 'true',
                        ]) ?>

                        <div class="row">
                            <div class="col-12">
                                <div class="alert alert-light border d-flex align-items-start">
                                    <i class="fas fa-info-circle mt-1 mr-2"></i>
                                    <div class="text-sm mb-0"><?= __('BAN__ADD_HINT') ?></div>
                                </div>
                            </div>

                            <div class="col-12">
                                <table class="table table-responsive-sm table-bordered"
                                       style="table-layout: fixed;word-wrap: break-word;"
                                       id="users">
                                    <thead>
                                    <tr>
                                        <th><?= __('BAN__QUESTION') ?></th>
                                        <th><?= __('USER__TITLE') ?></th>
                                        <th><?= __('USER__RANK') ?></th>
                                        <th>IP</th>
                                        <th><?= __('BAN__IP_QUESTION') ?></th>
                                    </tr>
                                    </thead>
                                </table>
                            </div>

                            <div class="col-lg-6 mt-3">
                                <div class="form-group mb-0">
                                    <label for="reason"><?= __('BAN__REASON') ?></label>
                                    <input type="text" class="form-control" id="reason" name="reason" autocomplete="off">
                                </div>
                            </div>

                            <div class="col-12">
                                <div class="d-flex justify-content-end mt-3">
                                    <a href="<?= $this->Url->build(['_name' => 'admin_ban_index']) ?>"
                                       class="btn btn-default mr-2">
                                        <i class="fas fa-times mr-1"></i><?= __('GLOBAL__CANCEL') ?>
                                    </a>
                                    <button class="btn btn-primary" type="submit">
                                        <i class="fas fa-check mr-1"></i><?= __('GLOBAL__SUBMIT') ?>
                                    </button>
                                </div>
                            </div>
                        </div>

                        <?= $this->Form->end() ?>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<script type="text/javascript">
    document.addEventListener("DOMContentLoaded", function () {
        if (typeof DataTable === "undefined") {
            return;
        }

        new DataTable("#users", {
            paging: true,
            lengthChange: false,
            searching: true,
            ordering: false,
            info: false,
            autoWidth: false,
            processing: true,
            serverSide: true,
            ajax: "<?= $this->Url->build(['_name' => 'admin_ban_get_users_not_ban']) ?>",
            columns: [
                {data: "Users.ban", searchable: false},
                {data: "Users.username", searchable: true},
                {data: "Users.rank", searchable: false},
                {data: "Users.ip", searchable: true},
                {data: "Users.banIp", searchable: false}
            ]
        });
    });
</script>
