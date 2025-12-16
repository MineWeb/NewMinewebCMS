<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-plus mr-2"></i><?= __('SOCIAL__ADD') ?>
                            </h3>

                            <a href="<?= $this->Url->build(['_name' => 'admin_social_index']) ?>"
                               class="btn btn-default btn-sm">
                                <i class="fas fa-arrow-left mr-2"></i><?= __('GLOBAL__BACK') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <?= $this->Form->create(null, [
                                'method' => 'post',
                                'data-ajax' => 'true',
                                'data-upload-image' => 'true',
                                'data-redirect-url' => $this->Url->build(['_name' => 'admin_social_index']),
                                'id' => 'add-social-button'
                            ]) ?>

                            <div class="ajax-msg"></div>

                            <div class="row">

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <label for="select-social-default" class="mb-1"><?= __('SOCIAL__BUTTON_SELECT_DEFAULT') ?></label>
                                                <select class="form-control" id="select-social-default" name="default_social">
                                                    <?php foreach ($social_default as $value) { ?>
                                                        <option value="<?= h(strtolower((string)$value['title'])) ?>"><?= h((string)$value['title']) ?></option>
                                                    <?php } ?>
                                                    <option value="custom">Custom</option>
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <label for="social-title" class="mb-1"><?= __('SOCIAL__BUTTON_TITLE') ?></label>
                                                <input type="text" name="title" class="form-control global-reset-input" id="social-title" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <span class="d-block mb-2"><?= __('SOCIAL__CHOOSE_TYPE') ?></span>

                                            <div class="custom-control custom-radio">
                                                <input class="custom-control-input" type="radio" id="choose-is-img" name="type" value="img">
                                                <label class="custom-control-label" for="choose-is-img"><?= __('SOCIAL__CHOOSE_TYPE_IMG') ?></label>
                                            </div>

                                            <div class="custom-control custom-radio mt-2 mb-0">
                                                <input class="custom-control-input" type="radio" id="choose-is-icon" name="type" value="icon">
                                                <label class="custom-control-label" for="choose-is-icon"><?= __('SOCIAL__CHOOSE_TYPE_ICON') ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">

                                    <div id="type-is-img" class="d-none">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div class="form-group mb-2">
                                                    <label for="social-img" class="mb-1"><?= __('SOCIAL__BUTTON_IMG') ?></label>
                                                    <div class="text-muted text-sm mb-2"><?= __('SOCIAL__BUTTON_IMG_SIZE') ?></div>

                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-image"></i></span>
                                                        </div>
                                                        <input
                                                            type="text"
                                                            name="img"
                                                            id="social-img"
                                                            class="form-control img-or-icon-input global-reset-input"
                                                            placeholder="https://images.google.com"
                                                            autocomplete="off"
                                                        >
                                                    </div>
                                                </div>

                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="text-muted text-sm" id="social-preview-img"></div>
                                                    <button type="button" class="btn btn-default btn-sm type-cancel"><?= __('SOCIAL__CHOOSE_TYPE_CANCEL') ?></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    <div id="type-is-icon" class="d-none">
                                        <div class="card mb-3">
                                            <div class="card-body">
                                                <div class="d-flex align-items-center justify-content-between mb-2">
                                                    <div>
                                                        <label for="social-icon" class="mb-1"><?= __('SOCIAL__BUTTON_ICON') ?></label>
                                                        <div class="text-muted text-sm"><?= __('SOCIAL__ICON_DESC') ?></div>
                                                    </div>

                                                    <a class="btn btn-outline-secondary btn-xs" target="_blank" rel="noopener" href="https://fontawesome.com/">
                                                        <i class="fas fa-external-link-alt mr-1"></i>Font Awesome
                                                    </a>
                                                </div>

                                                <div class="input-group mb-2">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">FA</span>
                                                    </div>
                                                    <input
                                                        type="text"
                                                        name="icon"
                                                        id="social-icon"
                                                        class="form-control img-or-icon-input global-reset-input"
                                                        placeholder="fab fa-teamspeak"
                                                        autocomplete="off"
                                                    >
                                                </div>

                                                <div class="d-flex align-items-center justify-content-between">
                                                    <div class="text-muted text-sm" id="social-preview-icon"></div>
                                                    <button type="button" class="btn btn-default btn-sm type-cancel"><?= __('SOCIAL__CHOOSE_TYPE_CANCEL') ?></button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                </div>

                                <div class="col-lg-8">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <label for="social-url" class="mb-1"><?= __('SOCIAL__BUTTON_URL') ?></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-link"></i></span>
                                                    </div>
                                                    <input type="text" name="url" id="social-url" class="form-control" autocomplete="off">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-4">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <label for="social-color" class="mb-1"><?= __('SOCIAL__BUTTON_COLOR') ?></label>
                                                    <span class="badge badge-light" style="min-width: 88px; text-align:center;">
                                                        <span id="social-color-value"></span>
                                                    </span>
                                                </div>

                                                <input type="color" name="color" id="social-color" class="form-control global-reset-input" style="max-width: 120px;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-end">
                                        <a href="<?= $this->Url->build(['_name' => 'admin_social_index']) ?>" class="btn btn-default mr-2">
                                            <?= __('GLOBAL__CANCEL') ?>
                                        </a>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save mr-2"></i><?= __('GLOBAL__SUBMIT') ?>
                                        </button>
                                    </div>
                                </div>

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
    document.addEventListener('DOMContentLoaded', function () {
        let defaultValue = "<?= h(strtolower((string)$social_default[0]['title'])) ?>";

        let selectDefault = document.getElementById('select-social-default');
        let titleInput = document.getElementById('social-title');
        let colorInput = document.getElementById('social-color');
        let colorValue = document.getElementById('social-color-value');

        let typeImg = document.getElementById('type-is-img');
        let typeIcon = document.getElementById('type-is-icon');

        let previewImg = document.getElementById('social-preview-img');
        let previewIcon = document.getElementById('social-preview-icon');

        function setColorBadge() {
            if (colorValue && colorInput) {
                colorValue.textContent = colorInput.value || '';
            }
        }

        function setPreview() {
            if (previewImg) {
                let imgInput = document.getElementById('social-img');
                let v = imgInput ? String(imgInput.value || '') : '';
                previewImg.innerHTML = v ? '<a href="' + v.replace(/"/g, '&quot;') + '" target="_blank" rel="noopener" class="text-decoration-none"><i class="far fa-image mr-1"></i>' + v.replace(/</g, '&lt;') + '</a>' : '';
            }
            if (previewIcon) {
                let iconInput = document.getElementById('social-icon');
                let v = iconInput ? String(iconInput.value || '') : '';
                previewIcon.innerHTML = v ? '<span class="text-muted"><i class="' + v.replace(/"/g, '&quot;') + ' mr-2"></i>' + v.replace(/</g, '&lt;') + '</span>' : '';
            }
        }

        function hideTypes() {
            if (typeImg) typeImg.classList.add('d-none');
            if (typeIcon) typeIcon.classList.add('d-none');
            setPreview();
        }

        function resetGlobal() {
            document.querySelectorAll('.global-reset-input').forEach(function (input) {
                input.value = '';
            });
            document.querySelectorAll('input[name="type"]').forEach(function (r) {
                r.checked = false;
            });
            hideTypes();
            setColorBadge();
        }

        function dispatchData(title, extra, color) {
            if (titleInput) titleInput.value = title;
            if (colorInput) colorInput.value = color;
            setColorBadge();

            if (extra && extra.length > 0) {
                if (extra.indexOf('fa-') !== -1) {
                    let iconRadio = document.getElementById('choose-is-icon');
                    if (iconRadio) iconRadio.checked = true;

                    if (typeIcon) typeIcon.classList.remove('d-none');
                    if (typeImg) typeImg.classList.add('d-none');

                    let iconInput = document.getElementById('social-icon');
                    if (iconInput) iconInput.value = extra;
                } else {
                    let imgRadio = document.getElementById('choose-is-img');
                    if (imgRadio) imgRadio.checked = true;

                    if (typeImg) typeImg.classList.remove('d-none');
                    if (typeIcon) typeIcon.classList.add('d-none');

                    let imgInput = document.getElementById('social-img');
                    if (imgInput) imgInput.value = extra;
                }
            } else {
                hideTypes();
            }

            setPreview();
        }

        function getSelectValue(v) {
            switch (v) {
            <?php foreach ($social_default as $value) { ?>
                case '<?= strtolower((string)$value['title']) ?>':
                    dispatchData('<?= addslashes((string)$value['title']) ?>', '<?= addslashes((string)$value['extra']) ?>', '<?= addslashes((string)$value['color']) ?>');
                    break;
            <?php } ?>
                default:
                    resetGlobal();
                    break;
            }
        }

        getSelectValue(defaultValue);

        if (selectDefault) {
            selectDefault.addEventListener('change', function () {
                getSelectValue(this.value);
            });
        }

        document.querySelectorAll('input[name="type"]').forEach(function (r) {
            r.addEventListener('change', function () {
                if (!r.checked) return;

                if (r.value === 'img') {
                    if (typeImg) typeImg.classList.remove('d-none');
                    if (typeIcon) typeIcon.classList.add('d-none');
                }
                if (r.value === 'icon') {
                    if (typeIcon) typeIcon.classList.remove('d-none');
                    if (typeImg) typeImg.classList.add('d-none');
                }
                setPreview();
            });
        });

        document.querySelectorAll('.type-cancel').forEach(function (b) {
            b.addEventListener('click', function () {
                document.querySelectorAll('input[name="type"]').forEach(function (r) {
                    r.checked = false;
                });
                document.querySelectorAll('.img-or-icon-input').forEach(function (input) {
                    input.value = '';
                });
                hideTypes();
            });
        });

        if (colorInput) {
            colorInput.addEventListener('input', setColorBadge);
            setColorBadge();
        }

        document.addEventListener('input', function (e) {
            let t = e.target;
            if (!t) return;
            if (t.id === 'social-img' || t.id === 'social-icon') {
                setPreview();
            }
        }, true);

        setPreview();
    });
</script>
