<!-- Modal (connexion ...) -->
<div class="modal modal-medium fade" id="login" tabindex="-1" role="dialog" aria-labelledby="loginLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only"><?= __('GLOBAL__CLOSE') ?></span>
                </button>
                <h4 class="modal-title" id="loginLabel"><?= __('USER__LOGIN') ?></h4>
            </div>

            <?= $this->Form->create(null, [
                'id' => 'login-before-two-factor-auth',
                'url' => ['_name' => 'auth_login'],
                'type' => 'post',
                'data-ajax' => 'true',
                'data-callback-function' => 'afterLogin',
            ]) ?>
                <div class="modal-body">
                    <div class="ajax-msg"></div>

                    <div class="form-group">
                        <label for="login-pseudo"><?= __('USER__USERNAME') ?></label>
                        <input type="text" class="form-control" id="login-pseudo" name="pseudo"
                               placeholder="<?= __('USER__USERNAME_LABEL') ?>">
                    </div>

                    <div class="form-group">
                        <label for="login-password"><?= __('USER__PASSWORD') ?></label>
                        <input type="password" class="form-control" id="login-password" name="password"
                               placeholder="<?= __('USER__PASSWORD_LABEL') ?>">
                    </div>

                    <div class="row">
                        <div class="col-lg-6">
                            <div class="checkbox">
                                <label for="login-remember">
                                    <input type="checkbox" id="login-remember" name="remember_me">
                                    <?= __('USER__REMEMBER_ME') ?>
                                </label>
                            </div>
                        </div>
                        <div class="col-lg-6 text-center">
                            <a href="#" data-toggle="modal" data-target="#lostpasswd" data-dismiss="modal">
                                <?= __('USER__PASSWORD_FORGOT_LABEL') ?>
                            </a>
                        </div>
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-block"><?= __('USER__LOGIN') ?></button>
                </div>
            <?= $this->Form->end() ?>

            <script type="text/javascript">
                function afterLogin(req, res) {
                    if (res['two-factor-auth'] === undefined) {
                        window.location.href = '?t_' + Date.now();
                        return;
                    }

                    const formBefore = document.getElementById('login-before-two-factor-auth');
                    const formTwoFactor = document.getElementById('login-two-factor-auth');

                    const checkboxSource = formBefore.querySelector('input[name="remember_me"]');
                    const inputTarget = formTwoFactor.querySelector('input[name="remember_me"]');

                    if (checkboxSource && inputTarget) {
                        inputTarget.value = checkboxSource.checked ? 1 : 0;
                    }

                    formBefore.style.display = 'none';
                    formTwoFactor.style.display = 'block';
                }
            </script>

            <?= $this->Form->create(null, [
                'id' => 'login-two-factor-auth',
                'style' => 'display:none;',
                'url' => ['_name' => 'auth_2fa_validate'],
                'type' => 'post',
                'data-ajax' => 'true',
                'data-redirect-url' => '?',
            ]) ?>
                <div class="modal-body">
                    <div class="ajax-msg"></div>
                    <input type="hidden" name="remember_me">

                    <div class="form-group">
                        <label for="login-2fa-code"><?= __('USER__LOGIN_CODE') ?></label>
                        <input type="text" class="form-control" id="login-2fa-code" name="code"
                               placeholder="<?= __('USER__LOGIN_CODE') ?>">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-block"><?= __('USER__LOGIN') ?></button>
                </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<div class="modal modal-medium fade" id="lostpasswd" tabindex="-1" role="dialog" aria-labelledby="lostpasswdLabel"
     aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only"><?= __('GLOBAL__CLOSE') ?></span>
                </button>
                <h4 class="modal-title" id="lostpasswdLabel"><?= __('USER__PASSWORD_FORGOT_LABEL') ?></h4>
            </div>

            <?= $this->Form->create(null, [
                'url' => ['_name' => 'auth_lost_password'],
                'type' => 'post',
                'data-ajax' => 'true',
            ]) ?>
                <div class="modal-body">
                    <div class="ajax-msg"></div>

                    <div class="form-group">
                        <label for="lost-email"><?= __('USER__EMAIL') ?></label>
                        <input type="email" class="form-control" id="lost-email" name="email"
                               placeholder="<?= __('USER__EMAIL_LABEL') ?>">
                    </div>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <?= __('USER__PASSWORD_FORGOT_SEND_MAIL') ?>
                    </button>
                </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<?php if (!empty($resetpsswd)) { ?>
    <div class="modal modal-medium fade" id="lostpasswd2" tabindex="-1" role="dialog"
         aria-labelledby="lostpasswd2Label" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only"><?= __('GLOBAL__CLOSE') ?></span>
                    </button>
                    <h4 class="modal-title" id="lostpasswd2Label"><?= __('USER__PASSWORD_FORGOT_LABEL') ?></h4>
                </div>

                <?= $this->Form->create(null, [
                    'url' => ['_name' => 'auth_reset_password'],
                    'type' => 'post',
                    'data-ajax' => 'true',
                    'data-redirect-url' => '?',
                ]) ?>
                    <div class="modal-body">
                        <div class="ajax-msg"></div>

                        <input type="hidden" name="key" value="<?= $resetpsswd['key'] ?>">
                        <input type="hidden" name="email" value="<?= $resetpsswd['email'] ?>">

                        <div class="form-group">
                            <label for="reset-password"><?= __('USER__PASSWORD') ?></label>
                            <input type="password" class="form-control" id="reset-password" name="password"
                                   placeholder="<?= __('USER__PASSWORD_LABEL') ?>">
                        </div>

                        <div class="form-group">
                            <label for="reset-password-confirm"><?= __('USER__PASSWORD_CONFIRM') ?></label>
                            <input type="password" class="form-control" id="reset-password-confirm" name="password2"
                                   placeholder="<?= __('USER__PASSWORD_CONFIRM_LABEL') ?>">
                        </div>
                    </div>

                    <div class="modal-footer">
                        <button type="submit" class="btn btn-success btn-block">
                            <?= __('GLOBAL__SAVE') ?>
                        </button>
                    </div>
                <?= $this->Form->end() ?>
            </div>
        </div>
    </div>
<?php } ?>

