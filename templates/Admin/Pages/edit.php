<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('PAGE__EDIT') ?></h3>
                </div>
                <div class="card-body">

                    <?php
                    echo $this->Form->create(null, [
                        'url' => ['_name' => 'admin_pages_edit_ajax'],
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_pages_index'])
                    ]);
                    ?>

                    <div class="ajax-msg"></div>

                    <input type="hidden" name="id" value="<?= (int)$page['id'] ?>">

                    <div class="form-group">
                        <label for="page-title"><?= __('GLOBAL__TITLE') ?></label>
                        <input
                            id="page-title"
                            name="title"
                            class="form-control"
                            type="text"
                            value="<?= h($page['title']) ?>"
                            placeholder="<?= __('GLOBAL__TITLE') ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="page-slug"><?= __('GLOBAL__SLUG') ?></label>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                    <span class="input-group-text">
                                        <?= $this->Url->build('/p/', ['fullBase' => true]) ?>
                                    </span>
                            </div>
                            <input
                                id="page-slug"
                                name="slug"
                                class="form-control"
                                type="text"
                                value="<?= h($page['slug']) ?>"
                                placeholder="<?= __('GLOBAL__SLUG') ?>"
                            >
                            <div class="input-group-append">
                                <a href="#" id="generate_slug" class="btn btn-info">
                                    <?= __('GLOBAL__GENERATE') ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <?= $this->Html->script('admin/tinymce/tinymce.min.js') ?>
                        <script type="text/javascript">
                            tinymce.init({
                                selector: "textarea",
                                license_key: 'gpl',
                                promotion: false,
                                branding: false,
                                height: 300,
                                width: "100%",
                                language: "<?= $currentLocale ?>",
                                plugins: "code image link",
                                toolbar: "fontselect fontsizeselect bold italic underline strikethrough link image forecolor backcolor alignleft aligncenter alignright alignjustify cut copy paste bullist numlist outdent indent blockquote code"
                            });
                        </script>
                        <textarea
                            id="editor"
                            name="content"
                            cols="30"
                            rows="10"
                        ><?= h($page['content']) ?></textarea>
                    </div>

                    <div class="float-right">
                        <a
                            href="<?= $this->Url->build(['_name' => 'admin_pages_index']) ?>"
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
