<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NEWS__ADD_NEWS') ?></h3>
                </div>

                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_news_add_ajax'],
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_news_index'])
                    ]) ?>

                    <div class="ajax-msg"></div>

                    <div class="form-group">
                        <label for="title"><?= __('GLOBAL__TITLE') ?></label>
                        <input id="title"
                               name="title"
                               type="text"
                               class="form-control"
                               placeholder="<?= __('GLOBAL__TITLE') ?>">
                    </div>

                    <div class="form-group">
                        <label for="slug"><?= __('GLOBAL__SLUG') ?></label>

                        <div class="input-group mb-3">
                                <span class="input-group-text">
                                    <?= $this->Url->build('/blog/', ['fullBase' => true]) ?>
                                </span>

                            <input id="slug"
                                   name="slug"
                                   type="text"
                                   class="form-control"
                                   placeholder="<?= __('GLOBAL__SLUG') ?>">

                            <a href="#"
                               id="generate_slug"
                               class="btn btn-info">
                                <?= __('GLOBAL__GENERATE') ?>
                            </a>
                        </div>
                    </div>

                    <div class="form-group">
                        <?= $this->Html->script('admin/tinymce/tinymce.min.js') ?>

                        <script>
                            tinymce.init({
                                selector: "textarea",
                                license_key: 'gpl',
                                promotion: false,
                                branding: false,
                                height: 300,
                                width: '100%',
                                language: '<?= $currentLocale ?>',
                                plugins: "code image link",
                                toolbar: "fontselect fontsizeselect bold italic underline strikethrough link image forecolor backcolor alignleft aligncenter alignright alignjustify cut copy paste bullist numlist outdent indent blockquote code"
                            });
                        </script>

                        <textarea id="editor"
                                  name="content"
                                  cols="30"
                                  rows="10"></textarea>
                    </div>

                    <div class="form-group">
                        <input id="published"
                               name="published"
                               type="checkbox"
                               checked>

                        <label for="published">
                            <?= __('NEWS__WANT_TO_PUBLISH') ?>
                        </label>
                    </div>

                    <div class="float-right">
                        <a href="<?= $this->Url->build(['_name' => 'admin_news_index']) ?>"
                           class="btn btn-default">
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
