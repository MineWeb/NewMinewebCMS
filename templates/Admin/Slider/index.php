<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('SLIDER__LIST') ?></h3>
                </div>
                <div class="card-body">

                    <a class="btn btn-large btn-block btn-primary"
                       href="<?= $this->Url->build(['_name' => 'admin_slider_add']) ?>">
                        <?= __('SLIDER__ADD') ?>
                    </a>

                    <hr>

                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th><?= __('GLOBAL__TITLE') ?></th>
                            <th><?= __('SLIDER__SUBTITLE') ?></th>
                            <th><?= __('GLOBAL__IMAGE') ?></th>
                            <th><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($sliders as $slider => $v) { ?>
                            <tr>
                                <td><?= $v['title'] ?></td>
                                <td><?= $v['subtitle'] ?></td>
                                <td>
                                    <img width="50" height="50" src="<?= $v['url_img'] ?>" alt="">
                                </td>
                                <td>
                                    <a href="<?= $this->Url->build(['_name' => 'admin_slider_edit', $v['id']]) ?>"
                                       class="btn btn-info">
                                        <?= __('GLOBAL__EDIT') ?>
                                    </a>

                                    <a onClick="confirmDel('<?= $this->Url->build(['_name' => 'admin_slider_delete', $v['id']]) ?>')"
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
