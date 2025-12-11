<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('CONFIG__GENERAL_PREFERENCES') ?></h3>
                </div>
                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_configuration_index'],
                        'method' => 'post'
                    ]) ?>

                    <div class="nav-tabs-custom">

                        <ul class="nav nav-tabs">
                            <li class="nav-item">
                                <a class="nav-link text-dark active" href="#tab_1" data-toggle="tab" aria-expanded="true">
                                    <?= __('CONFIG__GENERAL_PREFERENCES') ?>
                                </a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link text-dark" href="#tab_2" data-toggle="tab" aria-expanded="false">
                                    <?= __('CONFIG__OTHER_PREFERENCES') ?>
                                </a>
                            </li>
                        </ul>

                        <div class="tab-content">
                            <div class="tab-pane fade show active" id="tab_1">

                                <div class="form-group">
                                    <label for="website-url"><?= __('CONFIG__KEY_WEBSITE_URL') ?></label>
                                    <?= $this->Form->control('website_url', [
                                        'label' => false,
                                        'id' => 'website-url',
                                        'type' => 'text',
                                        'class' => 'form-control',
                                        'value' => $config['website_url']
                                    ]) ?>
                                </div>

                                <div class="form-group">
                                    <label for="config-name"><?= __('CONFIG__KEY_NAME') ?></label>
                                    <?= $this->Form->control('name', [
                                        'label' => false,
                                        'id' => 'config-name',
                                        'type' => 'text',
                                        'class' => 'form-control',
                                        'value' => $config['name']
                                    ]) ?>
                                </div>

                                <div class="form-group">
                                    <label for="config-email"><?= __('CONFIG__KEY_EMAIL') ?></label>
                                    <?= $this->Form->control('email', [
                                        'label' => false,
                                        'id' => 'config-email',
                                        'type' => 'text',
                                        'class' => 'form-control',
                                        'value' => $config['email']
                                    ]) ?>
                                </div>

                                <?php if ($shopIsInstalled) { ?>

                                    <div class="form-group">
                                        <label for="money-name-singular"><?= __('CONFIG__KEY_MONEY_NAME_SINGULAR') ?></label>
                                        <?= $this->Form->control('money_name_singular', [
                                            'label' => false,
                                            'id' => 'money-name-singular',
                                            'type' => 'text',
                                            'class' => 'form-control',
                                            'value' => $config['money_name_singular']
                                        ]) ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="money-name-plural"><?= __('CONFIG__KEY_MONEY_NAME_PLURAL') ?></label>
                                        <?= $this->Form->control('money_name_plural', [
                                            'label' => false,
                                            'id' => 'money-name-plural',
                                            'type' => 'text',
                                            'class' => 'form-control',
                                            'value' => $config['money_name_plural']
                                        ]) ?>
                                    </div>

                                <?php } ?>

                                <?= $this->Html->script('bootstrap-4/plugins/bootstrap-select/bootstrap-select.min.js') ?>
                                <?= $this->Html->css('bootstrap-4/plugins/bootstrap-select/bootstrap-select.min.css') ?>

                                <div class="form-group">
                                    <label for="config-lang"><?= __('CONFIG__KEY_LANG') ?></label>
                                    <div class="form-group">
                                        <?= $this->Form->select(
                                            'lang',
                                            $config['languages_available'],
                                            [
                                                'id' => 'config-lang',
                                                'data-live-search' => 'true',
                                                'class' => 'selectpicker',
                                                'value' => $config['lang']
                                            ]
                                        ) ?>
                                        <a href="<?= $this->Url->build(['_name' => 'admin_configuration_edit_lang']) ?>"
                                           class="btn btn-info">
                                            <?= __('CONFIG__EDIT_LANG_FILE') ?>
                                        </a>
                                    </div>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label for="config-passwords-hash"><?= __('CONFIG__KEY_PASSWORDS_HASH') ?></label>
                                    <div class="form-group">
                                        <?= $this->Form->select(
                                            'passwords_hash',
                                            [
                                                'sha256' => 'sha256',
                                                'sha1' => 'sha1',
                                                'sha384' => 'sha384',
                                                'sha512' => 'sha512'
                                            ],
                                            [
                                                'id' => 'config-passwords-hash',
                                                'data-live-search' => 'true',
                                                'class' => 'selectpicker',
                                                'value' => $config['passwords_hash']
                                            ]
                                        ) ?>
                                    </div>
                                    <div class="form-group">
                                        <input type="hidden" id="passwords-salt" name="passwords_salt"
                                               value="<?= $config['passwords_salt'] ?>">
                                        <div class="checkbox">
                                            <input
                                                id="passwords-salt-checkbox"
                                                name="passwords_salt_checkbox"
                                                type="checkbox"
                                                <?= $config['passwords_salt'] == '1' ? 'checked' : '' ?>
                                            >
                                            <label for="passwords-salt-checkbox"><?= __('CONFIG__KEY_PASSWORDS_SALT') ?></label>
                                        </div>
                                    </div>
                                    <small class="text-danger"><?= __('CONFIG__KEY_PASSWORDS_ADVERTISSEMENT') ?></small>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label><?= __('CONFIG__CHECK_UUID') ?></label>
                                    <div class="form-group">
                                        <input type="hidden" id="check-uuid" name="check_uuid"
                                               value="<?= $config['check_uuid'] ?>">
                                        <div class="checkbox">
                                            <input
                                                id="check-uuid-checkbox"
                                                name="check_uuid_checkbox"
                                                type="checkbox"
                                                <?= $config['check_uuid'] == '1' ? 'checked' : '' ?>
                                            >
                                            <label for="check-uuid-checkbox"><?= __('CONFIG__CHECK_UUID_CHANGE') ?></label>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label><?= __('CONFIG__MICROSOFT_CONNECTION') ?></label>
                                    <br>
                                    <small class="text-danger"><?= __('CONFIG__MICROSOFT_CONNECTION_REQUIREMENT') ?></small>
                                    <br>

                                    <label for="microsoft-client-id"><?= __('CONFIG__MICROSOFT_CLIENT_ID') ?></label>
                                    <?= $this->Form->control('microsoft_client_id', [
                                        'label' => false,
                                        'id' => 'microsoft-client-id',
                                        'type' => 'text',
                                        'class' => 'form-control',
                                        'value' => $config['microsoft_client_id']
                                    ]) ?>

                                    <label for="microsoft-client-secret"><?= __('CONFIG__MICROSOFT_CLIENT_SECRET') ?></label>
                                    <?= $this->Form->control('microsoft_client_secret', [
                                        'label' => false,
                                        'id' => 'microsoft-client-secret',
                                        'type' => 'text',
                                        'class' => 'form-control',
                                        'value' => $config['microsoft_client_secret']
                                    ]) ?>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <div class="form-group">
                                        <label for="config-condition"><?= __('CONFIG__CONDITION_TITLE') ?></label>
                                        <?= $this->Form->control('condition', [
                                            'label' => false,
                                            'id' => 'config-condition',
                                            'type' => 'text',
                                            'class' => 'form-control',
                                            'value' => $config['condition']
                                        ]) ?>
                                    </div>
                                    <small class="text-danger"><?= __('CONFIG__CONDITION') ?></small>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label for="config-session-type"><?= __('CONFIG__KEY_SESSION_TYPE') ?></label>
                                    <div class="form-group">
                                        <?= $this->Form->select(
                                            'session_type',
                                            [
                                                'php' => __('CONFIG__KEY_SESSION_TYPE_PHP'),
                                                'database' => __('CONFIG__KEY_SESSION_TYPE_DB')
                                            ],
                                            [
                                                'id' => 'config-session-type',
                                                'data-live-search' => 'true',
                                                'class' => 'selectpicker',
                                                'value' => (!$config['session_type']) ? 'php' : $config['session_type']
                                            ]
                                        ) ?>
                                    </div>
                                    <small class="text-info"><?= __('CONFIG__KEY_SESSION_TYPE_INFO') ?></small>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label for="config-version"><?= __('CONFIG__KEY_VERSION') ?></label>
                                    <input
                                        type="text"
                                        id="config-version"
                                        value="<?= file_get_contents(ROOT . DS . 'VERSION') ?>"
                                        class="form-control disabled"
                                        disabled
                                    >
                                </div>

                            </div>

                            <div class="tab-pane fade" id="tab_2">

                                <div class="form-group">
                                    <label><?= __('CONFIG__KEY_MEMBER_PAGE_TYPE') ?></label>
                                    <br>
                                    <small><?= __('CONFIG__KEY_MEMBER_PAGE_TYPE_EXPLAIN') ?></small>
                                    <div class="radio">
                                        <input
                                            type="radio"
                                            id="member-page-type-default"
                                            name="member_page_type"
                                            value="0"
                                            <?= ($config['member_page_type'] == '0') ? 'checked' : '' ?>
                                        >
                                        <label for="member-page-type-default"><?= __('CONFIG__KEY_MEMBER_PAGE_TYPE_DEFAULT') ?></label>
                                    </div>
                                    <div class="radio">
                                        <input
                                            type="radio"
                                            id="member-page-type-search"
                                            name="member_page_type"
                                            value="1"
                                            <?= ($config['member_page_type'] == '1') ? 'checked' : '' ?>
                                        >
                                        <label for="member-page-type-search"><?= __('CONFIG__KEY_MEMBER_PAGE_TYPE_SEARCH') ?></label>
                                    </div>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label><?= __('CONFIG__KEY_CONFIRM_MAIL_SIGNUP') ?></label>
                                    <br>
                                    <small><?= __('CONFIG__CONFIRM_MAIL_SIGNUP_EXPLAIN') ?></small>
                                    <div class="radio">
                                        <input
                                            type="radio"
                                            id="confirm-mail-signup-enable"
                                            name="confirm_mail_signup"
                                            value="1"
                                            <?= ($config['confirm_mail_signup'] == '1') ? 'checked' : '' ?>
                                        >
                                        <label for="confirm-mail-signup-enable"><?= __('GLOBAL__ENABLE') ?></label>
                                    </div>
                                    <div class="radio">
                                        <input
                                            type="radio"
                                            id="confirm-mail-signup-disable"
                                            name="confirm_mail_signup"
                                            value="0"
                                            <?= ($config['confirm_mail_signup'] == '0') ? 'checked' : '' ?>
                                        >
                                        <label for="confirm-mail-signup-disable"><?= __('GLOBAL__DISABLE') ?></label>
                                    </div>
                                </div>

                                <div
                                    id="confirm_mail_signup"
                                    style="display:<?= ($config['confirm_mail_signup'] == '1') ? 'block' : 'none' ?>;"
                                >
                                    <div class="form-group">
                                        <label><?= __('CONFIG__KEY_CONFIRM_MAIL_SIGNUP_BLOCK') ?></label>
                                        <div class="radio">
                                            <input
                                                type="radio"
                                                id="confirm-mail-signup-block-enable"
                                                name="confirm_mail_signup_block"
                                                value="1"
                                                <?= ($config['confirm_mail_signup_block'] == '1') ? 'checked' : '' ?>
                                            >
                                            <label for="confirm-mail-signup-block-enable"><?= __('GLOBAL__ENABLE') ?></label>
                                        </div>
                                        <div class="radio">
                                            <input
                                                type="radio"
                                                id="confirm-mail-signup-block-disable"
                                                name="confirm_mail_signup_block"
                                                value="0"
                                                <?= ($config['confirm_mail_signup_block'] == '0') ? 'checked' : '' ?>
                                            >
                                            <label for="confirm-mail-signup-block-disable"><?= __('GLOBAL__DISABLE') ?></label>
                                        </div>
                                    </div>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label><?= __('CONFIG__KEY_CAPTCHA_TYPE') ?></label>
                                    <div class="radio">
                                        <input
                                            type="radio"
                                            id="captcha-type-normal"
                                            name="captcha_type"
                                            value="1"
                                            <?= ($config['captcha_type'] == '1') ? 'checked' : '' ?>
                                        >
                                        <label for="captcha-type-normal"><?= __('GLOBAL__TYPE_NORMAL') ?></label>
                                    </div>
                                    <div class="radio">
                                        <input
                                            type="radio"
                                            id="captcha-type-google"
                                            name="captcha_type"
                                            value="2"
                                            <?= ($config['captcha_type'] == '2') ? 'checked' : '' ?>
                                        >
                                        <label for="captcha-type-google"><?= __('CONFIG__TYPE_CAPTCHA_GOOGLE') ?></label>
                                    </div>

                                    <div class="radio">
                                        <input
                                            type="radio"
                                            id="captcha-type-hcaptcha"
                                            name="captcha_type"
                                            value="3"
                                            <?= ($config['captcha_type'] == '3') ? 'checked' : '' ?>
                                        >
                                        <label for="captcha-type-hcaptcha"><?= __('CONFIG__TYPE_CAPTCHA_HCAPTCHA') ?></label>
                                    </div>
                                </div>

                                <div
                                    id="captcha"
                                    style="display:<?= ($config['captcha_type'] == '2' || $config['captcha_type'] == '3') ? 'block' : 'none' ?>;"
                                >
                                    <div class="form-group">
                                        <label for="captcha-sitekey"><?= __('CONFIG__KEY_CAPTCHA_SITEKEY') ?></label>
                                        <?= $this->Form->control('captcha_sitekey', [
                                            'label' => false,
                                            'id' => 'captcha-sitekey',
                                            'type' => 'text',
                                            'class' => 'form-control',
                                            'value' => $config['captcha_sitekey']
                                        ]) ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="captcha-secret"><?= __('CONFIG__KEY_CAPTCHA_SECRET') ?></label>
                                        <?= $this->Form->control('captcha_secret', [
                                            'label' => false,
                                            'id' => 'captcha-secret',
                                            'type' => 'text',
                                            'class' => 'form-control',
                                            'value' => $config['captcha_secret']
                                        ]) ?>
                                    </div>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label for="google-analytics"><?= __('CONFIG__KEY_GOOGLE_ANALYTICS') ?></label>
                                    <?= $this->Form->control('google_analytics', [
                                        'label' => false,
                                        'id' => 'google-analytics',
                                        'type' => 'text',
                                        'class' => 'form-control',
                                        'value' => $config['google_analytics'],
                                        'maxlength' => '15'
                                    ]) ?>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label for="end-layout-code"><?= __('CONFIG__KEY_END_LAYOUT_COE') ?></label>
                                    <?= $this->Form->textarea('end_layout_code', [
                                        'label' => false,
                                        'id' => 'end-layout-code',
                                        'rows' => '5',
                                        'type' => 'text',
                                        'class' => 'form-control',
                                        'value' => $config['end_layout_code']
                                    ]) ?>
                                </div>

                                <hr>

                                <div class="form-group">
                                    <label><?= __('CONFIG__KEY_EMAIL_SEND_TYPE') ?></label>
                                    <div class="radio">
                                        <input
                                            type="radio"
                                            id="email-send-type-normal"
                                            name="email_send_type"
                                            value="1"
                                            <?= ($config['email_send_type'] == '1') ? 'checked' : '' ?>
                                        >
                                        <label for="email-send-type-normal"><?= __('GLOBAL__TYPE_NORMAL') ?></label>
                                    </div>
                                    <div class="radio">
                                        <input
                                            type="radio"
                                            id="email-send-type-smtp"
                                            name="email_send_type"
                                            value="2"
                                            <?= ($config['email_send_type'] == '2') ? 'checked' : '' ?>
                                        >
                                        <label for="email-send-type-smtp"><?= __('SMTP') ?></label>
                                    </div>
                                </div>

                                <div
                                    id="smtp-config"
                                    style="display:<?= ($config['email_send_type'] == '1') ? 'none' : 'block' ?>;"
                                >
                                    <div class="form-group">
                                        <label for="smtp-host"><?= __('CONFIG__KEY_SMTP_HOST') ?></label>
                                        <?= $this->Form->control('smtpHost', [
                                            'label' => false,
                                            'id' => 'smtp-host',
                                            'type' => 'text',
                                            'class' => 'form-control',
                                            'value' => $config['smtpHost'],
                                            'autocomplete' => 'off'
                                        ]) ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="smtp-username"><?= __('CONFIG__KEY_SMTP_USERNAME') ?></label>
                                        <?= $this->Form->control('smtpUsername', [
                                            'label' => false,
                                            'id' => 'smtp-username',
                                            'type' => 'text',
                                            'class' => 'form-control',
                                            'value' => $config['smtpUsername'],
                                            'autocomplete' => 'off'
                                        ]) ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="smtp-port"><?= __('CONFIG__KEY_SMTP_PORT') ?></label>
                                        <?= $this->Form->control('smtpPort', [
                                            'label' => false,
                                            'id' => 'smtp-port',
                                            'type' => 'text',
                                            'class' => 'form-control',
                                            'value' => $config['smtpPort'],
                                            'autocomplete' => 'off'
                                        ]) ?>
                                    </div>

                                    <div class="form-group">
                                        <label for="smtp-password"><?= __('CONFIG__KEY_SMTP_PASSWORD') ?></label>
                                        <?= $this->Form->control('smtpPassword', [
                                            'label' => false,
                                            'id' => 'smtp-password',
                                            'type' => 'password',
                                            'class' => 'form-control',
                                            'value' => $config['smtpPassword'],
                                            'autocomplete' => 'off'
                                        ]) ?>
                                    </div>
                                </div>

                            </div>
                        </div>
                    </div>

                    <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                    <a href="<?= $this->Url->build(['_name' => 'admin_configuration_index']) ?>"
                       type="button" class="btn btn-default"><?= __('GLOBAL__CANCEL') ?></a>

                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        let passwordsSaltCheckbox = document.getElementById('passwords-salt-checkbox');
        let passwordsSaltHidden = document.getElementById('passwords-salt');
        if (passwordsSaltCheckbox && passwordsSaltHidden) {
            passwordsSaltCheckbox.addEventListener('change', function () {
                passwordsSaltHidden.value = passwordsSaltCheckbox.checked ? '1' : '0';
            });
        }

        let checkUuidCheckbox = document.getElementById('check-uuid-checkbox');
        let checkUuidHidden = document.getElementById('check-uuid');
        if (checkUuidCheckbox && checkUuidHidden) {
            checkUuidCheckbox.addEventListener('change', function () {
                checkUuidHidden.value = checkUuidCheckbox.checked ? '1' : '0';
            });
        }

        let confirmMailRadios = document.querySelectorAll('input[name="confirm_mail_signup"]');
        let confirmMailBlock = document.getElementById('confirm_mail_signup');
        if (confirmMailRadios.length && confirmMailBlock) {
            confirmMailRadios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    if (radio.value === '1') {
                        confirmMailBlock.style.display = 'block';
                    } else {
                        confirmMailBlock.style.display = 'none';
                    }
                });
            });
        }

        let captchaRadios = document.querySelectorAll('input[name="captcha_type"]');
        let captchaBlock = document.getElementById('captcha');
        if (captchaRadios.length && captchaBlock) {
            captchaRadios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    if (radio.value === '2' || radio.value === '3') {
                        captchaBlock.style.display = 'block';
                    } else {
                        captchaBlock.style.display = 'none';
                    }
                });
            });
        }

        let emailSendTypeRadios = document.querySelectorAll('input[name="email_send_type"]');
        let smtpConfigBlock = document.getElementById('smtp-config');
        if (emailSendTypeRadios.length && smtpConfigBlock) {
            emailSendTypeRadios.forEach(function (radio) {
                radio.addEventListener('change', function () {
                    if (radio.value === '2') {
                        smtpConfigBlock.style.display = 'block';
                    } else {
                        smtpConfigBlock.style.display = 'none';
                    }
                });
            });
        }
    });
</script>
