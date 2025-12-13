<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('THEME__CUSTOMIZATION') ?></h3>
                </div>

                <div class="card-body">

                    <?php
                    $themeKey = $this->getRequest()->getParam('pass.0');
                    echo $this->Form->create(null, [
                        'data-ajax' => 'false',
                        'url' => [
                            '_name' => 'admin_theme_custom',
                            $themeKey
                        ]
                    ]);
                    ?>

                    <div class="form-group">
                        <div class="checkbox">
                            <input
                                type="checkbox"
                                name="slider"
                                id="slider"
                                <?= isset($config['slider']) && $config['slider'] === 'true' ? 'checked' : '' ?>
                            >
                            <label for="slider"><?= __('SLIDER__TITLE') ?></label>
                        </div>
                    </div>

                    <script>
                        const slider = document.getElementById('slider');

                        function syncSliderValue() {
                            slider.value = slider.checked ? 'true' : 'false';
                        }

                        if (slider) {
                            slider.addEventListener('change', syncSliderValue);
                            syncSliderValue();
                        }
                    </script>

                    <div class="form-group">
                        <label for="favicon_url"><?= __('THEME__FAVICON_URL') ?></label>
                        <input
                            type="text"
                            class="form-control"
                            id="favicon_url"
                            name="favicon_url"
                            value="<?= isset($config['favicon_url']) ? h($config['favicon_url']) : '' ?>"
                        >
                    </div>

                    <button class="btn btn-primary" type="submit">
                        <?= __('GLOBAL__SUBMIT') ?>
                    </button>

                    <a
                        href="<?= $this->Url->build(['_name' => 'admin_theme_index']) ?>"
                        class="btn btn-default"
                    >
                        <?= __('GLOBAL__CANCEL') ?>
                    </a>

                    <?= $this->Form->end() ?>

                </div>
            </div>

        </div>
    </div>
</section>
