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
                        'url' => ['_name' => 'admin_social_save_ajax'],
                        'data-ajax' => 'true',
                        'data-upload-image' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_social_index']),
                        'id' => 'add-social-button'
                    ]) ?>

                    <div class="form-group">
                        <label for="select-social-default"><?= __('SOCIAL__BUTTON_SELECT_DEFAULT') ?></label>
                        <select class="form-control" id="select-social-default" name="default_social">
                            <?php foreach ($social_default as $value) { ?>
                                <option value="<?= strtolower($value['title']) ?>"><?= $value['title'] ?></option>
                            <?php } ?>
                            <option value="custom">Custom</option>
                        </select>
                    </div>

                    <div class="form-group">
                        <label for="social-title"><?= __('SOCIAL__BUTTON_TITLE') ?></label>
                        <input
                            type="text"
                            name="title"
                            class="form-control global-reset-input"
                            id="social-title"
                        >
                    </div>

                    <div class="form-group">
                        <span class="d-block mb-2"><?= __('SOCIAL__CHOOSE_TYPE') ?></span>

                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                id="choose-is-img"
                                name="type"
                                value="img"
                            >
                            <label class="form-check-label" for="choose-is-img">
                                <?= __('SOCIAL__CHOOSE_TYPE_IMG') ?>
                            </label>
                        </div>

                        <div class="form-check">
                            <input
                                class="form-check-input"
                                type="radio"
                                id="choose-is-icon"
                                name="type"
                                value="icon"
                            >
                            <label class="form-check-label" for="choose-is-icon">
                                <?= __('SOCIAL__CHOOSE_TYPE_ICON') ?>
                            </label>
                        </div>
                    </div>

                    <div id="type-is-img" class="d-none">
                        <div class="form-group mx-5">
                            <label for="social-img"><?= __('SOCIAL__BUTTON_IMG') ?></label>
                            <em> <?= __('SOCIAL__BUTTON_IMG_SIZE') ?></em>
                            <input
                                type="text"
                                name="img"
                                id="social-img"
                                class="form-control global-reset-input"
                                placeholder="https://images.google.com"
                            >
                        </div>
                        <div class="text-right mx-5">
                            <button type="button" class="btn btn-default type-cancel">
                                <?= __('SOCIAL__CHOOSE_TYPE_CANCEL') ?>
                            </button>
                        </div>
                    </div>

                    <div id="type-is-icon" class="d-none">
                        <div class="form-group mx-5">
                            <label for="social-icon"><?= __('SOCIAL__BUTTON_ICON') ?></label>
                            <p>
                                <?= __('SOCIAL__ICON_DESC') ?>
                                <a target="_blank" href="https://fontawesome.com/">https://fontawesome.com/</a>
                            </p>
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">FA</span>
                                </div>
                                <input
                                    type="text"
                                    name="icon"
                                    id="social-icon"
                                    class="form-control global-reset-input"
                                    placeholder="fab fa-teamspeak"
                                >
                            </div>
                        </div>
                        <div class="text-right mx-5">
                            <button type="button" class="btn btn-default type-cancel">
                                <?= __('SOCIAL__CHOOSE_TYPE_CANCEL') ?>
                            </button>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="social-url"><?= __('SOCIAL__BUTTON_URL') ?></label>
                        <input
                            type="text"
                            name="url"
                            id="social-url"
                            class="form-control"
                        >
                    </div>

                    <div class="form-group">
                        <label for="social-color"><?= __('SOCIAL__BUTTON_COLOR') ?></label>
                        <input
                            type="color"
                            name="color"
                            id="social-color"
                            class="form-control global-reset-input"
                        >
                    </div>

                    <div class="float-right">
                        <a
                            href="<?= $this->Url->build(['_name' => 'admin_social_index']) ?>"
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
    document.addEventListener('DOMContentLoaded', function() {
        let defaultValue = "<?= strtolower($social_default[0]['title']) ?>";
        let selectDefault = document.getElementById('select-social-default');
        let titleInput = document.getElementById('social-title');
        let colorInput = document.getElementById('social-color');
        let typeImg = document.getElementById('type-is-img');
        let typeIcon = document.getElementById('type-is-icon');

        function hideTypes() {
            typeImg.classList.add('d-none');
            typeIcon.classList.add('d-none');
        }

        function resetGlobal() {
            document.querySelectorAll('.global-reset-input').forEach(function(input) {
                input.value = '';
            });
            document.querySelectorAll('input[name="type"]').forEach(function(r) {
                r.checked = false;
            });
            hideTypes();
        }

        function dispatchData(title, extra, color) {
            titleInput.value = title;
            colorInput.value = color;

            if (extra.length > 0) {
                if (extra.indexOf('fa-') !== -1) {
                    let iconRadio = document.getElementById('choose-is-icon');
                    iconRadio.checked = true;
                    typeIcon.classList.remove('d-none');
                    typeImg.classList.add('d-none');
                    let iconInput = document.getElementById('social-icon');
                    if (iconInput) {
                        iconInput.value = extra;
                    }
                } else {
                    let imgRadio = document.getElementById('choose-is-img');
                    imgRadio.checked = true;
                    typeImg.classList.remove('d-none');
                    typeIcon.classList.add('d-none');
                    let imgInput = document.getElementById('social-img');
                    if (imgInput) {
                        imgInput.value = extra;
                    }
                }
            }
        }

        function getSelectValue(v) {
            switch (v) {
            <?php foreach ($social_default as $value) { ?>
                case '<?= strtolower($value['title']) ?>':
                    dispatchData(
                        '<?= $value['title'] ?>',
                        '<?= $value['extra'] ?>',
                        '<?= $value['color'] ?>'
                    );
                    break;
            <?php } ?>
                default:
                    resetGlobal();
                    break;
            }
        }

        getSelectValue(defaultValue);

        selectDefault.addEventListener('change', function() {
            getSelectValue(this.value);
        });

        document.querySelectorAll('input[name="type"]').forEach(function(r) {
            r.addEventListener('change', function() {
                if (this.value === 'img') {
                    typeImg.classList.remove('d-none');
                    typeIcon.classList.add('d-none');
                }
                if (this.value === 'icon') {
                    typeIcon.classList.remove('d-none');
                    typeImg.classList.add('d-none');
                }
            });
        });

        document.querySelectorAll('.type-cancel').forEach(function(b) {
            b.addEventListener('click', function() {
                resetGlobal();
            });
        });
    });
</script>
