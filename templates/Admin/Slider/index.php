<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                            <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-images mr-2"></i><?= __('SLIDER__LIST') ?>
                                </h3>

                                <span class="badge badge-light border">
                                    <i class="fas fa-list mr-1"></i><?= is_countable($sliders) ? count($sliders) : 0 ?> <?= __('TABLE__ITEMS') ?>
                                </span>
                            </div>

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
                                    <thead>
                                    <tr>
                                        <th class="text-muted text-uppercase text-sm" style="letter-spacing: .02em;">
                                            <?= __('GLOBAL__TITLE') ?>
                                        </th>
                                        <th class="text-muted text-uppercase text-sm d-none d-md-table-cell" style="letter-spacing: .02em;">
                                            <?= __('SLIDER__SUBTITLE') ?>
                                        </th>
                                        <th class="text-muted text-uppercase text-sm" style="letter-spacing: .02em;">
                                            <?= __('GLOBAL__IMAGE') ?>
                                        </th>
                                        <th class="text-muted text-uppercase text-sm text-right" style="letter-spacing: .02em; width: 1%; white-space: nowrap;">
                                            <?= __('GLOBAL__ACTIONS') ?>
                                        </th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php if (empty($sliders) || count($sliders) === 0) { ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="far fa-folder-open mr-2"></i><?= __('TABLE__NO_RESULT') ?>
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($sliders as $v) { ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="font-weight-bold"><?= h((string)($v['title'] ?? '')) ?></div>
                                                    <?php $subtitle = (string)($v['subtitle'] ?? ''); ?>
                                                    <?php if ($subtitle !== '') { ?>
                                                        <div class="text-muted text-sm d-md-none">
                                                            <?= h($subtitle) ?>
                                                        </div>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle d-none d-md-table-cell">
                                                    <span class="text-muted"><?= h((string)($v['subtitle'] ?? '')) ?></span>
                                                </td>

                                                <td class="align-middle">
                                                    <?php $img = (string)($v['url_img'] ?? ''); ?>
                                                    <?php if ($img !== '') { ?>
                                                        <a href="<?= h($img) ?>" target="_blank" rel="noopener" class="d-inline-block" title="<?= h($img) ?>">
                                                            <span class="d-inline-flex align-items-center justify-content-center border rounded bg-white"
                                                                  style="width: 120px; height: 72px; overflow: hidden;">
                                                                <img
                                                                    src="<?= h($img) ?>"
                                                                    alt="<?= h((string)($v['title'] ?? '')) ?>"
                                                                    style="max-width: 100%; max-height: 100%; width: auto; height: auto; object-fit: contain; display: block;"
                                                                >
                                                            </span>
                                                        </a>
                                                    <?php } else { ?>
                                                        <span class="badge badge-light border"><?= __('TABLE__NO_RESULT') ?></span>
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
