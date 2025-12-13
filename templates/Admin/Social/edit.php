<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('SOCIAL__HOME') ?></h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create(null, [
                        'method' => 'post',
                        'id' => 'add-social-button',
                        'data-ajax' => 'true',
                        'data-upload-image' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_social_index'])
                    ]) ?>
                    <div class="form-group">
                        <label for="select-social-default"><?= __('SOCIAL__BUTTON_SELECT_DEFAULT') ?></label>
                        <select
                            class="form-control"
                            aria-label="<?= __('SOCIAL__BUTTON_SELECT_DEFAULT') ?>"
                            id="select-social-default"
                            name="select_social_default"
                        >
                            <?php $haveSelected = false;
                            foreach ($social_default as $value) { ?>
                                <option
                                    value="<?= strtolower($value['title']) ?>"
                                    <?php if (strtolower($social_button['title']) === strtolower($value['title'])) { echo 'selected'; $haveSelected = true; } ?>
                                >
                                    <?= $value['title'] ?>
                                </option>
                            <?php } ?>
                            <option value="custom" <?php if (!$haveSelected) { echo 'selected'; } ?>>Custom</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="social-title"><?= __('SOCIAL__BUTTON_TITLE') ?></label>
                        <input
                            type="text"
                            name="title"
                            id="social-title"
                            class="form-control global-reset-input"
                            value="<?= h($social_button['title']) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label><?= __('SOCIAL__CHOOSE_TYPE') ?></label>
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                id="choose-is-img"
                                name="type"
                                value="img"
                                <?php if ($social_button_type === 'img') { echo 'checked'; } ?>
                            >
                            <label class="form-check-label" for="choose-is-img"><?= __('SOCIAL__CHOOSE_TYPE_IMG') ?></label>
                        </div>
                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                id="choose-is-icon"
                                name="type"
                                value="icon"
                                <?php if ($social_button_type === 'fa') { echo 'checked'; } ?>
                            >
                            <label class="form-check-label" for="choose-is-icon"><?= __('SOCIAL__CHOOSE_TYPE_ICON') ?></label>
                        </div>
                    </div>

                    <div id="type-is-img" <?php if ($social_button_type !== 'img') { ?>class="d-none"<?php } ?>>
                        <div class="form-group mx-5">
                            <label for="social-img"><?= __('SOCIAL__BUTTON_IMG') ?></label><em> <?= __('SOCIAL__BUTTON_IMG_SIZE') ?></em>
                            <input
                                type="text"
                                id="social-img"
                                name="img"
                                class="form-control img-or-icon-input global-reset-input"
                                placeholder="https://images.google.com"
                                value="<?= h($social_button['extra']) ?>"
                            >
                        </div>
                        <div class="text-right mx-5">
                            <a class="btn btn-default type-cancel"><?= __('SOCIAL__CHOOSE_TYPE_CANCEL') ?></a>
                        </div>
                    </div>

                    <div id="type-is-icon" <?php if ($social_button_type !== 'fa') { ?>class="d-none"<?php } ?>>
                        <div class="form-group mx-5">
                            <label for="social-icon"><?= __('SOCIAL__BUTTON_ICON') ?></label>
                            <p>
                                <?= __('SOCIAL__ICON_DESC') ?>
                                <a
                                    target="_blank"
                                    href="https://fontawesome.com/"
                                    title="Lien vers fontawesome"
                                >
                                    https://fontawesome.com/
                                </a>
                            </p>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">FA</span>
                                </div>
                                <input
                                    type="text"
                                    id="social-icon"
                                    name="icon"
                                    class="form-control img-or-icon-input global-reset-input"
                                    placeholder="fab fa-teamspeak"
                                    value="<?= h($social_button['extra']) ?>"
                                >
                            </div>
                        </div>
                        <div class="text-right mx-5">
                            <a class="btn btn-default type-cancel"><?= __('SOCIAL__CHOOSE_TYPE_CANCEL') ?></a>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="social-url"><?= __('SOCIAL__BUTTON_URL') ?></label>
                        <input
                            type="text"
                            id="social-url"
                            name="url"
                            class="form-control"
                            value="<?= h($social_button['url']) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="social-color"><?= __('SOCIAL__BUTTON_COLOR') ?></label>
                        <input
                            type="color"
                            id="social-color"
                            name="color"
                            class="form-control global-reset-input"
                            value="<?= h($social_button['color']) ?>"
                        >
                    </div>

                    <div class="float-right">
                        <a
                            href="<?= $this->Url->build(['_name' => 'admin_social_index']) ?>"
                            class="btn btn-default"
                        >
                            <?= __('GLOBAL__CANCEL') ?>
                        </a>
                        <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let selectSocialDefault = document.getElementById('select-social-default');
        let socialTitle = document.getElementById('social-title');
        let socialColor = document.getElementById('social-color');
        let typeImg = document.getElementById('type-is-img');
        let typeIcon = document.getElementById('type-is-icon');
        let radioTypes = document.querySelectorAll('input[name="type"]');
        let typeCancels = document.querySelectorAll('.type-cancel');
        let resetInputs = document.querySelectorAll('.global-reset-input');
        let imgOrIconInputs = document.querySelectorAll('.img-or-icon-input');

        function dispatchData(title, extra, color) {
            if (socialTitle) {
                socialTitle.value = title;
            }
            if (extra && extra.length > 0) {
                if (extra.indexOf('fa-') !== -1) {
                    let radioIcon = document.getElementById('choose-is-icon');
                    if (radioIcon) {
                        radioIcon.checked = true;
                    }
                    if (typeImg) {
                        typeImg.classList.add('d-none');
                    }
                    if (typeIcon) {
                        typeIcon.classList.remove('d-none');
                        let iconInput = document.getElementById('social-icon');
                        if (iconInput) {
                            iconInput.value = extra;
                        }
                    }
                } else {
                    let radioImg = document.getElementById('choose-is-img');
                    if (radioImg) {
                        radioImg.checked = true;
                    }
                    if (typeImg) {
                        typeImg.classList.remove('d-none');
                        let imgInput = document.getElementById('social-img');
                        if (imgInput) {
                            imgInput.value = extra;
                        }
                    }
                    if (typeIcon) {
                        typeIcon.classList.add('d-none');
                    }
                }
            }
            if (socialColor) {
                socialColor.value = color;
            }
        }

        function globalReset() {
            radioTypes.forEach(function (radio) {
                radio.checked = false;
            });
            if (typeImg) {
                typeImg.classList.add('d-none');
            }
            if (typeIcon) {
                typeIcon.classList.add('d-none');
            }
            resetInputs.forEach(function (input) {
                input.value = '';
            });
        }

        function getSelectValue(value) {
            switch (value) {
            <?php foreach ($social_default as $value) { ?>
                case '<?= strtolower($value['title']) ?>':
                    dispatchData('<?= addslashes($value['title']) ?>', '<?= addslashes($value['extra']) ?>', '<?= addslashes($value['color']) ?>');
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

        radioTypes.forEach(function (radio) {
            radio.addEventListener('change', function () {
                if (radio.checked) {
                    if (radio.value === 'img') {
                        if (typeImg) {
                            typeImg.classList.remove('d-none');
                        }
                        if (typeIcon) {
                            typeIcon.classList.add('d-none');
                        }
                    }
                    if (radio.value === 'icon') {
                        if (typeImg) {
                            typeImg.classList.add('d-none');
                        }
                        if (typeIcon) {
                            typeIcon.classList.remove('d-none');
                        }
                    }
                }
            });
        });

        typeCancels.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();
                radioTypes.forEach(function (radio) {
                    radio.checked = false;
                });
                if (typeImg) {
                    typeImg.classList.add('d-none');
                }
                if (typeIcon) {
                    typeIcon.classList.add('d-none');
                }
                imgOrIconInputs.forEach(function (input) {
                    input.value = '';
                });
            });
        });
    });
</script>
