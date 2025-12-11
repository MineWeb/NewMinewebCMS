<?php

use Cake\Routing\Router;

?>
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
                <p><b><?= __('USER__USERNAME') ?> :</b> <?= $user['pseudo'] ?></p>
            </div>
            <div class="section">
                <p><b><?= __('USER__EMAIL') ?> :</b> <span id="email"><?= $user['email'] ?></span></p>
            </div>
            <div class="section">
                <p>
                    <b><?= __('USER__RANK') ?> :</b>
                    <?php foreach ($available_ranks as $key => $value) {
                        if ($user['rank'] == $key) {
                            echo $value;
                        }
                    } ?>
                </p>
            </div>
            <?php if ($EyPlugin->isInstalled('eywek.shop')) { ?>
                <div class="section">
                    <p><b><?= __('USER__MONEY') ?> :</b> <span class="money"><?= $user['money'] ?></span></p>
                </div>
            <?php } ?>

            <div class="section">
                <p><b><?= __('IP') ?> :</b> <?= $user['ip'] ?></p>
            </div>

            <div class="section">
                <p><b><?= __('GLOBAL__CREATED') ?> :</b> <?= $this->Lang->date($user['created']) ?></p>
            </div>
            <div class="callout" id="twoFactorAuthStatus">
                <div class="row">
                    <div class="col-md-12 col-sm-12">
                        <a id="toggleTwoFactorAuth"
                           data-status="<?= (isset($twoFactorAuthStatus) && $twoFactorAuthStatus) ? '1' : '0' ?>"
                           class="btn btn-info">Voulez-vous <span
                                id="twoFactorAuthStatusInfos"><?= (isset($twoFactorAuthStatus) && $twoFactorAuthStatus) ? 'désactiver' : 'activer' ?></span>
                            la double authentification ?</a>
                    </div>
                </div>
            </div>
            <div id="twoFactorAuthValid" class="text-center" style="display: none;">
                <img src="" id="two-factor-auth-qrcode" alt=""/>
                <p>
                    <small class="text-muted">Secret : <em id="two-factor-auth-secret"></em></small>
                </p>

                <form class="form-horizontal" method="POST" data-ajax="true"
                      action="<?= Router::url(['_name' => 'authentification_valid_enable']) ?>"
                      data-callback-function="afterValidQrCode">
                    <div class="ajax-msg"></div>

                    <div class="form-group text-center">
                        <label><?= __('USER__LOGIN_CODE') ?></label>
                        <div class="col-md-6" style="margin: 0 auto;float: none;">
                            <input type="text" class="form-control" name="code"
                                   placeholder="<?= __('USER__LOGIN_CODE_PLACEHOLDER') ?>">
                        </div>
                    </div>

                    <button type="submit" class="btn btn-info"><?= __('USER__VALID_CODE') ?></button>
                </form>
            </div>
            <script type="text/javascript">
                document.addEventListener('DOMContentLoaded', function () {
                    var toggleBtn = document.getElementById('toggleTwoFactorAuth')
                    if (!toggleBtn) {
                        return
                    }
                    var statusInfos = document.getElementById('twoFactorAuthStatusInfos')
                    var statusBlock = document.getElementById('twoFactorAuthStatus')
                    var validBlock = document.getElementById('twoFactorAuthValid')
                    var qrcodeImg = document.getElementById('two-factor-auth-qrcode')
                    var secretSpan = document.getElementById('two-factor-auth-secret')

                    toggleBtn.addEventListener('click', function (e) {
                        e.preventDefault()
                        var status = parseInt(toggleBtn.getAttribute('data-status') || '0')
                        toggleBtn.innerHTML = '<i class="fa fa-refresh fa-spin"></i>'
                        toggleBtn.classList.add('disabled')

                        if (!status) {
                            fetch('<?= Router::url(['_name' => 'authentification_generate_secret']) ?>', {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                                .then(function (response) {
                                    return response.json()
                                })
                                .then(function (data) {
                                    if (qrcodeImg && data.qrcode_url) {
                                        qrcodeImg.setAttribute('src', data.qrcode_url)
                                    }
                                    if (secretSpan && data.secret) {
                                        secretSpan.textContent = data.secret
                                    }
                                    if (statusBlock) {
                                        statusBlock.style.display = 'none'
                                    }
                                    if (validBlock) {
                                        validBlock.style.display = 'block'
                                    }
                                })
                                .finally(function () {
                                    toggleBtn.classList.remove('disabled')
                                })
                        } else {
                            fetch('<?= Router::url(['_name' => 'authentification_disable']) ?>', {
                                method: 'GET',
                                headers: {
                                    'X-Requested-With': 'XMLHttpRequest'
                                }
                            })
                                .then(function () {
                                    toggleBtn.innerHTML = 'Voulez-vous activer la double authentification ?'
                                    toggleBtn.classList.remove('disabled')
                                    toggleBtn.classList.remove('btn-primary')
                                    toggleBtn.classList.add('btn-primary')
                                    toggleBtn.setAttribute('data-status', '0')
                                    if (statusInfos) {
                                        statusInfos.textContent = 'activer'
                                    }
                                })
                        }
                    })
                })

                function afterValidQrCode(req, res) {
                    var toggleBtn = document.getElementById('toggleTwoFactorAuth')
                    var statusInfos = document.getElementById('twoFactorAuthStatusInfos')
                    var statusBlock = document.getElementById('twoFactorAuthStatus')
                    var validBlock = document.getElementById('twoFactorAuthValid')

                    if (toggleBtn) {
                        toggleBtn.innerHTML = 'Voulez-vous désactiver la double authentification ?'
                        toggleBtn.classList.remove('disabled')
                        toggleBtn.classList.remove('btn-primary')
                        toggleBtn.classList.add('btn-primary')
                        toggleBtn.setAttribute('data-status', '1')
                    }
                    if (statusInfos) {
                        statusInfos.textContent = 'désactiver'
                    }
                    if (validBlock) {
                        validBlock.style.display = 'none'
                    }
                    if (statusBlock) {
                        statusBlock.style.display = 'block'
                    }
                }
            </script>
            <hr>

            <h3><?= __('USER__UPDATE_PASSWORD') ?></h3>

            <form method="post" class="form-inline" data-ajax="true"
                  action="<?= Router::url(['_name' => 'user_change_pw']) ?>">
                <div class="form-group">
                    <input type="password" class="form-control" name="password"
                           placeholder="<?= __('USER__PASSWORD') ?>">
                </div>
                <div class="form-group">
                    <input type="password" class="form-control" name="password_confirmation"
                           placeholder="<?= __('USER__PASSWORD_CONFIRM') ?>">
                </div>

                <div class="form-group">
                    <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                </div>
            </form>

            <?php if ($Permissions->can('EDIT_HIS_EMAIL')) { ?>
                <hr>

                <h3><?= __('USER__UPDATE_EMAIL') ?></h3>

                <form method="post" class="form-inline" data-ajax="true"
                      action="<?= Router::url(['_name' => 'user_change_email']) ?>">
                    <div class="form-group">
                        <input type="email" class="form-control" name="email"
                               placeholder="<?= __('USER__EMAIL') ?>">
                    </div>
                    <div class="form-group">
                        <input type="email" class="form-control" name="email_confirmation"
                               placeholder="<?= __('USER__EMAIL_CONFIRM_LABEL') ?>">
                    </div>

                    <div class="form-group">
                        <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                    </div>
                </form>
            <?php } ?>

            <?php if ($shop_active) { ?>

                <hr>

                <h3><?= __('SHOP__USER_POINTS_TRANSFER') ?></h3>

                <form method="post" class="form-inline" data-ajax="true"
                      action="<?= Router::url(['plugin' => 'shop', 'controller' => 'payment', 'action' => 'transfer_points']) ?>">
                    <div class="form-group">
                        <input type="text" class="form-control" name="to"
                               placeholder="<?= __('SHOP__USER_POINTS_TRANSFER_WHO') ?>">
                    </div>
                    <div class="form-group">
                        <input type="text" class="form-control" name="how"
                               placeholder="<?= __('SHOP__USER_POINTS_TRANSFER_HOW_MANY') ?>">
                    </div>

                    <div class="form-group">
                        <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                    </div>
                </form>

            <?php } ?>

            <?php if ($can_skin) { ?>
                <hr>

                <h3><?= __('API__SKIN_LABEL') ?></h3>

                <form class="form-inline" method="post" id="skin" data-ajax="true"
                      data-upload-image="true" action="<?= Router::url(['_name' => 'user_upload_skin']) ?>">
                    <div class="form-group">
                        <label><?= __('FORM__BROWSE') ?></label>
                        <input name="image" type="file">
                    </div>
                    <input name="data[_Token][key]" value="<?= $csrfToken ?>" type="hidden">
                    <button type="submit" class="btn btn-default"><?= __('GLOBAL__SUBMIT') ?></button>
                    <div class="form-group">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="form-group">
                        <u><?= __('USER__PROFILE_FORM_IMG') ?> :</u><br>

                        - <?= __('USER__IMG_UPLOAD_TYPE_PNG') ?><br>
                        - <?= str_replace('{PIXELS}', $skin_width_max, __('USER__IMG_UPLOAD_WIDTH_MAX')) ?><br>
                        - <?= str_replace('{PIXELS}', $skin_height_max, __('USER__IMG_UPLOAD_HEIGHT_MAX')) ?>
                        <br>
                    </div>
                </form>
            <?php } ?>

            <?php if ($can_cape) { ?>
                <hr>

                <h3><?= __('API__CAPE_LABEL') ?></h3>

                <form class="form-inline" id="cape" method="post" data-ajax="true"
                      data-upload-image="true" action="<?= Router::url(['_name' => 'user_upload_cape', '?' => ['crsf' => true]]) ?>">
                    <div class="form-group">
                        <label><?= __('FORM__BROWSE') ?></label>
                        <input name="image" type="file">
                    </div>
                    <input name="_csrfToken" value="<?= $csrfToken ?>" type="hidden">
                    <button type="submit" class="btn btn-default"><?= __('GLOBAL__SUBMIT') ?></button>
                    <div class="form-group">&nbsp;&nbsp;&nbsp;&nbsp;</div>
                    <div class="form-group">

                        <u><?= __('USER__PROFILE_FORM_IMG') ?> :</u><br>

                        - <?= __('USER__IMG_UPLOAD_TYPE_PNG') ?><br>
                        - <?= str_replace('{PIXELS}', $cape_width_max, __('USER__IMG_UPLOAD_WIDTH_MAX')) ?><br>
                        - <?= str_replace('{PIXELS}', $cape_height_max, __('USER__IMG_UPLOAD_HEIGHT_MAX')) ?>
                        <br>
                    </div>
                </form>
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
                    <?php
                    foreach ($histories as $value) { ?>
                        <tr>
                            <td><?= $value["ItemsBuyHistory"]["id"] ?></td>
                            <td><?= $value["ItemsBuyHistory"]["created"] ?></td>
                            <td><?= $value["Item"]["price"] ?></td>
                            <td><?= $value["Item"]["name"] ?></td>
                        </tr>
                    <?php } ?>
                    </tbody>
                </table>
            <?php } ?>
            <?= $Module->loadModules('user_profile') ?>
        </div>
    </div>
</div>
