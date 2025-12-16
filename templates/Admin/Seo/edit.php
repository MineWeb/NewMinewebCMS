<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-edit mr-2"></i><?= __('SEO__EDIT_PAGE') ?>
                            </h3>

                            <a href="<?= $this->Url->build(['_name' => 'admin_seo_index']) ?>" class="btn btn-default btn-sm mt-2 mt-sm-0">
                                <i class="fas fa-arrow-left mr-2"></i><?= __('GLOBAL__BACK') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <?= $this->Form->create(null, [
                            'url' => ['_name' => 'admin_seo_edit', $page['id']],
                            'type' => 'post',
                            'data-ajax' => 'true',
                            'data-upload-image' => 'true',
                            'data-redirect-url' => $this->Url->build(['_name' => 'admin_seo_index']),
                        ]) ?>

                        <div class="p-3 p-md-4">

                            <div class="ajax-msg"></div>

                            <div class="alert alert-light border d-flex align-items-start mb-4">
                                <i class="fas fa-info-circle mt-1 mr-2"></i>
                                <div class="text-sm mb-0"><?= __('SEO__EDIT_INTRO') ?></div>
                            </div>

                            <div class="card mb-3">
                                <div class="card-body">
                                    <div class="form-group mb-0">
                                        <label for="seo-page" class="mb-1"><?= __('SEO__PAGE') ?></label>
                                        <div class="text-muted text-sm mb-2"><?= __('SEO__PAGE_DESC') ?></div>

                                        <div class="input-group">
                                            <div class="input-group-prepend">
                                                <span class="input-group-text"><i class="fas fa-route"></i></span>
                                            </div>
                                            <input
                                                id="seo-page"
                                                type="text"
                                                name="page"
                                                class="form-control"
                                                value="<?= h((string)$page['page']) ?>"
                                                placeholder="<?= h(__('SEO__PAGE_PLACEHOLDER')) ?>"
                                                autocomplete="off"
                                            >
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <label for="seo-title" class="mb-1"><?= __('SEO__FORM_TITLE') ?></label>
                                                    <span class="badge badge-light js-empty-badge" data-empty-for="#seo-title"><?= __('SEO__KEEP_EMPTY') ?></span>
                                                </div>

                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-heading"></i></span>
                                                    </div>
                                                    <input
                                                        id="seo-title"
                                                        type="text"
                                                        name="title"
                                                        class="form-control"
                                                        value="<?= h((string)($page['title'] ?? '')) ?>"
                                                        autocomplete="off"
                                                        aria-describedby="seo-title-help"
                                                    >
                                                </div>

                                                <small id="seo-title-help" class="form-text text-muted mt-2 mb-0">
                                                    <b>{TITLE}</b> = <?= __('SEO__FORM_TITLE_DESC_T') ?><br>
                                                    <b>{WEBSITE_NAME}</b> = <?= __('SEO__FORM_TITLE_DESC_W') ?><br>
                                                    <?= __('SEO__KEEP_EMPTY_HELP') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <label for="seo-description" class="mb-1"><?= __('SEO__FORM_DESCRIPTION') ?></label>
                                                    <span class="badge badge-light js-empty-badge" data-empty-for="#seo-description"><?= __('SEO__KEEP_EMPTY') ?></span>
                                                </div>

                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="far fa-file-alt"></i></span>
                                                    </div>
                                                    <input
                                                        id="seo-description"
                                                        type="text"
                                                        name="description"
                                                        class="form-control"
                                                        value="<?= h((string)($page['description'] ?? '')) ?>"
                                                        autocomplete="off"
                                                        aria-describedby="seo-description-help"
                                                    >
                                                </div>

                                                <small id="seo-description-help" class="form-text text-muted mt-2 mb-0">
                                                    <?= __('SEO__KEEP_EMPTY_HELP') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card mb-0">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between mb-2">
                                                <div>
                                                    <label class="mb-1"><?= __('SEO__FORM_FAVICON') ?></label>
                                                    <div class="text-muted text-sm"><?= __('SEO__FORM_FAVICON_DESC') ?></div>
                                                </div>
                                                <span class="badge badge-light js-empty-badge" data-empty-for="#seo-favicon-edit-<?= h((string)$page['id']) ?>-root"><?= __('SEO__KEEP_EMPTY') ?></span>
                                            </div>

                                            <?= $this->element('form.input.upload.img', [
                                                'img' => $page['favicon_url'] ?? '',
                                                'field' => 'favicon',
                                                'uid' => 'seo-favicon-edit-' . (string)$page['id'],
                                            ]) ?>

                                            <small class="form-text text-muted mt-2 mb-0">
                                                <?= __('SEO__KEEP_EMPTY_HELP') ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">

                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <label for="seo-img-url" class="mb-1"><?= __('SEO__FORM_IMG_URL') ?></label>
                                                <span class="badge badge-light js-empty-badge" data-empty-for="#seo-img-url"><?= __('SEO__KEEP_EMPTY') ?></span>
                                            </div>

                                            <div class="text-muted text-sm mb-2"><?= __('SEO__FORM_IMG_URL_DESC') ?></div>

                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-link"></i></span>
                                                </div>
                                                <input
                                                    id="seo-img-url"
                                                    type="text"
                                                    name="img_url"
                                                    class="form-control"
                                                    value="<?= h((string)($page['img_url'] ?? '')) ?>"
                                                    placeholder="<?= h(__('SEO__PLACEHOLDER_IMG_URL')) ?>"
                                                    autocomplete="off"
                                                    aria-describedby="seo-img-url-help"
                                                >
                                            </div>

                                            <small id="seo-img-url-help" class="form-text text-muted mt-2 mb-0">
                                                <?= __('SEO__KEEP_EMPTY_HELP') ?>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <label for="seo-theme-color" class="mb-1"><?= __('SEO__FORM_THEME_COLOR') ?></label>
                                                <span class="badge badge-light js-empty-badge" data-empty-for="#seo-theme-color"><?= __('SEO__KEEP_EMPTY') ?></span>
                                            </div>

                                            <div class="text-muted text-sm mb-2"><?= __('SEO__FORM_THEME_COLOR_DESC') ?></div>

                                            <div class="d-flex align-items-center">
                                                <input
                                                    id="seo-theme-color"
                                                    type="color"
                                                    name="theme_color"
                                                    class="form-control"
                                                    value="<?= h((string)($page['theme_color'] ?? '')) ?>"
                                                    style="max-width: 120px;"
                                                    data-color-initial="<?= h((string)($page['theme_color'] ?? '')) ?>"
                                                >
                                                <span class="badge badge-light ml-3" style="min-width: 92px; text-align:center;">
                                                    <span id="seo-theme-color-value"><?= h((string)($page['theme_color'] ?? '')) ?></span>
                                                </span>
                                            </div>

                                            <small class="form-text text-muted mt-2 mb-0">
                                                <?= __('SEO__KEEP_EMPTY_HELP') ?>
                                            </small>
                                        </div>
                                    </div>

                                    <div class="card mb-0">
                                        <div class="card-body">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <label for="seo-twitter-site" class="mb-1"><?= __('SEO__FORM_TWITTER_SITE') ?></label>
                                                <span class="badge badge-light js-empty-badge" data-empty-for="#seo-twitter-site"><?= __('SEO__KEEP_EMPTY') ?></span>
                                            </div>

                                            <div class="text-muted text-sm mb-2"><?= __('SEO__FORM_TWITTER_SITE_DESC') ?></div>

                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">@</span>
                                                </div>
                                                <input
                                                    id="seo-twitter-site"
                                                    type="text"
                                                    name="twitter_site"
                                                    class="form-control"
                                                    value="<?= h((string)($page['twitter_site'] ?? '')) ?>"
                                                    placeholder="<?= h(__('SEO__PLACEHOLDER_TWITTER')) ?>"
                                                    autocomplete="off"
                                                    aria-describedby="seo-twitter-help"
                                                >
                                            </div>

                                            <small id="seo-twitter-help" class="form-text text-muted mt-2 mb-0">
                                                <?= __('SEO__KEEP_EMPTY_HELP') ?>
                                            </small>
                                        </div>
                                    </div>

                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-end mt-3">
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save mr-2"></i><?= __('GLOBAL__SUBMIT') ?>
                                        </button>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <?= $this->Form->end() ?>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        const color = document.getElementById('seo-theme-color');
        const colorValue = document.getElementById('seo-theme-color-value');

        if (color && colorValue) {
            const sync = () => { colorValue.textContent = color.value || ''; };
            color.addEventListener('input', sync);
            sync();
        }

        const isColorEmpty = (input) => {
            const initial = input.getAttribute('data-color-initial') || '';
            const touched = input.getAttribute('data-color-touched') === '1';
            if (initial !== '') return false;
            return !touched;
        };

        const updateBadges = () => {
            const badges = document.querySelectorAll('.js-empty-badge[data-empty-for]');
            for (const badge of badges) {
                const sel = badge.getAttribute('data-empty-for');
                if (!sel) continue;

                const el = document.querySelector(sel);
                if (!el) continue;

                let isEmpty = true;

                if (el.tagName === 'INPUT' || el.tagName === 'TEXTAREA' || el.tagName === 'SELECT') {
                    const type = (el.getAttribute('type') || '').toLowerCase();
                    if (type === 'color') {
                        isEmpty = isColorEmpty(el);
                    } else {
                        isEmpty = ((el.value || '').trim() === '');
                    }
                } else {
                    const hasEdit = !!el.querySelector('input[name$="[edit]"]');
                    const hasDelete = (el.querySelector('input[name$="[delete]"]')?.value || '0') === '1';
                    isEmpty = !(hasEdit || hasDelete);
                }

                if (isEmpty) badge.classList.remove('d-none');
                else badge.classList.add('d-none');
            }
        };

        const scheduleUpdate = () => setTimeout(updateBadges, 0);

        document.addEventListener('input', function (e) {
            const t = e.target;
            if (t && t.matches && t.matches('input[type="color"][data-color-initial]')) {
                t.setAttribute('data-color-touched', '1');
            }
            updateBadges();
        }, true);

        document.addEventListener('change', function (e) {
            const t = e.target;
            if (t && t.matches && t.matches('input[type="color"][data-color-initial]')) {
                t.setAttribute('data-color-touched', '1');
            }
            updateBadges();
        }, true);

        document.addEventListener('click', scheduleUpdate, true);
        document.addEventListener('uploadimg:state', scheduleUpdate);

        updateBadges();
    })();
</script>
