<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('MAINTENANCE__TITLE') ?></h3>
                </div>
                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'method' => 'post',
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_maintenance_index'])
                    ]) ?>

                    <div class="form-group">
                        <label for="url"><?= __("MAINTENANCE__PAGE") ?></label><br>
                        <i><?= __("MAINTENANCE__ADD_EXAMPLE") ?></i><br>
                        <i><?= __("MAINTENANCE__ADD_EMPTY_URL") ?></i>

                        <input
                            type="text"
                            id="url"
                            name="url"
                            class="form-control"
                            value="<?= $page["url"] ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="editor"><?= __('MAINTENANCE__REASON') ?></label>

                        <?= $this->Html->script('admin/tinymce/tinymce.min.js') ?>
                        <script type="text/javascript">
                            document.addEventListener('DOMContentLoaded', function () {
                                tinymce.init({
                                    selector: "textarea",
                                    license_key: 'gpl',
                                    promotion: false,
                                    branding: false,
                                    height: 300,
                                    width: '100%',
                                    language: '<?= $currentLocale ?>',
                                    plugins: "code image link",
                                    toolbar: "fontselect fontsizeselect bold italic underline strikethrough link image forecolor backcolor alignleft aligncenter alignright alignjustify cut copy paste bullist numlist outdent indent blockquote code"
                                });
                            });
                        </script>

                        <textarea
                            id="editor"
                            name="reason"
                            cols="30"
                            rows="10"
                        ><?= $page["reason"] ?></textarea>
                    </div>

                    <div class="form-group">
                        <input type="hidden" id="sub_url" name="sub_url" value="<?= $page['sub_url'] ?>">

                        <div class="checkbox">
                            <input
                                id="sub_url_checkbox"
                                name="sub_url_checkbox"
                                type="checkbox"
                                <?= $page['sub_url'] == 1 ? 'checked' : '' ?>
                            >
                            <label for="sub_url_checkbox"><?= __('MAINTENANCE__USE_SUB_URL') ?></label>
                        </div>
                    </div>

                    <script type="text/javascript">
                        document.addEventListener('DOMContentLoaded', function () {
                            let checkbox = document.getElementById('sub_url_checkbox');
                            let hiddenInput = document.getElementById('sub_url');

                            checkbox.addEventListener('change', function () {
                                hiddenInput.value = checkbox.checked ? '1' : '0';
                            });
                        });
                    </script>

                    <div class="float-right">
                        <a href="<?= $this->Url->build(['_name' => 'admin_maintenance_index']) ?>"
                           class="btn btn-default">
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