<div class="modal fade" id="register" tabindex="-1" role="dialog"
     aria-labelledby="registerLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal">
                    <span aria-hidden="true">&times;</span>
                    <span class="sr-only"><?= __('GLOBAL__CLOSE') ?></span>
                </button>
                <h4 class="modal-title" id="registerLabel"><?= __('USER__REGISTER') ?></h4>
            </div>

            <?= $this->Form->create(null, [
                'url' => ['_name' => 'auth_register'],
                'type' => 'post',
                'data-ajax' => 'true',
                'data-redirect-url' => '?',
            ]) ?>
                <div class="modal-body">
                    <div class="ajax-msg"></div>

                    <div class="form-group">
                        <label for="register-pseudo"><?= __('USER__USERNAME') ?></label>
                        <input type="text" class="form-control" id="register-pseudo" name="pseudo"
                               placeholder="<?= __('USER__USERNAME_LABEL') ?>">
                    </div>

                    <div class="form-group">
                        <label for="register-password"><?= __('USER__PASSWORD') ?></label>
                        <input type="password" class="form-control" id="register-password" name="password"
                               placeholder="<?= __('USER__PASSWORD_LABEL') ?>">
                    </div>

                    <div class="form-group">
                        <label for="register-password-confirm"><?= __('USER__PASSWORD_CONFIRM') ?></label>
                        <input type="password" class="form-control" id="register-password-confirm"
                               name="password_confirmation"
                               placeholder="<?= __('USER__PASSWORD_CONFIRM_LABEL') ?>">
                    </div>

                    <div class="form-group">
                        <label for="register-email"><?= __('USER__EMAIL') ?></label>
                        <input type="email" class="form-control" id="register-email" name="email"
                               placeholder="<?= __('USER__EMAIL_LABEL') ?>">
                    </div>

                    <?php if (!empty($condition)) { ?>
                        <div class="checkbox">
                            <label for="register-condition">
                                <input type="checkbox" id="register-condition" name="condition">
                                <?= __('USER__CONDITION_1') ?>
                                <a href="<?= $condition ?>"><?= __('USER__CONDITION_2') ?></a>
                            </label>
                        </div>
                    <?php } ?>
                </div>

                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary btn-block">
                        <?= __('USER__REGISTER') ?>
                    </button>
                </div>
            <?= $this->Form->end() ?>
        </div>
    </div>
</div>

<?php if ($this->Auth->isConnected()) { ?>
    <div class="modal modal-medium fade" id="notifications_modal" tabindex="-1" role="dialog">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <button type="button" class="close" data-dismiss="modal">
                        <span aria-hidden="true">&times;</span>
                        <span class="sr-only"><?= __('GLOBAL__CLOSE') ?></span>
                    </button>
                    <h4 class="modal-title"><?= __('NOTIFICATIONS__LIST') ?></h4>
                </div>

                <div class="modal-body" style="padding:0;">
                    <div class="notifications-list"></div>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-default btn-block"
                            onclick="notification.markAllAsSeen()" data-dismiss="modal">
                        <?= __('NOTIFICATIONS__MARK_ALL_AS_SEEN') ?>
                    </button>
                    <button type="button" class="btn btn-danger btn-block"
                            onclick="notification.clearAll()" data-dismiss="modal">
                        <?= __('NOTIFICATIONS__CLEAR_ALL') ?>
                    </button>
                </div>
            </div>
        </div>
    </div>
<?php } ?>
