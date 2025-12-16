<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-edit mr-2"></i><?= __('SLIDER__EDIT') ?>
                            </h3>

                            <a href="<?= $this->Url->build(['_name' => 'admin_slider_index']) ?>"
                               class="btn btn-default btn-sm">
                                <i class="fas fa-arrow-left mr-2"></i><?= __('GLOBAL__BACK') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <?= $this->Form->create(null, [
                            'url' => ['_name' => 'admin_slider_edit_ajax'],
                            'type' => 'post',
                            'data-ajax' => 'true',
                            'data-upload-image' => 'true',
                            'data-redirect-url' => $this->Url->build(['_name' => 'admin_slider_index']),
                        ]) ?>

                        <div class="p-3 p-md-4">

                            <div class="ajax-msg"></div>

                            <input type="hidden" name="id" value="<?= (int)($slider['id'] ?? 0) ?>">

                            <div class="row">
                                <div class="col-lg-6">

                                    <?= $this->element('form.input.upload.img', [
                                        'uid' => 'slider-edit-img-' . ($slider['id'] ?? '0'),
                                        'field' => 'img',
                                        'img' => $slider['url_img'] ?? '',
                                        'filename' => $slider['filename'] ?? '',
                                    ]) ?>

                                </div>

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <label for="slider-title"
                                                       class="mb-1"><?= __('GLOBAL__TITLE') ?></label>
                                                <input
                                                    id="slider-title"
                                                    name="title"
                                                    class="form-control"
                                                    type="text"
                                                    value="<?= h((string)($slider['title'] ?? '')) ?>"
                                                    autocomplete="off"
                                                >
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card mb-0">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <label for="slider-subtitle"
                                                       class="mb-1"><?= __('SLIDER__SUBTITLE') ?></label>
                                                <input
                                                    id="slider-subtitle"
                                                    name="subtitle"
                                                    class="form-control"
                                                    type="text"
                                                    value="<?= h((string)($slider['subtitle'] ?? '')) ?>"
                                                    autocomplete="off"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-end mt-3">
                                        <a href="<?= $this->Url->build(['_name' => 'admin_slider_index']) ?>"
                                           class="btn btn-default mr-2">
                                            <i class="fas fa-times mr-1"></i><?= __('GLOBAL__CANCEL') ?>
                                        </a>
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
