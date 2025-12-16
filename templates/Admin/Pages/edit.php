<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-pen mr-2"></i><?= __('PAGE__EDIT') ?>
                            </h3>

                            <div class="d-flex align-items-center">
                                <?php
                                $slug = (string)($page['slug'] ?? '');
                                $publicUrl = $this->Url->build('/p/' . $slug, ['fullBase' => true]);
                                ?>
                                <a href="<?= h($publicUrl) ?>" target="_blank" class="btn btn-outline-secondary btn-sm mr-2">
                                    <i class="fas fa-external-link-alt mr-1"></i><?= __('GLOBAL__VIEW') ?>
                                </a>

                                <a href="<?= $this->Url->build(['_name' => 'admin_pages_index']) ?>"
                                   class="btn btn-default btn-sm">
                                    <i class="fas fa-arrow-left mr-2"></i><?= __('GLOBAL__BACK') ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <?= $this->Form->create(null, [
                                'url' => ['_name' => 'admin_pages_edit_ajax'],
                                'data-ajax' => 'true',
                                'data-redirect-url' => $this->Url->build(['_name' => 'admin_pages_index']),
                            ]) ?>

                            <div class="ajax-msg"></div>

                            <input type="hidden" name="id" value="<?= (int)$page['id'] ?>">

                            <div class="form-group">
                                <label for="page-title"><?= __('GLOBAL__TITLE') ?></label>
                                <input
                                    id="page-title"
                                    name="title"
                                    class="form-control"
                                    type="text"
                                    value="<?= h((string)$page['title']) ?>"
                                    placeholder="<?= __('GLOBAL__TITLE') ?>"
                                    autocomplete="off"
                                >
                            </div>

                            <div class="form-group">
                                <label for="page-slug"><?= __('GLOBAL__SLUG') ?></label>

                                <div class="input-group">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><?= h($this->Url->build('/p/', ['fullBase' => true])) ?></span>
                                    </div>

                                    <input
                                        id="page-slug"
                                        name="slug"
                                        class="form-control"
                                        type="text"
                                        value="<?= h((string)$page['slug']) ?>"
                                        placeholder="<?= __('GLOBAL__SLUG') ?>"
                                        autocomplete="off"
                                    >

                                    <div class="input-group-append">
                                        <button type="button" id="generate_slug" class="btn btn-info">
                                            <i class="fas fa-magic mr-1"></i><?= __('GLOBAL__GENERATE') ?>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="page-content"><?= __('GLOBAL__CONTENT') ?></label>

                                <?= $this->Html->script('admin/tinymce/tinymce.min.js') ?>

                                <script>
                                    tinymce.init({
                                        selector: "#page-content",
                                        license_key: 'gpl',
                                        promotion: false,
                                        branding: false,
                                        height: 380,
                                        width: "100%",
                                        language: "<?= $currentLocale ?>",
                                        plugins: "code image link lists",
                                        toolbar: "fontselect fontsizeselect bold italic underline strikethrough link image forecolor backcolor alignleft aligncenter alignright alignjustify bullist numlist outdent indent blockquote code",
                                        menubar: false,
                                        statusbar: true
                                    });
                                </script>

                                <textarea id="page-content" name="content" cols="30" rows="10"><?= h((string)$page['content']) ?></textarea>
                            </div>

                            <div class="d-flex justify-content-end">
                                <a href="<?= $this->Url->build(['_name' => 'admin_pages_index']) ?>"
                                   class="btn btn-default mr-2">
                                    <i class="fas fa-times mr-1"></i><?= __('GLOBAL__CANCEL') ?>
                                </a>

                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-check mr-1"></i><?= __('GLOBAL__SUBMIT') ?>
                                </button>
                            </div>

                            <?= $this->Form->end() ?>

                        </div>
                    </div>

                </div>

            </div>
        </div>

    </div>
</section>

<script>
    (function () {
        const btn = document.querySelector('#generate_slug');
        const titleEl = document.querySelector('#page-title');
        const slugEl = document.querySelector('#page-slug');

        if (!btn || !titleEl || !slugEl) {
            return;
        }

        btn.addEventListener('click', function (e) {
            e.preventDefault();

            const title = String(titleEl.value || '');
            slugEl.value = title
                .trim()
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, "-")
                .replace(/^-+|-+$/g, "");
        });
    })();
</script>
