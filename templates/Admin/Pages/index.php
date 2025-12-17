<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">

                            <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-file-alt mr-2"></i><?= __('PAGE__LIST') ?>
                                </h3>

                                <span class="badge badge-light border">
                                    <i class="fas fa-list mr-1"></i><?= is_countable($pages) ? count($pages) : 0 ?> <?= __('TABLE__ITEMS') ?>
                                </span>
                            </div>

                            <a class="btn btn-primary btn-sm"
                               href="<?= $this->Url->build(['_name' => 'admin_pages_add']) ?>">
                                <i class="fas fa-plus mr-2"></i><?= __('PAGE__ADD') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <div class="table-responsive">
                                <table class="table table-hover table-striped table-bordered mb-0">
                                    <thead>
                                    <tr>
                                        <th class="align-middle"><?= __('GLOBAL__TITLE') ?></th>
                                        <th class="align-middle"><?= __('GLOBAL__BY') ?></th>
                                        <th class="align-middle"><?= __('PAGE__POSTED_ON') ?></th>
                                        <th class="align-middle"><?= __('GLOBAL__UPDATED') ?></th>
                                        <th class="align-middle"><?= __('GLOBAL__SLUG') ?></th>
                                        <th class="align-middle text-center" style="width: 170px;"><?= __('GLOBAL__ACTIONS') ?></th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php if (!empty($pages)) : ?>
                                        <?php foreach ($pages as $value) : ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="font-weight-bold"><?= h((string)$value['title']) ?></div>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-muted"><?= h((string)($value['author'] ?? '')) ?></span>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-muted"><?= $this->Lang->date($value['created_at']) ?></span>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-muted"><?= $this->Lang->date($value['updated_at']) ?></span>
                                                </td>

                                                <td class="align-middle">
                                                    <?php
                                                    $slug = (string)($value['slug'] ?? '');
                                                    $publicUrl = $this->Url->build('/p/' . $slug, ['fullBase' => true]);
                                                    ?>
                                                    <a href="<?= h($publicUrl) ?>" target="_blank" class="text-decoration-none">
                                                        <i class="fas fa-external-link-alt mr-1"></i><?= h($slug) ?>
                                                    </a>
                                                </td>

                                                <td class="align-middle text-right text-nowrap">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="<?= h(__('GLOBAL__ACTIONS')) ?>">
                                                        <a href="<?= $this->Url->build(['_name' => 'admin_pages_edit', (int)$value['id']]) ?>" class="btn btn-info">
                                                            <i class="fas fa-edit mr-1"></i><?= __('GLOBAL__EDIT') ?>
                                                        </a>
                                                        <a href="#"
                                                           class="btn btn-danger"
                                                           onclick="confirmDel('<?= $this->Url->build(['_name' => 'admin_pages_delete', (int)$value['id']]) ?>'); return false;">
                                                            <i class="fas fa-trash mr-1"></i><?= __('GLOBAL__DELETE') ?>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted p-4">
                                                <i class="far fa-folder-open mr-2"></i><?= __('TABLE__NO_RESULT') ?>
                                            </td>
                                        </tr>
                                    <?php endif; ?>
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
