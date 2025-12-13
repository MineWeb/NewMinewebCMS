<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="callout callout-danger">
                <h4><?= __('SEO__CALLOUT') ?></h4>
                <?= __('SEO__CALLOUT_MESSAGE') ?>
            </div>

            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('SEO__TITLE_DEFAULT') ?></h3>
                </div>

                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_seo_index'],
                        'data-ajax' => 'true',
                        'data-upload-image' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_seo_index'])
                    ]) ?>

                    <div class="row">

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="seo-title"><?= __('SEO__FORM_TITLE') ?></label>
                                <input
                                    id="seo-title"
                                    type="text"
                                    class="form-control"
                                    name="title"
                                    value="<?= empty($default['title']) ? '{TITLE} - {WEBSITE_NAME}' : $default['title'] ?>"
                                >

                                <small>
                                    <b>{TITLE}</b> = <?= __('SEO__FORM_TITLE_DESC_T') ?><br>
                                    <b>{WEBSITE_NAME}</b> = <?= __('SEO__FORM_TITLE_DESC_W') ?>
                                </small>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="seo-description"><?= __('SEO__FORM_DESCRIPTION') ?></label>
                                <input
                                    id="seo-description"
                                    type="text"
                                    class="form-control"
                                    name="description"
                                    value="<?= $default['description'] ?? '' ?>"
                                >
                            </div>
                        </div>

                        <div class="col-12"><hr></div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?= __('SEO__FORM_FAVICON') ?></label>
                                <em> <?= __('SEO__FORM_FAVICON_DESC') ?></em>
                                <?= $this->element('form.input.upload.img', [
                                    'img' => $default['favicon_url'] ?? '',
                                    'filename' => 'favicon.png'
                                ]) ?>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="seo-img-url"><?= __('SEO__FORM_IMG_URL') ?></label>
                                <em> <?= __('SEO__FORM_IMG_URL_DESC') ?></em>
                                <input
                                    id="seo-img-url"
                                    type="text"
                                    class="form-control"
                                    name="img_url"
                                    value="<?= $default['img_url'] ?? '' ?>"
                                >
                            </div>
                        </div>

                        <div class="col-12"><hr></div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="seo-theme-color"><?= __('SEO__FORM_THEME_COLOR') ?></label>
                                <em> <?= __('SEO__FORM_THEME_COLOR_DESC') ?></em>
                                <input
                                    id="seo-theme-color"
                                    type="color"
                                    class="form-control"
                                    name="theme_color"
                                    value="<?= $default['theme_color'] ?? '' ?>"
                                >
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="seo-twitter"><?= __('SEO__FORM_TWITTER_SITE') ?></label>
                                <em> <?= __('SEO__FORM_TWITTER_SITE_DESC') ?></em>
                                <input
                                    id="seo-twitter"
                                    type="text"
                                    class="form-control"
                                    name="twitter_site"
                                    value="<?= $default['twitter_site'] ?? '' ?>"
                                >
                            </div>
                        </div>

                    </div>

                    <button class="btn btn-primary float-right" type="submit">
                        <?= __('GLOBAL__SUBMIT') ?>
                    </button>

                    <?= $this->Form->end() ?>

                </div>
            </div>


            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('SEO__TITLE_OTHER') ?></h3>
                </div>

                <div class="card-body">

                    <a class="btn btn-large btn-block btn-primary"
                       href="<?= $this->Url->build(['_name' => 'admin_seo_add']) ?>">
                        <?= __('SEO__ADD_PAGE') ?>
                    </a>

                    <hr>

                    <table class="table table-responsive-sm table-bordered">
                        <thead>
                        <tr>
                            <th><?= __('SEO__PAGE') ?></th>
                            <th><?= __('SEO__FORM_TITLE') ?></th>
                            <th><?= __('SEO__FORM_DESCRIPTION') ?></th>
                            <th><?= __('SEO__FORM_FAVICON') ?></th>
                            <th><?= __('SEO__FORM_IMG_URL') ?></th>
                            <th><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($seo_other as $v) { ?>
                            <tr>
                                <td><?= $v['page'] ?></td>
                                <td><?= $v['title'] ?: __('SEO__DEFAULT_VALUE') ?></td>
                                <td><?= $v['description'] ?: __('SEO__DEFAULT_VALUE') ?></td>
                                <td><?= $v['favicon_url'] ?: __('SEO__DEFAULT_VALUE') ?></td>
                                <td><?= $v['img_url'] ?: __('SEO__DEFAULT_VALUE') ?></td>
                                <td>
                                    <a class="btn btn-info"
                                       href="<?= $this->Url->build(['_name' => 'admin_seo_edit', 'pass' => [$v['id']]]) ?>">
                                        <?= __('GLOBAL__EDIT') ?>
                                    </a>

                                    <a class="btn btn-danger"
                                       onClick="confirmDel('<?= $this->Url->build(['_name' => 'admin_seo_delete', 'pass' => [$v['id']]]) ?>')">
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
