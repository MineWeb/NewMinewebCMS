<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('MAINTENANCE__TITLE') ?></h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_maintenance_add'],
                        'id' => 'maintenance-form',
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_maintenance_index']),
                    ]) ?>
                        <div class="form-group">
                            <label for="maintenance-url"><?= __('MAINTENANCE__PAGE') ?></label><br>
                            <i><?= __('MAINTENANCE__ADD_EXAMPLE') ?></i><br>
                            <i><?= __('MAINTENANCE__ADD_EMPTY_URL') ?></i>
                            <input
                                type="text"
                                id="maintenance-url"
                                name="url"
                                class="form-control">
                        </div>

                        <div class="form-group">
                            <label for="maintenance-reason"><?= __('MAINTENANCE__REASON') ?></label>
                            <?= $this->Html->script('admin/tinymce/tinymce.min.js') ?>
                            <script type="text/javascript">
                                document.addEventListener("DOMContentLoaded", function () {
                                    if (typeof tinymce === "undefined") {
                                        return;
                                    }
                                    tinymce.init({
                                        selector: "#maintenance-reason",
                                        height: 300,
                                        width: "100%",
                                        language: "fr_FR",
                                        plugins: "textcolor code image link",
                                        toolbar: "fontselect fontsizeselect bold italic underline strikethrough link image forecolor backcolor alignleft aligncenter alignright alignjustify cut copy paste bullist numlist outdent indent blockquote code"
                                    });
                                });
                            </script>
                            <textarea
                                id="maintenance-reason"
                                name="reason"
                                cols="30"
                                rows="10"
                            ></textarea>
                        </div>

                        <div class="form-group">
                            <input type="hidden" name="sub_url" value="0">
                            <div class="checkbox">
                                <input
                                    id="maintenance-sub-url"
                                    name="sub_url_checkbox"
                                    type="checkbox"
                                >
                                <label for="maintenance-sub-url"><?= __('MAINTENANCE__USE_SUB_URL') ?></label>
                            </div>
                        </div>

                        <script type="text/javascript">
                            document.addEventListener("DOMContentLoaded", function () {
                                let checkbox = document.querySelector('input[name="sub_url_checkbox"]');
                                let hiddenInput = document.querySelector('input[name="sub_url"]');

                                if (!checkbox || !hiddenInput) {
                                    return;
                                }

                                checkbox.addEventListener("change", function () {
                                    hiddenInput.value = checkbox.checked ? "1" : "0";
                                });
                            });
                        </script>

                        <div class="float-right">
                            <a
                                href="<?= $this->Url->build(['_name' => 'admin_maintenance_index']) ?>"
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
