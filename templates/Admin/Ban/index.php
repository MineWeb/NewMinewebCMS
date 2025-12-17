<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-user-slash mr-2"></i><?= __('BAN__HOME') ?>
                            </h3>

                            <a class="btn btn-primary btn-sm"
                               href="<?= $this->Url->build(['_name' => 'admin_ban_add']) ?>">
                                <i class="fas fa-plus mr-2"></i><?= __('BAN__ADD') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <div class="table-responsive">
                                <table class="table table-responsive-sm table-bordered mb-0">
                                    <thead>
                                    <tr>
                                        <th class="align-middle"><?= __('USER__USERNAME') ?></th>
                                        <th class="align-middle"><?= __('BAN__REASON') ?></th>
                                        <th class="align-middle"><?= __('BAN__IS_BAN_IP') ?></th>
                                        <th class="align-middle text-right" style="width: 1%; white-space: nowrap;"><?= __('GLOBAL__ACTIONS')?></th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php if (empty($banned_users) || count($banned_users) === 0) { ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="far fa-folder-open mr-2"></i><?= __('TABLE__NO_RESULT') ?>
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($banned_users as $v) { ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="font-weight-bold"><?= h((string)($v['username'] ?? '')) ?></div>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-muted"><?= h((string)($v['reason'] ?? '')) ?></span>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if (!empty($v['ip'])) { ?>
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-network-wired mr-1"></i><?= h((string)$v['ip']) ?>
                                                        </span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-light"><?= __('BAN__NOT_BAN_IP') ?></span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle text-right text-nowrap">
                                                    <a href="#"
                                                       onclick="confirmDel('<?= $this->Url->build(['_name' => 'admin_ban_unban', (int)$v['id']]) ?>'); return false;"
                                                       class="btn btn-danger btn-sm">
                                                        <i class="fas fa-unlock mr-1"></i><?= __('BAN__UNBAN') ?>
                                                    </a>
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

            </div>
        </div>

    </div>
</section>
