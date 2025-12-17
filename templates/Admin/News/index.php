<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="far fa-newspaper mr-2"></i><?= __('NEWS__LIST_PUBLISHED') ?>
                            </h3>

                            <a class="btn btn-primary btn-sm"
                               href="<?= $this->Url->build(['_name' => 'admin_news_add']) ?>">
                                <i class="fas fa-plus mr-2"></i><?= __('NEWS__ADD_NEWS') ?>
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
                                        <th class="align-middle"><?= __('NEWS__PUBLISHED') ?></th>
                                        <th class="align-middle"><?= __('NEWS__POSTED_ON') ?></th>
                                        <th class="align-middle"><?= __('NEWS__COMMENTS_NBR') ?></th>
                                        <th class="align-middle"><?= __('NEWS__LIKES_NBR') ?></th>
                                        <th class="align-middle text-center" style="width: 170px;"><?= __('GLOBAL__ACTIONS') ?></th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php if (!empty($view_news)) : ?>
                                        <?php foreach ($view_news as $v) : ?>
                                            <?php
                                            $title = (string)($v['title'] ?? '');
                                            $author = (string)($v['author'] ?? '');
                                            $published = !empty($v['published']);
                                            $createdAt = $v['created_at'] ?? null;
                                            $commentsCount = isset($v['comment']) && is_array($v['comment']) ? count($v['comment']) : 0;
                                            $likesCount = isset($v['likes']) && is_array($v['likes']) ? count($v['likes']) : 0;
                                            ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="font-weight-bold"><?= h($title) ?></div>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-muted"><?= h($author) ?></span>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if ($published) : ?>
                                                        <span class="badge badge-success"><?= __('GLOBAL__YES') ?></span>
                                                    <?php else : ?>
                                                        <span class="badge badge-danger"><?= __('GLOBAL__NO') ?></span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-muted"><?= $this->Lang->date($createdAt) ?></span>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-muted"><?= $commentsCount ?> <?= __('NEWS__COMMENTS_TITLE') ?></span>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-muted"><?= $likesCount ?> <?= __('NEWS__LIKES') ?></span>
                                                </td>

                                                <td class="align-middle text-right text-nowrap">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="<?= h(__('GLOBAL__ACTIONS')) ?>">
                                                        <a href="<?= $this->Url->build(['_name' => 'admin_news_edit', (int)$v['id']]) ?>" class="btn btn-info">
                                                            <i class="fas fa-edit mr-1"></i><?= __('GLOBAL__EDIT') ?>
                                                        </a>
                                                        <a href="#"
                                                           class="btn btn-danger"
                                                           onclick="confirmDel('<?= $this->Url->build(['_name' => 'admin_news_delete', (int)$v['id']]) ?>'); return false;">
                                                            <i class="fas fa-trash mr-1"></i><?= __('GLOBAL__DELETE') ?>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php else : ?>
                                        <tr>
                                            <td colspan="7" class="text-center text-muted p-4">
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
