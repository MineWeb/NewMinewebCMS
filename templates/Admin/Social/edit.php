<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-edit mr-2"></i><?= __('SOCIAL__EDIT') ?>
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
                                'id' => 'edit-social-button',
                                'data-ajax' => 'true',
                                'data-redirect-url' => $this->Url->build(['_name' => 'admin_social_index']),
                            ]) ?>

                            <?= $this->Form->hidden('id', ['value' => (int)$social_button['id']]) ?>

                            <div class="ajax-msg"></div>

                            <div class="row">
                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <label for="select-social-default" class="mb-1"><?= __('SOCIAL__BUTTON_SELECT_DEFAULT') ?></label>
                                                <select
                                                    class="form-control"
                                                    aria-label="<?= __('SOCIAL__BUTTON_SELECT_DEFAULT') ?>"
                                                    id="select-social-default"
                                                    name="select_social_default"
                                                >
                                                    <?php
                                                    $haveSelected = false;
                                                    foreach ($social_default as $value) {
                                                        $isSelected = strtolower((string)$social_button['title']) === strtolower((string)$value['title']);
                                                        if ($isSelected) {
                                                            $haveSelected = true;
                                                        }
                                                        ?>
                                                        <option value="<?= h(strtolower((string)$value['title'])) ?>"<?= $isSelected ? ' selected' : '' ?>>
                                                            <?= h((string)$value['title']) ?>
                                                        </option>
                                                    <?php } ?>
                                                    <option value="custom"<?= !$haveSelected ? ' selected' : '' ?>>Custom</option>
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
                                                <input
                                                    type="text"
                                                    name="title"
                                                    id="social-title"
                                                    class="form-control"
                                                    value="<?= h((string)$social_button['title']) ?>"
                                                    autocomplete="off"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <label class="mb-2"><?= __('SOCIAL__CHOOSE_TYPE') ?></label>

                                            <div class="custom-control custom-radio">
                                                <input
                                                    class="custom-control-input"
                                                    type="radio"
                                                    id="choose-is-img"
                                                    name="type"
                                                    value="img"
                                                >
                                                <label class="custom-control-label" for="choose-is-img"><?= __('SOCIAL__CHOOSE_TYPE_IMG') ?></label>
                                            </div>

                                            <div class="custom-control custom-radio mt-2 mb-0">
                                                <input
                                                    class="custom-control-input"
                                                    type="radio"
                                                    id="choose-is-icon"
                                                    name="type"
                                                    value="icon"
                                                >
                                                <label class="custom-control-label" for="choose-is-icon"><?= __('SOCIAL__CHOOSE_TYPE_ICON') ?></label>
                                            </div>

                                            <small class="form-text text-muted mt-2 mb-0">
                                                <?= __('SOCIAL__CHOOSE_TYPE') ?>
                                            </small>
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
                                                            id="social-img"
                                                            name="img"
                                                            class="form-control"
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
                                                        id="social-icon"
                                                        name="icon"
                                                        class="form-control"
                                                        placeholder="fab fa-discord"
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
                                                    <input
                                                        type="text"
                                                        id="social-url"
                                                        name="url"
                                                        class="form-control"
                                                        value="<?= h((string)$social_button['url']) ?>"
                                                        autocomplete="off"
                                                    >
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
                                                        <span id="social-color-value"><?= h((string)$social_button['color']) ?></span>
                                                    </span>
                                                </div>

                                                <div class="d-flex align-items-center">
                                                    <input
                                                        type="color"
                                                        id="social-color"
                                                        name="color"
                                                        class="form-control"
                                                        value="<?= h((string)$social_button['color']) ?>"
                                                        style="max-width: 120px;"
                                                        data-color-initial="<?= h((string)$social_button['color']) ?>"
                                                    >
                                                </div>
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
        const selectSocialDefault = document.getElementById('select-social-default');
        const socialTitle = document.getElementById('social-title');
        const socialColor = document.getElementById('social-color');
        const colorValue = document.getElementById('social-color-value');

        const typeImg = document.getElementById('type-is-img');
        const typeIcon = document.getElementById('type-is-icon');

        const radioImg = document.getElementById('choose-is-img');
        const radioIcon = document.getElementById('choose-is-icon');

        const imgInput = document.getElementById('social-img');
        const iconInput = document.getElementById('social-icon');

        const previewImg = document.getElementById('social-preview-img');
        const previewIcon = document.getElementById('social-preview-icon');

        const typeCancels = Array.from(document.querySelectorAll('.type-cancel'));

        const initialExtra = <?= json_encode((string)($social_button['extra'] ?? '')) ?>;
        const initialType = <?= json_encode((string)($social_button_type ?? '')) ?>;

        function escapeHtml(s) {
            return String(s || '')
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        function setColorBadge() {
            if (colorValue && socialColor) {
                colorValue.textContent = socialColor.value || '';
            }
        }

        function setPreview() {
            if (previewImg && imgInput) {
                const v = String(imgInput.value || '');
                previewImg.innerHTML = v
                    ? '<a href="' + escapeHtml(v) + '" target="_blank" rel="noopener" class="text-decoration-none"><i class="far fa-image mr-1"></i>' + escapeHtml(v) + '</a>'
                    : '';
            }

            if (previewIcon && iconInput) {
                const v = String(iconInput.value || '');
                previewIcon.innerHTML = v
                    ? '<span class="text-muted"><i class="' + escapeHtml(v) + ' mr-2"></i>' + escapeHtml(v) + '</span>'
                    : '';
            }
        }

        function clearTypeInputs() {
            if (imgInput) imgInput.value = '';
            if (iconInput) iconInput.value = '';
            setPreview();
        }

        function showType(which) {
            if (typeImg) typeImg.classList.toggle('d-none', which !== 'img');
            if (typeIcon) typeIcon.classList.toggle('d-none', which !== 'icon');
            setPreview();
        }

        function setRadio(which) {
            if (radioImg) radioImg.checked = (which === 'img');
            if (radioIcon) radioIcon.checked = (which === 'icon');
        }

        function applyType(which) {
            setRadio(which);
            showType(which);

            if (which === 'img') {
                if (iconInput) iconInput.value = '';
            } else if (which === 'icon') {
                if (imgInput) imgInput.value = '';
            } else {
                if (radioImg) radioImg.checked = false;
                if (radioIcon) radioIcon.checked = false;
                if (typeImg) typeImg.classList.add('d-none');
                if (typeIcon) typeIcon.classList.add('d-none');
            }

            setPreview();
        }

        function inferTypeFromExtra(extra) {
            const v = String(extra || '').trim();
            if (!v) return '';
            return v.indexOf('fa-') !== -1 ? 'icon' : 'img';
        }

        function dispatchData(title, extra, color) {
            if (socialTitle) socialTitle.value = title || '';
            if (socialColor) socialColor.value = color || '';
            setColorBadge();

            const inferred = inferTypeFromExtra(extra);
            clearTypeInputs();

            if (inferred === 'icon') {
                if (iconInput) iconInput.value = extra || '';
                applyType('icon');
            } else if (inferred === 'img') {
                if (imgInput) imgInput.value = extra || '';
                applyType('img');
            } else {
                applyType('');
            }
        }

        function globalReset() {
            if (socialTitle) socialTitle.value = '';
            if (socialColor) socialColor.value = '';
            setColorBadge();
            clearTypeInputs();
            applyType('');
        }

        function getSelectValue(value) {
            switch (value) {
            <?php foreach ($social_default as $v) { ?>
                case '<?= strtolower((string)$v['title']) ?>':
                    dispatchData(
                        '<?= addslashes((string)$v['title']) ?>',
                        '<?= addslashes((string)$v['extra']) ?>',
                        '<?= addslashes((string)$v['color']) ?>'
                    );
                    break;
            <?php } ?>
                default:
                    globalReset();
                    break;
            }
        }

        if (selectSocialDefault) {
            selectSocialDefault.addEventListener('change', function () {
                getSelectValue(selectSocialDefault.value);
            });
        }

        if (radioImg) {
            radioImg.addEventListener('change', function () {
                if (radioImg.checked) {
                    applyType('img');
                }
            });
        }

        if (radioIcon) {
            radioIcon.addEventListener('change', function () {
                if (radioIcon.checked) {
                    applyType('icon');
                }
            });
        }

        typeCancels.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                clearTypeInputs();
                applyType('');
            });
        });

        if (socialColor) {
            socialColor.addEventListener('input', setColorBadge);
            setColorBadge();
        }

        document.addEventListener('input', function (e) {
            const t = e.target;
            if (!t) return;

            if (t.id === 'social-img') {
                if (String(imgInput.value || '').trim() !== '') {
                    setRadio('img');
                    showType('img');
                }
                setPreview();
                return;
            }

            if (t.id === 'social-icon') {
                if (String(iconInput.value || '').trim() !== '') {
                    setRadio('icon');
                    showType('icon');
                }
                setPreview();
                return;
            }
        }, true);

        const startType = (initialType === 'fa') ? 'icon' : (initialType === 'img' ? 'img' : '');
        const inferredStart = startType || inferTypeFromExtra(initialExtra);

        if (inferredStart === 'icon') {
            if (iconInput) iconInput.value = initialExtra || '';
            applyType('icon');
        } else if (inferredStart === 'img') {
            if (imgInput) imgInput.value = initialExtra || '';
            applyType('img');
        } else {
            applyType('');
        }

        setPreview();
        setColorBadge();
    });
</script>
