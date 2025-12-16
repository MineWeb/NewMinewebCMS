<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NEWS__EDIT') ?></h3>
                </div>

                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_news_edit_ajax'],
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_news_index'])
                    ]) ?>

                    <div class="ajax-msg"></div>

                    <input type="hidden" name="id" value="<?= h($news['id']) ?>">

                    <div class="form-group">
                        <label for="news-title"><?= __('GLOBAL__TITLE') ?></label>
                        <input
                            id="news-title"
                            name="title"
                            class="form-control"
                            value="<?= h($news['title']) ?>"
                            placeholder="<?= __('GLOBAL__TITLE') ?>"
                            type="text"
                        >
                    </div>

                    <div class="form-group">
                        <label for="slug"><?= __('GLOBAL__SLUG') ?></label>
                        <div class="input-group mb-3">

                            <div class="input-group-prepend">
                                <span class="input-group-text"><?= h($this->Url->build('/blog/', true)) ?></span>
                            </div>

                            <input
                                id="slug"
                                name="slug"
                                class="form-control"
                                value="<?= h($news['slug']) ?>"
                                placeholder="<?= __('GLOBAL__SLUG') ?>"
                                type="text"
                            >

                            <div class="input-group-append">
                                <a href="#" id="generate_slug" class="btn btn-info"><?= __('GLOBAL__GENERATE') ?></a>
                            </div>
                        </div>
                    </div>

                    <div class="form-group">
                        <?= $this->Html->script('admin/tinymce/tinymce.min.js') ?>

                        <script>
                            tinymce.init({
                                selector: "textarea#editor",
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

                        <label for="editor"><?= __('GLOBAL__CONTENT') ?></label>
                        <textarea
                            id="editor"
                            name="content"
                            cols="30"
                            rows="10"
                        ><?= h($news['content']) ?></textarea>
                    </div>

                    <div class="form-group">
                        <div class="form-check">
                            <input
                                id="news-published"
                                name="published"
                                type="checkbox"
                                class="form-check-input"
                                value="1"<?= $news['published'] ? ' checked' : '' ?>
                            >
                            <label class="form-check-label" for="news-published">
                                <?= __('NEWS__WANT_TO_PUBLISH') ?>
                            </label>
                        </div>
                    </div>

                    <div class="float-right">
                        <a
                            href="<?= $this->Url->build(['_name' => 'admin_news_index']) ?>"
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
    document.querySelector('#generate_slug')?.addEventListener('click', function (e) {
        e.preventDefault();
        const titleInput = document.querySelector('input[name="title"]');
        const slugInput = document.querySelector('#slug');

        if (!titleInput || !slugInput) {
            return;
        }

        const title = titleInput.value || "";
        slugInput.value = title
            .trim()
            .toLowerCase()
            .replace(/[^a-z0-9]+/g, "-")
            .replace(/^-+|-+$/g, "");
    });
</script>
