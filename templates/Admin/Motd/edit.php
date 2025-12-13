<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('MOTD__EDIT_TITLE') ?></h3>
                </div>

                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_motd_edit_ajax', $get['id']],
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_motd_index'])
                    ]) ?>

                    <div class="form-group">
                        <label for="motd-name"><?= __('GLOBAL__NAME') ?></label>
                        <input
                            id="motd-name"
                            disabled
                            class="form-control"
                            value="<?= h($get['name']) ?>"
                            type="text"
                        >
                    </div>

                    <div class="form-group">
                        <label for="motd-line1"><?= __('MOTD__EDIT_TITLE') ?></label>
                        <p><?= __('MOTD__DESC') ?></p>

                        <div class="input-group mb-3">
                            <span class="input-group-text"><?= __('MOTD__LINE') ?> 1</span>
                            <input
                                id="motd-line1"
                                type="text"
                                name="motd_line1"
                                class="form-control"
                                value="<?= h($get['motd_line1']) ?>"
                            >
                        </div>

                        <label for="motd-line2"><?= __('MOTD__LINE') ?> 2</label>
                        <div class="input-group mb-3">
                            <span class="input-group-text"><?= __('MOTD__LINE') ?> 2</span>
                            <input
                                id="motd-line2"
                                type="text"
                                name="motd_line2"
                                class="form-control"
                                value="<?= h($get['motd_line2'] ?? 'Non définie') ?>"
                            >
                        </div>

                        <p><strong><?= __('MOTD__letIABLES') ?> :</strong></p>

                        <p>
                            <em>{PLAYERS}</em> : <?= __('MOTD__letIABLE_PLAYERS') ?>
                        </p>
                    </div>

                    <div class="float-right">
                        <a
                            href="<?= $this->Url->build(['_name' => 'admin_motd_index']) ?>"
                            class="btn btn-default"
                        >
                            <?= __('GLOBAL__CANCEL') ?>
                        </a>

                        <button class="btn btn-primary" type="submit">
                            <?= __('GLOBAL__EDIT') ?>
                        </button>
                    </div>

                    <?= $this->Form->end() ?>

                </div>
            </div>

        </div>
    </div>
</section>
