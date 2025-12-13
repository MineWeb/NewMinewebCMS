<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('PAGE__LIST') ?></h3>
                </div>
                <div class="card-body">

                    <a
                        class="btn btn-large btn-block btn-primary"
                        href="<?= $this->Url->build(['_name' => 'admin_pages_add']) ?>">
                        <?= __('PAGE__ADD') ?>
                    </a>

                    <hr>

                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th><?= __('GLOBAL__TITLE') ?></th>
                            <th><?= __('GLOBAL__BY') ?></th>
                            <th><?= __('PAGE__POSTED_ON') ?></th>
                            <th><?= __('GLOBAL__UPDATED') ?></th>
                            <th><?= __('GLOBAL__SLUG') ?></th>
                            <th><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody>

                        <?php foreach ($pages as $value) { ?>
                            <tr>
                                <td><?= h($value['title']) ?></td>
                                <td><?= h($value['author']) ?></td>
                                <td><?= $this->Lang->date($value['created_at']) ?></td>
                                <td><?= $this->Lang->date($value['updated_at']) ?></td>

                                <td>
                                    <a
                                        href="<?= $this->Url->build('/p/' . h($value['slug']), ['fullBase' => true]) ?>"
                                        target="_blank">
                                        <?= h($value['slug']) ?>
                                    </a>
                                </td>

                                <td>
                                    <a
                                        href="<?= $this->Url->build(['_name' => 'admin_pages_edit', $value['id']]) ?>"
                                        class="btn btn-info">
                                        <?= __('GLOBAL__EDIT') ?>
                                    </a>

                                    <a
                                        onClick="confirmDel('<?= $this->Url->build(['_name' => 'admin_pages_delete', $value['id']]) ?>')"
                                        class="btn btn-danger">
                                        <?= __('GLOBAL__DELETE') ?>
                                    </a>
                                </td>
                            </tr>
                        <?php } ?>

                        </tbody>
                    </table>

                </div>
            </div>
        </div>
    </div>
</section>
