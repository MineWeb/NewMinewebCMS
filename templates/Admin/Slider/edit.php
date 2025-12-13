<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('SLIDER__EDIT') ?></h3>
                </div>

                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_slider_edit_ajax'],
                        'type' => 'post',
                        'data-ajax' => 'true',
                        'data-upload-image' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_slider_index'])
                    ]) ?>

                    <input type="hidden" name="id" value="<?= (int)$slider['id'] ?>">

                    <div class="col-md-4">
                        <?= $this->element('form.input.upload.img', [
                            'img' => $slider['url_img'] ?? '',
                            'filename' => $slider['filename'] ?? ''
                        ]) ?>
                    </div>

                    <div class="col-md-12">

                        <div class="form-group">
                            <label for="slider-title"><?= __('GLOBAL__TITLE') ?></label>
                            <input
                                id="slider-title"
                                name="title"
                                class="form-control"
                                type="text"
                                value="<?= h($slider['title'] ?? '') ?>"
                            >
                        </div>

                        <div class="form-group">
                            <label for="slider-subtitle"><?= __('SLIDER__SUBTITLE') ?></label>
                            <input
                                id="slider-subtitle"
                                name="subtitle"
                                class="form-control"
                                type="text"
                                value="<?= h($slider['subtitle'] ?? '') ?>"
                            >
                        </div>

                    </div>

                    <div class="float-right">
                        <a
                            href="<?= $this->Url->build(['_name' => 'admin_slider_index']) ?>"
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
