<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap:10px;">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-plus-circle mr-2"></i><?= __('MAINTENANCE__ADD_PAGE') ?>
                            </h3>

                            <a href="<?= $this->Url->build(['_name' => 'admin_maintenance_index']) ?>" class="btn btn-default btn-sm mt-2 mt-sm-0">
                                <i class="fas fa-arrow-left mr-2"></i><?= __('GLOBAL__BACK') ?>
                            </a>
                        </div>
                    </div>

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_maintenance_add'],
                        'id' => 'maintenance-form',
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_maintenance_index']),
                    ]) ?>

                    <div class="card-body">

                        <div class="ajax-msg"></div>

                        <div class="form-group">
                            <label for="maintenance-url"><?= __('MAINTENANCE__PAGE') ?></label>
                            <div class="text-muted text-sm mb-2">
                                <div><?= __('MAINTENANCE__ADD_EXAMPLE') ?></div>
                                <div><?= __('MAINTENANCE__ADD_EMPTY_URL') ?></div>
                            </div>
                            <input
                                type="text"
                                id="maintenance-url"
                                name="url"
                                class="form-control"
                                autocomplete="off"
                            >
                        </div>

                        <div class="form-group">
                            <label for="maintenance-reason"><?= __('MAINTENANCE__REASON') ?></label>

                            <?= $this->Html->script('admin/tinymce/tinymce.min.js') ?>
                            <script>
                                document.addEventListener('DOMContentLoaded', function () {
                                    if (typeof tinymce === 'undefined') return;
                                    tinymce.init({
                                        selector: '#maintenance-reason',
                                        license_key: 'gpl',
                                        promotion: false,
                                        branding: false,
                                        height: 300,
                                        width: '100%',
                                        language: "<?= $currentLocale ?>",
                                        plugins: 'code image link',
                                        toolbar: 'fontselect fontsizeselect bold italic underline strikethrough link image forecolor backcolor alignleft aligncenter alignright alignjustify cut copy paste bullist numlist outdent indent blockquote code'
                                    });
                                });
                            </script>

                            <textarea
                                id="maintenance-reason"
                                name="reason"
                                rows="10"
                                class="form-control"
                            ></textarea>
                        </div>

                        <input type="hidden" name="sub_url" value="0">

                        <div class="custom-control custom-switch">
                            <input
                                id="maintenance-sub-url"
                                name="sub_url_checkbox"
                                type="checkbox"
                                class="custom-control-input"
                            >
                            <label class="custom-control-label" for="maintenance-sub-url">
                                <?= __('MAINTENANCE__USE_SUB_URL') ?>
                            </label>
                        </div>

                    </div>

                    <div class="card-footer bg-white">
                        <div class="d-flex justify-content-end">
                            <a href="<?= $this->Url->build(['_name' => 'admin_maintenance_index']) ?>"
                               class="btn btn-default mr-2">
                                <?= __('GLOBAL__CANCEL') ?>
                            </a>
                            <button class="btn btn-primary" type="submit">
                                <?= __('GLOBAL__SUBMIT') ?>
                            </button>
                        </div>
                    </div>

                    <?= $this->Form->end() ?>

                </div>

            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const checkbox = document.querySelector('input[name="sub_url_checkbox"]');
        const hiddenInput = document.querySelector('input[name="sub_url"]');
        if (!checkbox || !hiddenInput) return;

        const sync = () => hiddenInput.value = checkbox.checked ? '1' : '0';
        checkbox.addEventListener('change', sync);
        sync();
    });
</script>
