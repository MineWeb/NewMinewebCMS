<section class="content">
    <div class="container-fluid">

        <div class="callout callout-info mb-3">
            <h5 class="mb-1">
                <i class="fas fa-info-circle mr-2"></i><?= __('MOTD__TITLE') ?>
            </h5>
            <div class="text-sm mb-0"><?= __('MOTD__TITLE_DESC') ?></div>
        </div>

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-server mr-2"></i><?= __('MOTD__TITLE') ?>
                        </h3>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th><?= __('GLOBAL__NAME') ?></th>
                                        <th><?= __('MOTD__LINE') ?> 1</th>
                                        <th><?= __('MOTD__LINE') ?> 2</th>
                                        <th class="text-right" style="width: 1%; white-space: nowrap;"><?= __('GLOBAL__ACTIONS') ?></th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php if (empty($get_servers) || count($get_servers) === 0) { ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="far fa-folder-open mr-2"></i><?= __('GLOBAL__NO_RESULT') ?>
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($get_servers as $server) { ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="font-weight-bold"><?= h((string)$server['name']) ?></div>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-muted"><?= h((string)$server['motd_line1']) ?></span>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if (!empty($server['motd_line2'])) { ?>
                                                        <span class="text-muted"><?= h((string)$server['motd_line2']) ?></span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-light"><?= __('MOTD__NOT_SET') ?></span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle text-right text-nowrap">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="<?= h(__('GLOBAL__ACTIONS')) ?>">
                                                        <a class="btn btn-info"
                                                           href="<?= $this->Url->build(['_name' => 'admin_motd_edit', (int)$server['id']]) ?>">
                                                            <i class="fas fa-edit mr-1"></i><?= __('GLOBAL__EDIT') ?>
                                                        </a>

                                                        <a class="btn btn-danger"
                                                           href="#"
                                                           onclick="confirmDel('<?= $this->Url->build(['_name' => 'admin_motd_reset', (int)$server['id']]) ?>'); return false;">
                                                            <i class="fas fa-undo mr-1"></i><?= __('MOTD__RESET') ?>
                                                        </a>
                                                    </div>
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
