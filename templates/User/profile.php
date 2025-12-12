<div class="container">
    <div class="row">
        <div class="col-md-6">
            <h1><?= __('USER__PROFILE') ?></h1>
        </div>
    </div>
    <div class="panel panel-default">
        <div class="panel-body">

            <?= $Module->loadModules('user_profile_messages') ?>

            <div class="section">
                <p><b><?= __('USER__USERNAME') ?> :</b> <?= h($this->Auth->username()) ?></p>
            </div>
            <div class="section">
                <p><b><?= __('USER__EMAIL') ?> :</b> <span id="email"><?= h($user['email']) ?></span></p>
            </div>
            <div class="section">
                <p>
                    <b><?= __('USER__RANK') ?> :</b>
                    <?php foreach ($available_ranks as $key => $value) {
                        if ($user['rank'] == $key) {
                            echo h($value);
                        }
                    } ?>
                </p>
            </div>

            <?php if ($EyPlugin->isInstalled('eywek.shop')) { ?>
                <div class="section">
                    <p><b><?= __('USER__MONEY') ?> :</b> <span class="money"><?= h($user['money']) ?></span></p>
                </div>
            <?php } ?>

            <div class="section">
                <p><b><?= __('IP') ?> :</b> <?= h($user['ip']) ?></p>
            </div>

            <div class="section">
                <p><b><?= __('GLOBAL__CREATED') ?> :</b> <?= $this->Lang->date($user['created_at']) ?></p>
            </div>

            <div class="callout" id="twoFactorAuthStatus">
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <a
                            id="toggleTwoFactorAuth"
                            data-status="<?= (isset($twoFactorAuthStatus) && $twoFactorAuthStatus) ? '1' : '0' ?>"
                            class="btn btn-info"
                        >
                            Voulez-vous
                            <span id="twoFactorAuthStatusInfos">
                                <?= (isset($twoFactorAuthStatus) && $twoFactorAuthStatus) ? 'désactiver' : 'activer' ?>
                            </span>
                            la double authentification ?
                        </a>
                    </div>
                </div>
            </div>

            <div id="twoFactorAuthValid" class="text-center" style="display: none;">
                <img src="" id="two-factor-auth-qrcode" alt=""/>
                <p>
                    <small class="text-muted">Secret : <em id="two-factor-auth-secret"></em></small>
                </p>

                <?= $this->Form->create(null, [
                    'class' => 'form-horizontal',
                    'method' => 'post',
                    'url' => ['_name' => 'auth_2fa_enable'],
                    'data-ajax' => 'true',
                    'data-callback-function' => 'afterValidQrCode'
                ]) ?>
                <div class="ajax-msg"></div>

                <div class="form-group text-center">
                    <label for="two-factor-code"><?= __('USER__LOGIN_CODE') ?></label>
                    <div class="col-md-6" style="margin: 0 auto;float: none;">
                        <input
                            type="text"
                            id="two-factor-code"
                            class="form-control"
                            name="code"
                            placeholder="<?= __('USER__LOGIN_CODE_PLACEHOLDER') ?>"
                        >
                    </div>
                </div>

                <button type="submit" class="btn btn-info"><?= __('USER__VALID_CODE') ?></button>
                <?= $this->Form->end() ?>
            </div>

            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function () {
                    let toggleBtn = document.getElementById('toggleTwoFactorAuth');
                    if (!toggleBtn) {
                        return;
                    }
                    let statusInfos = document.getElementById('twoFactorAuthStatusInfos');
                    let statusBlock = document.getElementById('twoFactorAuthStatus');
                    let validBlock = document.getElementById('twoFactorAuthValid');
                    let qrcodeImg = document.getElementById('two-factor-auth-qrcode');
                    let secretSpan = document.getElementById('two-factor-auth-secret');

                    toggleBtn.addEventListener('click', function (e) {
                        e.preventDefault();
                        let status = parseInt(toggleBtn.getAttribute('data-status') || '0', 10);
                        toggleBtn.innerHTML = '<i class="fa fa-refresh fa-spin"></i>';
                        toggleBtn.classList.add('disabled');

                        if (!status) {
                            fetch('<?= $this->Url->build(['_name' => 'auth_2fa_generate_secret']) ?>', {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                                .then(function (response) {
                                    return response.json();
                                })
                                .then(function (data) {
                                    if (qrcodeImg && data.qrcode_url) {
                                        qrcodeImg.setAttribute('src', data.qrcode_url);
                                    }
                                    if (secretSpan && data.secret) {
                                        secretSpan.textContent = data.secret;
                                    }
                                    if (statusBlock) {
                                        statusBlock.style.display = 'none';
                                    }
                                    if (validBlock) {
                                        validBlock.style.display = 'block';
                                    }
                                })
                                .finally(function () {
                                    toggleBtn.classList.remove('disabled');
                                });
                        } else {
                            fetch('<?= $this->Url->build(['_name' => 'auth_2fa_disable']) ?>', {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                                .then(function () {
                                    toggleBtn.innerHTML = 'Voulez-vous activer la double authentification ?';
                                    toggleBtn.classList.remove('disabled');
                                    toggleBtn.classList.remove('btn-primary');
                                    toggleBtn.classList.add('btn-primary');
                                    toggleBtn.setAttribute('data-status', '0');
                                    if (statusInfos) {
                                        statusInfos.textContent = 'activer';
                                    }
                                });
                        }
                    });
                });

                function afterValidQrCode(req, res) {
                    let toggleBtn = document.getElementById('toggleTwoFactorAuth');
                    let statusInfos = document.getElementById('twoFactorAuthStatusInfos');
                    let statusBlock = document.getElementById('twoFactorAuthStatus');
                    let validBlock = document.getElementById('twoFactorAuthValid');

                    if (toggleBtn) {
                        toggleBtn.innerHTML = 'Voulez-vous désactiver la double authentification ?';
                        toggleBtn.classList.remove('disabled');
                        toggleBtn.classList.remove('btn-primary');
                        toggleBtn.classList.add('btn-primary');
                        toggleBtn.setAttribute('data-status', '1');
                    }
                    if (statusInfos) {
                        statusInfos.textContent = 'désactiver';
                    }
                    if (validBlock) {
                        validBlock.style.display = 'none';
                    }
                    if (statusBlock) {
                        statusBlock.style.display = 'block';
                    }
                }
            </script>

            <hr>

            <h3><?= __('USER__UPDATE_PASSWORD') ?></h3>

            <?= $this->Form->create(null, [
                'method' => 'post',
                'class' => 'form-inline',
                'url' => ['_name' => 'user_change_pw'],
                'data-ajax' => 'true'
            ]) ?>
            <div class="form-group">
                <label for="password-new"><?= __('USER__PASSWORD') ?></label>
                <input
                    type="password"
                    id="password-new"
                    class="form-control"
                    name="password"
                    placeholder="<?= __('USER__PASSWORD') ?>"
                >
            </div>
            <div class="form-group">
                <label for="password-confirm"><?= __('USER__PASSWORD_CONFIRM') ?></label>
                <input
                    type="password"
                    id="password-confirm"
                    class="form-control"
                    name="password_confirmation"
                    placeholder="<?= __('USER__PASSWORD_CONFIRM') ?>"
                >
            </div>

            <div class="form-group">
                <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
            </div>
            <?= $this->Form->end() ?>

            <?php if ($this->Auth->can('EDIT_HIS_EMAIL')) { ?>
                <hr>

                <h3><?= __('USER__UPDATE_EMAIL') ?></h3>

                <?= $this->Form->create(null, [
                'method' => 'post',
                'class' => 'form-inline',
                'url' => ['_name' => 'user_change_email'],
                'data-ajax' => 'true'
            ]) ?>
                <div class="form-group">
                    <label for="email-new"><?= __('USER__EMAIL') ?></label>
                    <input
                        type="email"
                        id="email-new"
                        class="form-control"
                        name="email"
                        placeholder="<?= __('USER__EMAIL') ?>"
                    >
                </div>
                <div class="form-group">
                    <label for="email-confirm"><?= __('USER__EMAIL_CONFIRM_LABEL') ?></label>
                    <input
                        type="email"
                        id="email-confirm"
                        class="form-control"
                        name="email_confirmation"
                        placeholder="<?= __('USER__EMAIL_CONFIRM_LABEL') ?>"
                    >
                </div>

                <div class="form-group">
                    <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                </div>
                <?= $this->Form->end() ?>
            <?php } ?>

            <?php if ($shop_active) { ?>

                <hr>

                <h3><?= __('SHOP__USER_POINTS_TRANSFER') ?></h3>

                <?= $this->Form->create(null, [
                'method' => 'post',
                'class' => 'form-inline',
                'url' => ['_name' => 'shop_transfer_points'],
                'data-ajax' => 'true'
            ]) ?>
                <div class="form-group">
                    <label for="transfer-to"><?= __('SHOP__USER_POINTS_TRANSFER_WHO') ?></label>
                    <input
                        type="text"
                        id="transfer-to"
                        class="form-control"
                        name="to"
                        placeholder="<?= __('SHOP__USER_POINTS_TRANSFER_WHO') ?>"
                    >
                </div>
                <div class="form-group">
                    <label for="transfer-how"><?= __('SHOP__USER_POINTS_TRANSFER_HOW_MANY') ?></label>
                    <input
                        type="text"
                        id="transfer-how"
                        class="form-control"
                        name="how"
                        placeholder="<?= __('SHOP__USER_POINTS_TRANSFER_HOW_MANY') ?>"
                    >
                </div>

                <div class="form-group">
                    <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                </div>
                <?= $this->Form->end() ?>

            <?php } ?>

            <?php if ($can_skin) { ?>
                <hr>

                <h3><?= __('API__SKIN_LABEL') ?></h3>

                <?= $this->Form->create(null, [
                'class' => 'form-inline',
                'id' => 'skin',
                'method' => 'post',
                'url' => ['_name' => 'user_upload_skin'],
                'data-ajax' => 'true',
                'data-upload-image' => 'true',
                'enctype' => 'multipart/form-data'
            ]) ?>
                <div class="form-group">
                    <label for="skin-image"><?= __('FORM__BROWSE') ?></label>
                    <input
                        id="skin-image"
                        name="image"
                        type="file"
                        class="form-control"
                    >
                </div>
                <button type="submit" class="btn btn-default"><?= __('GLOBAL__SUBMIT') ?></button>
                <div class="form-group">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="form-group">
                    <u><?= __('USER__PROFILE_FORM_IMG') ?> :</u><br>
                    - <?= __('USER__IMG_UPLOAD_TYPE_PNG') ?><br>
                    - <?= str_replace('{PIXELS}', $skin_width_max, __('USER__IMG_UPLOAD_WIDTH_MAX')) ?><br>
                    - <?= str_replace('{PIXELS}', $skin_height_max, __('USER__IMG_UPLOAD_HEIGHT_MAX')) ?><br>
                </div>
                <?= $this->Form->end() ?>
            <?php } ?>

            <?php if ($can_cape) { ?>
                <hr>

                <h3><?= __('API__CAPE_LABEL') ?></h3>

                <?= $this->Form->create(null, [
                'class' => 'form-inline',
                'id' => 'cape',
                'method' => 'post',
                'url' => ['_name' => 'user_upload_cape', '?' => ['crsf' => true]],
                'data-ajax' => 'true',
                'data-upload-image' => 'true',
                'enctype' => 'multipart/form-data'
            ]) ?>
                <div class="form-group">
                    <label for="cape-image"><?= __('FORM__BROWSE') ?></label>
                    <input
                        id="cape-image"
                        name="image"
                        type="file"
                        class="form-control"
                    >
                </div>
                <button type="submit" class="btn btn-default"><?= __('GLOBAL__SUBMIT') ?></button>
                <div class="form-group">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                <div class="form-group">
                    <u><?= __('USER__PROFILE_FORM_IMG') ?> :</u><br>
                    - <?= __('USER__IMG_UPLOAD_TYPE_PNG') ?><br>
                    - <?= str_replace('{PIXELS}', $cape_width_max, __('USER__IMG_UPLOAD_WIDTH_MAX')) ?><br>
                    - <?= str_replace('{PIXELS}', $cape_height_max, __('USER__IMG_UPLOAD_HEIGHT_MAX')) ?><br>
                </div>
                <?= $this->Form->end() ?>
            <?php } ?>

            <?php if ($EyPlugin->isInstalled('eywek.shop')) { ?>
                <hr>
                <h3 class="text-center"><?= __('SHOP__HISTORY_PURCHASES') ?></h3>
                <table class="table table-bordered" id="users">
                    <thead>
                    <tr>
                        <th><?= __('DASHBOARD__PURCHASES') ?> ID</th>
                        <th><?= __('GLOBAL__CREATED') ?></th>
                        <th><?= __('SHOP__ITEM_PRICE') ?></th>
                        <th class="right"><?= __('SHOP__ITEMS') ?></th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($histories as $value) { ?>
                        <tr>
                            <td><?= h($value["ItemsBuyHistory"]["id"]) ?></td>
                            <td><?= h($value["ItemsBuyHistory"]["created"]) ?></td>
                            <td><?= h($value["Item"]["price"]) ?></td>
                            <td><?= h($value["Item"]["name"]) ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>

            <?= $Module->loadModules('user_profile') ?>
        </div>
    </div>
</div>
