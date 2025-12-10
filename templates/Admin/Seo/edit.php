<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('SEO__EDIT_PAGE') ?></h3>
                </div>
                <div class="card-body">
                    <form method="post" data-ajax="true" data-upload-image="true"
                          data-redirect-url="<?= Router::url(['controller' => 'seo', 'action' => 'index', 'admin' => 'true']) ?>">
                        <div class="row">
                            <div class="col-12">
                                <div class="form-group">
                                    <label><?= __('SEO__PAGE') ?></label>
                                    <br>
                                    <em><?= __('SEO__PAGE_DESC') ?></em>
                                    <input type="text" class="form-control" name="page" value="<?= $page['page'] ?>">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?= __('SEO__FORM_TITLE') ?></label>
                                    <br>
                                    <em><?= __('SEO__KEEP_EMPTY') ?></em>
                                    <input type="text" class="form-control"
                                           value="<?= $page['title'] ?>"
                                           name="title">

                                    <small><b>{TITLE}</b> = <?= __('SEO__FORM_TITLE_DESC_T') ?>
                                        <br><b>{WEBSITE_NAME}</b> = <?= __('SEO__FORM_TITLE_DESC_W') ?></small>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?= __('SEO__FORM_DESCRIPTION') ?></label>
                                    <br>
                                    <em><?= __('SEO__KEEP_EMPTY') ?></em>
                                    <input type="text" class="form-control" value="<?= $page['description'] ?>"
                                           name="description">
                                </div>
                            </div>

                            <div class="col-12">
                                <hr>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?= __('SEO__FORM_FAVICON') ?></label><em> <?= __('SEO__FORM_FAVICON_DESC') ?></em>
                                    <br>
                                    <em><?= __('SEO__KEEP_EMPTY') ?></em>
                                    <?= $this->element('form.input.upload.img', ['img' => $page['favicon_url'], 'filename' => "favicon.png"]); ?>
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?= __('SEO__FORM_IMG_URL') ?></label><em> <?= __('SEO__FORM_IMG_URL_DESC') ?></em>
                                    <br>
                                    <em><?= __('SEO__KEEP_EMPTY') ?></em>
                                    <input type="text" class="form-control" value="<?= $page['img_url'] ?>"
                                           name="img_url">
                                </div>
                            </div>

                            <div class="col-12">
                                <hr>
                            </div>

                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?= __('SEO__FORM_THEME_COLOR') ?></label><em> <?= __('SEO__FORM_THEME_COLOR_DESC') ?></em>
                                    <br>
                                    <em><?= __('SEO__KEEP_EMPTY') ?></em>
                                    <input type="color" class="form-control" value="<?= $page['theme_color'] ?>" name="theme_color">
                                </div>
                            </div>
                            <div class="col-sm-6">
                                <div class="form-group">
                                    <label><?= __('SEO__FORM_TWITTER_SITE') ?></label> <em><?= __('SEO__FORM_TWITTER_SITE_DESC') ?></em>
                                    <br>
                                    <em><?= __('SEO__KEEP_EMPTY') ?></em>
                                    <input type="text" class="form-control" value="<?= $page['twitter_site'] ?>" name="twitter_site">
                                </div>
                            </div>
                        </div>

                        <div class="float-right">
                            <a href="<?= Router::url(['controller' => 'seo', 'action' => 'index', 'admin' => true]) ?>"
                               class="btn btn-default"><?= __('GLOBAL__CANCEL') ?></a>
                            <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                        </div>

                    </form>

                </div>
            </div>
        </div>
    </div>
</section>
