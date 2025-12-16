<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-images mr-2"></i><?= __('SLIDER__LIST') ?>
                            </h3>

                            <a class="btn btn-primary btn-sm"
                               href="<?= $this->Url->build(['_name' => 'admin_slider_add']) ?>">
                                <i class="fas fa-plus mr-2"></i><?= __('SLIDER__ADD') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th><?= __('GLOBAL__TITLE') ?></th>
                                        <th><?= __('SLIDER__SUBTITLE') ?></th>
                                        <th><?= __('GLOBAL__IMAGE') ?></th>
                                        <th class="text-right" style="width: 1%; white-space: nowrap;"><?= __('GLOBAL__ACTIONS') ?></th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php if (empty($sliders) || count($sliders) === 0) { ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="far fa-folder-open mr-2"></i><?= __('GLOBAL__NO_RESULT') ?>
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($sliders as $v) { ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="font-weight-bold"><?= h((string)($v['title'] ?? '')) ?></div>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-muted"><?= h((string)($v['subtitle'] ?? '')) ?></span>
                                                </td>

                                                <td class="align-middle">
                                                    <?php $img = (string)($v['url_img'] ?? ''); ?>
                                                    <?php if ($img !== '') { ?>
                                                        <a href="<?= h($img) ?>" target="_blank" rel="noopener" class="d-inline-block" title="<?= h($img) ?>">
                                                            <img
                                                                src="<?= h($img) ?>"
                                                                alt="<?= h((string)($v['title'] ?? '')) ?>"
                                                                style="width: 64px; height: 40px; object-fit: cover; border-radius: .25rem;"
                                                            >
                                                        </a>
                                                    <?php } else { ?>
                                                        <span class="badge badge-light"><?= __('GLOBAL__NO_RESULT') ?></span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle text-right text-nowrap">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="<?= h(__('GLOBAL__ACTIONS')) ?>">
                                                        <a href="<?= $this->Url->build(['_name' => 'admin_slider_edit', (int)$v['id']]) ?>"
                                                           class="btn btn-info">
                                                            <i class="fas fa-edit mr-1"></i><?= __('GLOBAL__EDIT') ?>
                                                        </a>

                                                        <a href="#"
                                                           class="btn btn-danger"
                                                           onclick="confirmDel('<?= $this->Url->build(['_name' => 'admin_slider_delete', (int)$v['id']]) ?>'); return false;">
                                                            <i class="fas fa-trash mr-1"></i><?= __('GLOBAL__DELETE') ?>
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
