<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="card card-outline card-primary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cogs mr-2"></i><?= __('API__LABEL') ?>
                    </h3>
                </div>

                <?= $this->Form->create(null, ['url' => ['action' => 'index'], 'type' => 'post']) ?>

                <div class="card-body">

                    <div class="row">
                        <div class="col-lg-6">

                            <div class="card card-outline card-info">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-user mr-2"></i><?= __('API__SKIN') ?>
                                    </h3>
                                </div>

                                <div class="card-body">

                                    <div class="form-group mb-4">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span id="lbl_skins" class="font-weight-bold"><?= __('API__SKIN_LABEL') ?></span>
                                                <div class="text-muted small"><?= __('GLOBAL__ENABLED') ?> / <?= __('GLOBAL__DISABLED') ?></div>
                                            </div>

                                            <input type="hidden" name="skins" value="0">
                                            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                                                <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="skins"
                                                    name="skins"
                                                    value="1"
                                                    aria-labelledby="lbl_skins"
                                                    <?= $config['skins'] ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="skins"></label>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="form-group mb-0">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span id="lbl_premium" class="font-weight-bold"><?= __('API__SKIN_PREMIUM_LABEL') ?></span>
                                                <div class="text-muted small"><?= __('API__SKIN_PREMIUM_DESC') ?></div>
                                            </div>

                                            <input type="hidden" name="get_premium_skins" value="0">
                                            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                                                <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="get_premium_skins"
                                                    name="get_premium_skins"
                                                    value="1"
                                                    aria-labelledby="lbl_premium"
                                                    <?= $config['get_premium_skins'] ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="get_premium_skins"></label>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="skins_require" style="<?= !$config['skins'] ? 'display: none;' : '' ?>">

                                <div class="card card-outline card-warning">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-plug mr-2"></i><?= __('API__USE_SKIN_RESTORER') ?>
                                        </h3>
                                    </div>

                                    <div class="card-body">

                                        <div class="form-group mb-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <span id="lbl_skin_restorer" class="font-weight-bold"><?= __('API__USE_SKIN_RESTORER') ?></span>
                                                    <div class="text-muted small"><?= __('API__SKIN_RESTORER_SERVER_DESC') ?></div>
                                                </div>

                                                <input type="hidden" name="use_skin_restorer" value="0">
                                                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                                                    <input
                                                        type="checkbox"
                                                        class="custom-control-input"
                                                        id="use_skin_restorer"
                                                        name="use_skin_restorer"
                                                        value="1"
                                                        aria-labelledby="lbl_skin_restorer"
                                                        <?= $config['use_skin_restorer'] ? 'checked' : '' ?>
                                                    >
                                                    <label class="custom-control-label" for="use_skin_restorer"></label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label for="skin_restorer_server" class="font-weight-bold"><?= __('API__SKIN_RESTORER_SERVER') ?></label>
                                            <select id="skin_restorer_server" class="form-control" name="servers">
                                                <?php foreach ($get_all_servers as $key => $value) { ?>
                                                    <option value="<?= $key ?>"<?= (isset($selected_server) && in_array($key, $selected_server)) ? ' selected' : '' ?>>
                                                        <?= $value ?>
                                                    </option>
                                                <?php } ?>
                                            </select>
                                        </div>

                                    </div>
                                </div>

                                <div class="card card-outline card-light">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-file-image mr-2"></i><?= __('API__FILENAME') ?>
                                        </h3>
                                    </div>

                                    <div class="card-body">

                                        <div class="form-group mb-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <span id="lbl_skin_free" class="font-weight-bold"><?= __('API__SKIN_FREE') ?></span>
                                                    <div class="text-muted small"><?= __('GLOBAL__ENABLED') ?> / <?= __('GLOBAL__DISABLED') ?></div>
                                                </div>

                                                <input type="hidden" name="skin_free" value="0">
                                                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                                                    <input
                                                        type="checkbox"
                                                        class="custom-control-input"
                                                        id="skin_free"
                                                        name="skin_free"
                                                        value="1"
                                                        aria-labelledby="lbl_skin_free"
                                                        <?= $config['skin_free'] ? 'checked' : '' ?>
                                                    >
                                                    <label class="custom-control-label" for="skin_free"></label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="skin_filename" class="font-weight-bold"><?= __('API__FILENAME') ?></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><?= $this->Url->build('/', ['fullBase' => true]) ?></span>
                                                </div>
                                                <input
                                                    id="skin_filename"
                                                    type="text"
                                                    class="form-control"
                                                    name="skin_filename"
                                                    value="<?= $config['skin_filename'] ?>"
                                                    placeholder="<?= __('GLOBAL__DEFAULT') ?> : skins/{PLAYER}"
                                                >
                                                <div class="input-group-append">
                                                    <span class="input-group-text">.png</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold" for="skin_width"><?= __('API__FILE_SIZE') ?></label>

                                            <div class="row">
                                                <div class="col-6 col-md-4">
                                                    <input
                                                        id="skin_width"
                                                        type="text"
                                                        class="form-control"
                                                        name="skin_width"
                                                        value="<?= $config['skin_width'] ?>"
                                                        placeholder="<?= __('WIDTH') ?>"
                                                        aria-label="<?= __('WIDTH') ?>"
                                                    >
                                                </div>
                                                <div class="col-auto d-flex align-items-center px-0">
                                                    <span class="text-muted">×</span>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <input
                                                        id="skin_height"
                                                        type="text"
                                                        class="form-control"
                                                        name="skin_height"
                                                        value="<?= $config['skin_height'] ?>"
                                                        placeholder="<?= __('HEIGHT') ?>"
                                                        aria-label="<?= __('HEIGHT') ?>"
                                                    >
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        </div>

                        <div class="col-lg-6">

                            <div class="card card-outline card-secondary">
                                <div class="card-header">
                                    <h3 class="card-title">
                                        <i class="fas fa-mask mr-2"></i><?= __('API__CAPE') ?>
                                    </h3>
                                </div>

                                <div class="card-body">

                                    <div class="form-group mb-0">
                                        <div class="d-flex align-items-center justify-content-between">
                                            <div>
                                                <span id="lbl_capes" class="font-weight-bold"><?= __('API__CAPE_LABEL') ?></span>
                                                <div class="text-muted small"><?= __('GLOBAL__ENABLED') ?> / <?= __('GLOBAL__DISABLED') ?></div>
                                            </div>

                                            <input type="hidden" name="capes" value="0">
                                            <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                                                <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="capes"
                                                    name="capes"
                                                    value="1"
                                                    aria-labelledby="lbl_capes"
                                                    <?= $config['capes'] ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="capes"></label>
                                            </div>
                                        </div>
                                    </div>

                                </div>
                            </div>

                            <div class="capes_require" style="<?= !$config['capes'] ? 'display: none;' : '' ?>">

                                <div class="card card-outline card-light">
                                    <div class="card-header">
                                        <h3 class="card-title">
                                            <i class="fas fa-unlock mr-2"></i><?= __('API__CAPE_FREE') ?>
                                        </h3>
                                    </div>

                                    <div class="card-body">

                                        <div class="form-group mb-4">
                                            <div class="d-flex align-items-center justify-content-between">
                                                <div>
                                                    <span id="lbl_cape_free" class="font-weight-bold"><?= __('API__CAPE_FREE') ?></span>
                                                    <div class="text-muted small"><?= __('GLOBAL__ENABLED') ?> / <?= __('GLOBAL__DISABLED') ?></div>
                                                </div>

                                                <input type="hidden" name="cape_free" value="0">
                                                <div class="custom-control custom-switch custom-switch-off-danger custom-switch-on-success">
                                                    <input
                                                        type="checkbox"
                                                        class="custom-control-input"
                                                        id="cape_free"
                                                        name="cape_free"
                                                        value="1"
                                                        aria-labelledby="lbl_cape_free"
                                                        <?= $config['cape_free'] ? 'checked' : '' ?>
                                                    >
                                                    <label class="custom-control-label" for="cape_free"></label>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group">
                                            <label for="cape_filename" class="font-weight-bold"><?= __('API__FILENAME') ?></label>
                                            <div class="input-group">
                                                <div class="input-group-prepend">
                                                    <span class="input-group-text"><?= $this->Url->build('/', ['fullBase' => true]) ?></span>
                                                </div>
                                                <input
                                                    id="cape_filename"
                                                    type="text"
                                                    class="form-control"
                                                    name="cape_filename"
                                                    value="<?= $config['cape_filename'] ?>"
                                                    placeholder="<?= __('GLOBAL__DEFAULT') ?> : capes/{PLAYER}"
                                                >
                                                <div class="input-group-append">
                                                    <span class="input-group-text">.png</span>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="form-group mb-0">
                                            <label class="font-weight-bold" for="cape_width"><?= __('API__FILE_SIZE') ?></label>

                                            <div class="row">
                                                <div class="col-6 col-md-4">
                                                    <input
                                                        id="cape_width"
                                                        type="text"
                                                        class="form-control"
                                                        name="cape_width"
                                                        value="<?= $config['cape_width'] ?>"
                                                        placeholder="<?= __('WIDTH') ?>"
                                                        aria-label="<?= __('WIDTH') ?>"
                                                    >
                                                </div>
                                                <div class="col-auto d-flex align-items-center px-0">
                                                    <span class="text-muted">×</span>
                                                </div>
                                                <div class="col-6 col-md-4">
                                                    <input
                                                        id="cape_height"
                                                        type="text"
                                                        class="form-control"
                                                        name="cape_height"
                                                        value="<?= $config['cape_height'] ?>"
                                                        placeholder="<?= __('HEIGHT') ?>"
                                                        aria-label="<?= __('HEIGHT') ?>"
                                                    >
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>

                            </div>

                        </div>
                    </div>

                </div>

                <div class="card-footer text-right">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-save mr-1"></i><?= __('GLOBAL__SUBMIT') ?>
                    </button>
                </div>

                <?= $this->Form->end() ?>

            </div>

        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        function toggleCheckbox(id, selector) {
            const checkbox = document.getElementById(id);
            const blocks = document.querySelectorAll(selector);

            function apply() {
                blocks.forEach(function (el) {
                    el.style.display = checkbox && checkbox.checked ? '' : 'none';
                });
            }

            apply();
            if (checkbox) {
                checkbox.addEventListener('change', apply);
            }
        }

        toggleCheckbox('skins', '.skins_require');
        toggleCheckbox('capes', '.capes_require');
    });
</script>
