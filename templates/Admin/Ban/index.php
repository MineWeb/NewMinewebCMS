<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __("BAN__HOME") ?></h3>
                </div>
                <div class="card-body">
                    <a class="btn btn-large btn-block btn-primary"
                       href="<?= $this->Url->build(['_name' => 'admin_ban_add']) ?>">
                        <?= __('BAN__ADD') ?>
                    </a>
                    <hr>
                    <table class="table table-responsive-sm table-bordered">
                        <thead>
                        <tr>
                            <th><?= __("USER__USERNAME") ?></th>
                            <th><?= __("BAN__REASON") ?></th>
                            <th><?= __("BAN__IS_BAN_IP") ?></th>
                            <th><?= __("GLOBAL__ACTIONS")?></th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($banned_users as $v) { ?>
                            <tr>
                                <td><?= $v["username"] ?></td>
                                <td><?= $v["reason"] ?></td>
                                <td><?= $v["ip"] !== null ? $v["ip"] : __("BAN__NOT_BAN_IP") ?></td>
                                <td>
                                    <a onclick="confirmDel('<?= $this->Url->build(['_name' => 'admin_ban_unban', $v['id']]) ?>')"
                                       class="btn btn-danger">
                                        <?= __('BAN__UNBAN') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
