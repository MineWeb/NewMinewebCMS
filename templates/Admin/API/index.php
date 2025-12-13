<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('API__LABEL') ?></h3>
                </div>
                <div class="card-body">

                    <?= $this->Form->create(null, ['url' => ['action' => 'index'], 'type' => 'post']) ?>

                    <h3><?= __('API__SKIN') ?></h3>
                    <br>

                    <div class="ml-5">

                        <div class="form-group">
                            <label><?= __('API__SKIN_LABEL') ?></label>

                            <?php $id = 'skins_on'; ?>
                            <div class="radio">
                                <input id="<?= $id ?>" type="radio" name="skins" value="1" <?= $config['skins'] ? 'checked="checked"' : '' ?>>
                                <label for="<?= $id ?>"><?= __('GLOBAL__ENABLED') ?></label>
                            </div>

                            <?php $id = 'skins_off'; ?>
                            <div class="radio">
                                <input id="<?= $id ?>" type="radio" name="skins" value="0" <?= !$config['skins'] ? 'checked="checked"' : '' ?>>
                                <label for="<?= $id ?>"><?= __('GLOBAL__DISABLED') ?></label>
                            </div>
                        </div>

                        <hr>

                        <div class="form-group">
                            <label><?= __('API__SKIN_PREMIUM_LABEL') ?></label>
                            <em><?= __('API__SKIN_PREMIUM_DESC') ?></em>

                            <?php $id = 'premium_on'; ?>
                            <div class="radio">
                                <input id="<?= $id ?>" type="radio" name="get_premium_skins" value="1" <?= $config['get_premium_skins'] ? 'checked="checked"' : '' ?>>
                                <label for="<?= $id ?>"><?= __('GLOBAL__ENABLED') ?></label>
                            </div>

                            <?php $id = 'premium_off'; ?>
                            <div class="radio">
                                <input id="<?= $id ?>" type="radio" name="get_premium_skins" value="0" <?= !$config['get_premium_skins'] ? 'checked="checked"' : '' ?>>
                                <label for="<?= $id ?>"><?= __('GLOBAL__DISABLED') ?></label>
                            </div>
                        </div>

                        <div class="skins_require" style="<?= !$config['skins'] ? 'display: none;' : '' ?>">

                            <div class="form-group">
                                <hr>
                                <label><?= __('API__USE_SKIN_RESTORER') ?></label>

                                <?php $id = 'skin_restorer_on'; ?>
                                <div class="radio">
                                    <input id="<?= $id ?>" type="radio" name="use_skin_restorer" value="1" <?= $config['use_skin_restorer'] ? 'checked="checked"' : '' ?>>
                                    <label for="<?= $id ?>"><?= __('GLOBAL__ENABLED') ?></label>
                                </div>

                                <?php $id = 'skin_restorer_off'; ?>
                                <div class="radio">
                                    <input id="<?= $id ?>" type="radio" name="use_skin_restorer" value="0" <?= !$config['use_skin_restorer'] ? 'checked="checked"' : '' ?>>
                                    <label for="<?= $id ?>"><?= __('GLOBAL__DISABLED') ?></label>
                                </div>
                            </div>

                            <div class="form-group">
                                <label for="skin_restorer_server"><?= __('API__SKIN_RESTORER_SERVER') ?></label>
                                <em><?= __('API__SKIN_RESTORER_SERVER_DESC') ?></em>
                                <select id="skin_restorer_server" class="form-control" name="servers">
                                    <?php foreach ($get_all_servers as $key => $value) { ?>
                                        <option value="<?= $key ?>"<?= (isset($selected_server) && in_array($key, $selected_server)) ? ' selected' : '' ?>><?= $value ?></option>
                                    <?php } ?>
                                </select>
                                <hr>
                            </div>

                            <div class="form-group">
                                <label><?= __('API__SKIN_FREE') ?></label>

                                <?php $id = 'skin_free_on'; ?>
                                <div class="radio">
                                    <input id="<?= $id ?>" type="radio" name="skin_free" value="1" <?= $config['skin_free'] ? 'checked="checked"' : '' ?>>
                                    <label for="<?= $id ?>"><?= __('GLOBAL__ENABLED') ?></label>
                                </div>

                                <?php $id = 'skin_free_off'; ?>
                                <div class="radio">
                                    <input id="<?= $id ?>" type="radio" name="skin_free" value="0" <?= !$config['skin_free'] ? 'checked="checked"' : '' ?>>
                                    <label for="<?= $id ?>"><?= __('GLOBAL__DISABLED') ?></label>
                                </div>
                                <hr>
                            </div>

                            <div class="form-group">
                                <label for="skin_filename"><?= __('API__FILENAME') ?></label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><?= $this->Url->build('/', ['fullBase' => true]) ?></span>
                                    </div>
                                    <input id="skin_filename" type="text" class="form-control" name="skin_filename" value="<?= $config['skin_filename'] ?>" placeholder="<?= __('GLOBAL__DEFAULT') ?> : skins/{PLAYER}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">.png</span>
                                    </div>
                                </div>
                                <hr>
                            </div>

                            <div class="form-group">
                                <label><?= __('API__FILE_SIZE') ?></label>
                                <div class="input-group mb-3">
                                    <input id="skin_width" type="text" class="form-control" name="skin_width" value="<?= $config['skin_width'] ?>" placeholder="<?= __('WIDTH') ?>">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text">X</span>
                                    </div>
                                    <input id="skin_height" type="text" class="form-control" name="skin_height" value="<?= $config['skin_height'] ?>" placeholder="<?= __('HEIGHT') ?>">
                                </div>
                            </div>

                        </div>

                    </div>

                    <hr>

                    <h3><?= __('API__CAPE') ?></h3>
                    <br>

                    <div class="ml-5">

                        <div class="form-group">
                            <label><?= __('API__CAPE_LABEL') ?></label>

                            <?php $id = 'capes_on'; ?>
                            <div class="radio">
                                <input id="<?= $id ?>" type="radio" name="capes" value="1" <?= $config['capes'] ? 'checked="checked"' : '' ?>>
                                <label for="<?= $id ?>"><?= __('GLOBAL__ENABLED') ?></label>
                            </div>

                            <?php $id = 'capes_off'; ?>
                            <div class="radio">
                                <input id="<?= $id ?>" type="radio" name="capes" value="0" <?= !$config['capes'] ? 'checked="checked"' : '' ?>>
                                <label for="<?= $id ?>"><?= __('GLOBAL__DISABLED') ?></label>
                            </div>
                        </div>

                        <div class="capes_require" style="<?= !$config['capes'] ? 'display: none;' : '' ?>">

                            <div class="form-group">
                                <hr>
                                <label><?= __('API__CAPE_FREE') ?></label>

                                <?php $id = 'cape_free_on'; ?>
                                <div class="radio">
                                    <input id="<?= $id ?>" type="radio" name="cape_free" value="1" <?= $config['cape_free'] ? 'checked="checked"' : '' ?>>
                                    <label for="<?= $id ?>"><?= __('GLOBAL__ENABLED') ?></label>
                                </div>

                                <?php $id = 'cape_free_off'; ?>
                                <div class="radio">
                                    <input id="<?= $id ?>" type="radio" name="cape_free" value="0" <?= !$config['cape_free'] ? 'checked="checked"' : '' ?>>
                                    <label for="<?= $id ?>"><?= __('GLOBAL__DISABLED') ?></label>
                                </div>
                                <hr>
                            </div>

                            <div class="form-group">
                                <label for="cape_filename"><?= __('API__FILENAME') ?></label>
                                <div class="input-group mb-3">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text"><?= $this->Url->build('/', ['fullBase' => true]) ?></span>
                                    </div>
                                    <input id="cape_filename" type="text" class="form-control" name="cape_filename" value="<?= $config['cape_filename'] ?>" placeholder="<?= __('GLOBAL__DEFAULT') ?> : capes/{PLAYER}">
                                    <div class="input-group-append">
                                        <span class="input-group-text">.png</span>
                                    </div>
                                </div>
                                <hr>
                            </div>

                            <div class="input-group mb-3">
                                <input id="cape_width" type="text" class="form-control" name="cape_width" value="<?= $config['cape_width'] ?>" placeholder="<?= __('WIDTH') ?>">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">X</span>
                                </div>
                                <input id="cape_height" type="text" class="form-control" name="cape_height" value="<?= $config['cape_height'] ?>" placeholder="<?= __('HEIGHT') ?>">
                            </div>

                        </div>
                    </div>

                    <hr>

                    <script>
                        document.addEventListener('DOMContentLoaded', function () {
                            function toggleGroup(name, selector) {
                                const radios = document.querySelectorAll('input[type=radio][name="' + name + '"]');
                                const blocks = document.querySelectorAll(selector);

                                function apply(value) {
                                    blocks.forEach(function (el) {
                                        el.style.display = value === '0' ? 'none' : '';
                                    });
                                }

                                radios.forEach(function (radio) {
                                    if (radio.checked) {
                                        apply(radio.value);
                                    }
                                    radio.addEventListener('change', function () {
                                        apply(this.value);
                                    });
                                });
                            }

                            toggleGroup('skins', '.skins_require');
                            toggleGroup('capes', '.capes_require');
                        });
                    </script>

                    <div class="float-right">
                        <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                    </div>

                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>
    </div>
</section>
