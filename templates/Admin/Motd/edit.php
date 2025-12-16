<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-edit mr-2"></i><?= __('MOTD__EDIT_TITLE') ?>
                            </h3>

                            <a href="<?= $this->Url->build(['_name' => 'admin_motd_index']) ?>" class="btn btn-default btn-sm">
                                <i class="fas fa-arrow-left mr-2"></i><?= __('GLOBAL__BACK') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <?= $this->Form->create(null, [
                                'url' => ['_name' => 'admin_motd_edit_ajax', $get['id']],
                                'data-ajax' => 'true',
                                'data-redirect-url' => $this->Url->build(['_name' => 'admin_motd_index'])
                            ]) ?>

                            <div class="ajax-msg"></div>

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <label for="motd-name" class="mb-1"><?= __('GLOBAL__NAME') ?></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-server"></i></span>
                                                    </div>
                                                    <input
                                                        id="motd-name"
                                                        class="form-control"
                                                        value="<?= h((string)$get['name']) ?>"
                                                        type="text"
                                                        disabled
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="alert alert-light border d-flex align-items-start mb-0">
                                                <i class="fas fa-info-circle mt-1 mr-2"></i>
                                                <div class="text-sm mb-0"><?= __('MOTD__DESC') ?></div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <label class="mb-2"><?= __('MOTD__EDIT_TITLE') ?></label>

                                            <div class="form-group">
                                                <label for="motd-line1" class="mb-1"><?= __('MOTD__LINE') ?> 1</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><?= __('MOTD__LINE') ?> 1</span>
                                                    </div>
                                                    <input
                                                        id="motd-line1"
                                                        type="text"
                                                        name="motd_line1"
                                                        class="form-control"
                                                        value="<?= h((string)$get['motd_line1']) ?>"
                                                        autocomplete="off"
                                                    >
                                                </div>
                                            </div>

                                            <div class="form-group mb-0">
                                                <label for="motd-line2" class="mb-1"><?= __('MOTD__LINE') ?> 2</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><?= __('MOTD__LINE') ?> 2</span>
                                                    </div>
                                                    <input
                                                        id="motd-line2"
                                                        type="text"
                                                        name="motd_line2"
                                                        class="form-control"
                                                        value="<?= h((string)($get['motd_line2'] ?? '')) ?>"
                                                        placeholder="<?= h((string)__('MOTD__NOT_SET')) ?>"
                                                        autocomplete="off"
                                                    >
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex align-items-start">
                                                <i class="fas fa-code mt-1 mr-2 text-muted"></i>
                                                <div>
                                                    <div class="font-weight-bold mb-2"><?= __('MOTD__letIABLES') ?> :</div>
                                                    <div class="text-sm mb-0">
                                                        <span class="badge badge-light">{PLAYERS}</span>
                                                        <span class="text-muted ml-2"><?= __('MOTD__letIABLE_PLAYERS') ?></span>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-end">
                                        <a href="<?= $this->Url->build(['_name' => 'admin_motd_index']) ?>" class="btn btn-default mr-2">
                                            <?= __('GLOBAL__CANCEL') ?>
                                        </a>

                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save mr-2"></i><?= __('GLOBAL__EDIT') ?>
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
    </div>
</section>
