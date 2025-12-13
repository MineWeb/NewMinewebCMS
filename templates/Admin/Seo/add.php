<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('SEO__ADD_PAGE') ?></h3>
                </div>

                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_seo_add'],
                        'type' => 'post',
                        'data-ajax' => 'true',
                        'data-upload-image' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_seo_index'])
                    ]) ?>

                    <div class="row">

                        <div class="col-12">
                            <div class="form-group">
                                <label for="seo-page"><?= __('SEO__PAGE') ?></label>
                                <br>
                                <em><?= __('SEO__PAGE_DESC') ?></em>
                                <input
                                    id="seo-page"
                                    type="text"
                                    name="page"
                                    class="form-control"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="seo-title"><?= __('SEO__FORM_TITLE') ?></label>
                                <br>
                                <em><?= __('SEO__KEEP_EMPTY') ?></em>

                                <input
                                    id="seo-title"
                                    type="text"
                                    name="title"
                                    class="form-control"
                                    autocomplete="off"
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
                                <br>
                                <em><?= __('SEO__KEEP_EMPTY') ?></em>

                                <input
                                    id="seo-description"
                                    type="text"
                                    name="description"
                                    class="form-control"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="col-12"><hr></div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?= __('SEO__FORM_FAVICON') ?></label>
                                <em> <?= __('SEO__FORM_FAVICON_DESC') ?></em>
                                <br>
                                <em><?= __('SEO__KEEP_EMPTY') ?></em>

                                <?= $this->element('form.input.upload.img', ['filename' => 'favicon.png']) ?>
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="seo-img-url"><?= __('SEO__FORM_IMG_URL') ?></label>
                                <em> <?= __('SEO__FORM_IMG_URL_DESC') ?></em>
                                <br>
                                <em><?= __('SEO__KEEP_EMPTY') ?></em>

                                <input
                                    id="seo-img-url"
                                    type="text"
                                    name="img_url"
                                    class="form-control"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                        <div class="col-12"><hr></div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="seo-theme-color"><?= __('SEO__FORM_THEME_COLOR') ?></label>
                                <em> <?= __('SEO__FORM_THEME_COLOR_DESC') ?></em>
                                <br>
                                <em><?= __('SEO__KEEP_EMPTY') ?></em>

                                <input
                                    id="seo-theme-color"
                                    type="color"
                                    name="theme_color"
                                    class="form-control"
                                >
                            </div>
                        </div>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label for="seo-twitter-site"><?= __('SEO__FORM_TWITTER_SITE') ?></label>
                                <em> <?= __('SEO__FORM_TWITTER_SITE_DESC') ?></em>
                                <br>
                                <em><?= __('SEO__KEEP_EMPTY') ?></em>

                                <input
                                    id="seo-twitter-site"
                                    type="text"
                                    name="twitter_site"
                                    class="form-control"
                                    autocomplete="off"
                                >
                            </div>
                        </div>

                    </div>

                    <div class="float-right">
                        <a
                            href="<?= $this->Url->build(['_name' => 'admin_seo_index']) ?>"
                            class="btn btn-default"
                        >
                            <?= __('GLOBAL__CANCEL') ?>
                        </a>

                        <button class="btn btn-primary" type="submit">
                            <?= __('GLOBAL__SUBMIT') ?>
                        </button>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</section>
