<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="callout callout-danger mb-3">
                    <div class="d-flex align-items-start">
                        <i class="fas fa-exclamation-triangle mt-1 mr-2"></i>
                        <div>
                            <h5 class="mb-1"><?= __('SEO__CALLOUT') ?></h5>
                            <div class="text-sm mb-0"><?= __('SEO__CALLOUT_MESSAGE') ?></div>
                        </div>
                    </div>
                </div>

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title">
                            <i class="fas fa-sliders-h mr-2"></i><?= __('SEO__TITLE_DEFAULT') ?>
                        </h3>
                    </div>

                    <div class="card-body p-0">
                        <?= $this->Form->create(null, [
                            'url' => ['_name' => 'admin_seo_edit_default'],
                            'data-ajax' => 'true',
                            'data-upload-image' => 'true',
                            'data-redirect-url' => $this->Url->build(['_name' => 'admin_seo_index']),
                        ]) ?>

                        <div class="p-3 p-md-4">

                            <div class="ajax-msg"></div>

                            <div class="alert alert-light border d-flex align-items-start mb-4">
                                <i class="fas fa-info-circle mt-1 mr-2"></i>
                                <div class="text-sm mb-0"><?= __('SEO__FORM_INTRO') ?></div>
                            </div>

                            <div class="row">

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <label for="seo-title" class="mb-1"><?= __('SEO__FORM_TITLE') ?></label>

                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="fas fa-heading"></i></span>
                                                    </div>
                                                    <input
                                                        id="seo-title"
                                                        type="text"
                                                        class="form-control"
                                                        name="title"
                                                        value="<?= empty($default['title']) ? '{TITLE} - {WEBSITE_NAME}' : h((string)$default['title']) ?>"
                                                        aria-describedby="seo-title-help"
                                                        autocomplete="off"
                                                    >
                                                </div>

                                                <small id="seo-title-help" class="form-text text-muted mt-2 mb-0">
                                                    <b>{TITLE}</b> = <?= __('SEO__FORM_TITLE_DESC_T') ?><br>
                                                    <b>{WEBSITE_NAME}</b> = <?= __('SEO__FORM_TITLE_DESC_W') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <label for="seo-description"
                                                       class="mb-1"><?= __('SEO__FORM_DESCRIPTION') ?></label>

                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i
                                                                class="far fa-file-alt"></i></span>
                                                    </div>
                                                    <input
                                                        id="seo-description"
                                                        type="text"
                                                        class="form-control"
                                                        name="description"
                                                        value="<?= h((string)($default['description'] ?? '')) ?>"
                                                        aria-describedby="seo-description-help"
                                                        autocomplete="off"
                                                    >
                                                </div>

                                                <small id="seo-description-help" class="form-text text-muted mt-2 mb-0">
                                                    <?= __('SEO__FORM_DESCRIPTION_HELP') ?>
                                                </small>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card mb-0">
                                        <div class="card-body">
                                            <label class="mb-1"><?= __('SEO__FORM_FAVICON') ?></label>
                                            <div
                                                class="text-muted text-sm mb-2"><?= __('SEO__FORM_FAVICON_DESC') ?></div>

                                            <?= $this->element('form.input.upload.img', [
                                                'img' => $default['favicon_url'] ?? '',
                                                'field' => 'favicon',
                                                'uid' => 'seo-favicon',
                                            ]) ?>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">

                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <label for="seo-img-url" class="mb-1"><?= __('SEO__FORM_IMG_URL') ?></label>
                                            <div
                                                class="text-muted text-sm mb-2"><?= __('SEO__FORM_IMG_URL_DESC') ?></div>

                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><i class="fas fa-link"></i></span>
                                                </div>
                                                <input
                                                    id="seo-img-url"
                                                    type="text"
                                                    class="form-control"
                                                    name="img_url"
                                                    value="<?= h((string)($default['img_url'] ?? '')) ?>"
                                                    placeholder="<?= h(__('SEO__PLACEHOLDER_IMG_URL')) ?>"
                                                    autocomplete="off"
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <label for="seo-theme-color"
                                                   class="mb-1"><?= __('SEO__FORM_THEME_COLOR') ?></label>
                                            <div
                                                class="text-muted text-sm mb-2"><?= __('SEO__FORM_THEME_COLOR_DESC') ?></div>

                                            <div class="d-flex align-items-center">
                                                <input
                                                    id="seo-theme-color"
                                                    type="color"
                                                    class="form-control"
                                                    name="theme_color"
                                                    value="<?= h((string)($default['theme_color'] ?? '')) ?>"
                                                    style="max-width: 120px;"
                                                >
                                                <span class="badge badge-light ml-3"
                                                      style="min-width: 92px; text-align:center;">
                                                    <span
                                                        id="seo-theme-color-value"><?= h((string)($default['theme_color'] ?? '')) ?></span>
                                                </span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card mb-0">
                                        <div class="card-body">
                                            <label for="seo-twitter"
                                                   class="mb-1"><?= __('SEO__FORM_TWITTER_SITE') ?></label>
                                            <div
                                                class="text-muted text-sm mb-2"><?= __('SEO__FORM_TWITTER_SITE_DESC') ?></div>

                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text">@</span>
                                                </div>
                                                <input
                                                    id="seo-twitter"
                                                    type="text"
                                                    class="form-control"
                                                    name="twitter_site"
                                                    value="<?= h((string)($default['twitter_site'] ?? '')) ?>"
                                                    placeholder="<?= h(__('SEO__PLACEHOLDER_TWITTER')) ?>"
                                                    autocomplete="off"
                                                >
                                            </div>
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

                <div class="card card-outline card-info mt-3">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-list mr-2"></i><?= __('SEO__TITLE_OTHER') ?>
                            </h3>

                            <a class="btn btn-info btn-sm"
                               href="<?= $this->Url->build(['_name' => 'admin_seo_add']) ?>">
                                <i class="fas fa-plus mr-2"></i><?= __('SEO__ADD_PAGE') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <div class="text-muted text-sm mb-3">
                                <i class="fas fa-info-circle mr-2"></i><?= __('SEO__ADD_INTRO') ?>
                            </div>

                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th style="width: 22%;"><?= __('SEO__PAGE') ?></th>
                                        <th><?= __('SEO__FORM_TITLE') ?></th>
                                        <th><?= __('SEO__FORM_DESCRIPTION') ?></th>
                                        <th style="width: 16%;"><?= __('SEO__FORM_FAVICON') ?></th>
                                        <th style="width: 16%;"><?= __('SEO__FORM_IMG_URL') ?></th>
                                        <th class="text-right"
                                            style="width: 1%; white-space: nowrap;"><?= __('GLOBAL__ACTIONS') ?></th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php if (empty($seo_other) || count($seo_other) === 0) { ?>
                                        <tr>
                                            <td colspan="6" class="text-center text-muted py-4">
                                                <i class="far fa-folder-open mr-2"></i><?= __('TABLE__NO_RESULT') ?>
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($seo_other as $v) { ?>
                                            <?php
                                            $pageVal = (string)($v['page'] ?? '');
                                            $titleVal = (string)($v['title'] ?? '');
                                            $descVal = (string)($v['description'] ?? '');
                                            $faviconVal = (string)($v['favicon_url'] ?? '');
                                            $imgUrlVal = (string)($v['img_url'] ?? '');
                                            ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <div class="font-weight-bold">
                                                        <i class="fas fa-route text-muted mr-1"></i><?= h($pageVal) ?>
                                                    </div>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if ($titleVal !== '') { ?>
                                                        <span class="text-truncate d-inline-block"
                                                              style="max-width: 420px;" title="<?= h($titleVal) ?>">
                                                        <?= h($titleVal) ?>
                                        </span>
                                                    <?php } else { ?>
                                                        <span
                                                            class="badge badge-light"><?= __('SEO__DEFAULT_VALUE') ?></span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if ($descVal !== '') { ?>
                                                        <span class="text-truncate d-inline-block"
                                                              style="max-width: 420px;" title="<?= h($descVal) ?>">
                                                        <?= h($descVal) ?>
                                        </span>
                                                    <?php } else { ?>
                                                        <span
                                                            class="badge badge-light"><?= __('SEO__DEFAULT_VALUE') ?></span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if ($faviconVal !== '') { ?>
                                                        <a href="<?= h($faviconVal) ?>" target="_blank" rel="noopener"
                                                           class="text-truncate d-inline-block"
                                                           style="max-width: 220px;" title="<?= h($faviconVal) ?>">
                                                            <i class="far fa-image mr-1"></i><?= h($faviconVal) ?>
                                                        </a>
                                                    <?php } else { ?>
                                                        <span
                                                            class="badge badge-light"><?= __('SEO__DEFAULT_VALUE') ?></span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if ($imgUrlVal !== '') { ?>
                                                        <a href="<?= h($imgUrlVal) ?>" target="_blank" rel="noopener"
                                                           class="text-truncate d-inline-block"
                                                           style="max-width: 220px;" title="<?= h($imgUrlVal) ?>">
                                                            <i class="fas fa-link mr-1"></i><?= h($imgUrlVal) ?>
                                                        </a>
                                                    <?php } else { ?>
                                                        <span
                                                            class="badge badge-light"><?= __('SEO__DEFAULT_VALUE') ?></span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle text-right text-nowrap">
                                                    <div class="btn-group btn-group-sm" role="group"
                                                         aria-label="<?= h(__('GLOBAL__ACTIONS')) ?>">
                                                        <a class="btn btn-info"
                                                           href="<?= $this->Url->build(['_name' => 'admin_seo_edit', $v['id']]) ?>">
                                                            <i class="fas fa-edit mr-1"></i><?= __('GLOBAL__EDIT') ?>
                                                        </a>

                                                        <a class="btn btn-danger js-confirm-del"
                                                           href="<?= $this->Url->build(['_name' => 'admin_seo_delete', $v['id']]) ?>"
                                                           data-confirm="<?= h(__('SEO__CONFIRM_DELETE')) ?>">
                                                            <i class="fas fa-trash mr-1"></i><?= __('GLOBAL__DELETE') ?>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>

                                </table>
                            </div>

                        </div>
                    </div>
                </div>


            </div>
        </div>
    </div>
</section>

<script>
    (function () {
        const delLinks = document.querySelectorAll('a.js-confirm-del');

        for (const a of delLinks) {
            a.addEventListener('click', function (e) {
                const msg = a.getAttribute('data-confirm') || 'Confirmer la suppression ?';
                if (!window.confirm(msg)) {
                    e.preventDefault();
                    return;
                }
                if (typeof window.confirmDel === 'function') {
                    e.preventDefault();
                    window.confirmDel(a.getAttribute('href'));
                }
            });
        }

        const color = document.getElementById('seo-theme-color');
        const colorValue = document.getElementById('seo-theme-color-value');

        if (color && colorValue) {
            const sync = () => {
                colorValue.textContent = color.value || '';
            };
            color.addEventListener('input', sync);
            sync();
        }
    })();
</script>
