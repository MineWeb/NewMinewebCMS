<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NEWS__LIST_PUBLISHED') ?></h3>
                </div>

                <div class="card-body">

                    <a class="btn btn-large btn-block btn-primary"
                       href="<?= $this->Url->build(['_name' => 'admin_news_add']) ?>">
                        <?= __('NEWS__ADD_NEWS') ?>
                    </a>

                    <hr>

                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th><?= __('GLOBAL__TITLE') ?></th>
                            <th><?= __('GLOBAL__BY') ?></th>
                            <th><?= __('NEWS__PUBLISHED') ?></th>
                            <th><?= __('NEWS__POSTED_ON') ?></th>
                            <th><?= __('NEWS__COMMENTS_NBR') ?></th>
                            <th><?= __('NEWS__LIKES_NBR') ?></th>
                            <th><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($view_news as $v): ?>
                            <tr>
                                <td><?= h($v['title']) ?></td>
                                <td><?= h($v['author']) ?></td>

                                <td>
                                    <?php if ($v['published']): ?>
                                        <span class="label label-success"><?= __('GLOBAL__YES') ?></span>
                                    <?php else: ?>
                                        <span class="label label-danger"><?= __('GLOBAL__NO') ?></span>
                                    <?php endif; ?>
                                </td>

                                <td><?= $this->Lang->date($v['created_at']) ?></td>
                                <td><?= count($v['comment']) ?> <?= __('NEWS__COMMENTS_TITLE') ?></td>
                                <td><?= count($v['likes']) ?> <?= __('NEWS__LIKES') ?></td>

                                <td>
                                    <a class="btn btn-info"
                                       href="<?= $this->Url->build(['_name' => 'admin_news_edit', 'pass' => [$v['id']]]) ?>">
                                        <?= __('GLOBAL__EDIT') ?>
                                    </a>

                                    <a class="btn btn-danger"
                                       onclick="confirmDel('<?= $this->Url->build(['_name' => 'admin_news_delete', 'pass' => [$v['id']]]) ?>')">
                                        <?= __('GLOBAL__DELETE') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>

                    </table>

                </div>
            </div>

        </div>
    </div>
</section>
