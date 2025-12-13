<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('BAN__HOME') ?></h3>
                </div>
                <div class="card-body">

                    <?= $this->Form->create(
                        null,
                        [
                            'method' => 'post',
                            'data-ajax' => 'true',
                            'data-upload-image' => 'true',
                            'data-redirect-url' => $this->Url->build(['_name' => 'admin_ban_index']),
                        ]
                    ) ?>

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

                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="reason"><?= __('BAN__REASON') ?></label>
                            <input type="text" class="form-control" id="reason" name="reason">
                        </div>
                    </div>

                    <div class="float-right">
                        <a href="<?= $this->Url->build(['_name' => 'admin_ban_index']) ?>"
                           class="btn btn-default">
                            <?= __('GLOBAL__CANCEL') ?>
                        </a>
                        <button class="btn btn-primary" type="submit">
                            <?= __('GLOBAL__SUBMIT') ?>
                        </button>
                    </div>

                    <?= $this->Form->end() ?>

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
