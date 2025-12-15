<section class="content">

    <div class="callout callout-info">
        <h4><?= __('MOTD__TITLE') ?></h4>
        <?= __('MOTD__TITLE_DESC') ?>
    </div>

    <div class="card">
        <div class="card-header with-border">
            <h3 class="card-title"><?= __('MOTD__TITLE') ?></h3>
        </div>

        <div class="card-body">
            <hr>

            <table class="table table-bordered table-responsive-sm">
                <thead>
                <tr>
                    <th><?= __('GLOBAL__NAME') ?></th>
                    <th><?= __('MOTD__LINE') ?> 1</th>
                    <th><?= __('MOTD__LINE') ?> 2</th>
                    <th><?= __('GLOBAL__ACTIONS') ?></th>
                </tr>
                </thead>

                <tbody>
                <?php foreach ($get_servers as $server) { ?>
                    <tr>
                        <td><?= h($server['name']) ?></td>
                        <td><?= h($server['motd_line1']) ?></td>
                        <td><?= $server['motd_line2'] ? h($server['motd_line2']) : __('MOTD__NOT_SET') ?></td>

                        <td>
                            <a class="btn btn-info"
                               href="<?= $this->Url->build([
                                   '_name' => 'admin_motd_edit',
                                   $server['id']
                               ]) ?>">
                                <?= __('GLOBAL__EDIT') ?>
                            </a>

                            <a class="btn btn-danger"
                               onClick="confirmDel('<?= $this->Url->build([
                                   '_name' => 'admin_motd_reset',
                                   $server['id']
                               ]) ?>')">
                                <?= __('MOTD__RESET') ?>
                            </a>
                        </td>
                    </tr>
                <?php } ?>
                </tbody>

            </table>
        </div>
    </div>

</section>
