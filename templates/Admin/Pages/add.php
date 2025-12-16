<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('PAGE__ADD') ?></h3>
                </div>

                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_pages_add_ajax'],
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_pages_index'])
                    ]) ?>

                    <div class="ajax-msg"></div>

                    <div class="form-group">
                        <label for="title"><?= __('GLOBAL__TITLE') ?></label>
                        <input
                            id="title"
                            name="title"
                            class="form-control"
                            placeholder="<?= __('GLOBAL__TITLE') ?>"
                            type="text">
                    </div>

                    <div class="form-group">
                        <label for="slug"><?= __('GLOBAL__SLUG') ?></label>

                        <div class="input-group mb-3">

                            <div class="input-group-prepend">
                                <span class="input-group-text"><?= h($this->Url->build('/p/', true)) ?></span>
                            </div>

                            <input
                                id="slug"
                                name="slug"
                                class="form-control"
                                placeholder="<?= __('GLOBAL__SLUG') ?>"
                                type="text">

                            <div class="input-group-append">
                                <a href="#" id="generate_slug" class="btn btn-info">
                                    <?= __('GLOBAL__GENERATE') ?>
                                </a>
                            </div>

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
                                width: "100%",
                                language: "<?= $currentLocale ?>",
                                plugins: "code image link",
                                toolbar: "fontselect fontsizeselect bold italic underline strikethrough image link forecolor backcolor alignleft aligncenter alignright alignjustify cut copy paste bullist numlist outdent indent blockquote code"
                            });
                        </script>

                        <textarea id="editor" name="content" cols="30" rows="10"></textarea>
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

<script>
    document.querySelector('#generate_slug')?.addEventListener('click', e => {
        e.preventDefault();

        const title = document.querySelector('#title')?.value || "";

        document.querySelector('#slug').value = title
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/^-+|-+$/g, "");
    });
</script>
