<section class="content">
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                    <div>
                        <h1 class="m-0">
                            <?= __('CONFIG__GENERAL_PREFERENCES') ?>
                        </h1>
                    </div>

                    <div class="d-flex flex-wrap" style="gap: 10px;">
                        <a
                            href="<?= $this->Url->build(['_name' => 'admin_configuration_index']) ?>"
                            class="btn btn-outline-secondary"
                        >
                            <?= __('GLOBAL__CANCEL') ?>
                        </a>
                        <button class="btn btn-primary" type="submit" form="configuration-form">
                            <i class="fas fa-save mr-1"></i>
                            <?= __('GLOBAL__SUBMIT') ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <?= $this->Form->create(null, [
            'id' => 'configuration-form',
            'url' => ['_name' => 'admin_configuration_save'],
            'method' => 'post',
            'data-ajax' => 'true',
        ]) ?>

        <div class="row">
            <div class="col-12">

                <div class="card">
                    <div class="card-header p-0 border-bottom-0">
                        <ul class="nav nav-tabs" id="config-tabs" role="tablist">
                            <li class="nav-item">
                                <a
                                    class="nav-link active"
                                    id="tab-general-link"
                                    data-toggle="tab"
                                    href="#tab-general"
                                    role="tab"
                                    aria-controls="tab-general"
                                    aria-selected="true"
                                >
                                    <i class="fas fa-sliders-h mr-1"></i>
                                    <?= __('CONFIG__GENERAL_PREFERENCES') ?>
                                </a>
                            </li>

                            <li class="nav-item">
                                <a
                                    class="nav-link"
                                    id="tab-other-link"
                                    data-toggle="tab"
                                    href="#tab-other"
                                    role="tab"
                                    aria-controls="tab-other"
                                    aria-selected="false"
                                >
                                    <i class="fas fa-cogs mr-1"></i>
                                    <?= __('CONFIG__OTHER_PREFERENCES') ?>
                                </a>
                            </li>
                        </ul>
                    </div>

                    <div class="card-body">
                        <div class="tab-content" id="config-tabs-content">

                            <div
                                class="tab-pane fade show active"
                                id="tab-general"
                                role="tabpanel"
                                aria-labelledby="tab-general-link"
                            >
                                <div class="row">
                                    <div class="col-lg-6">

                                        <div class="border rounded p-3 mb-3">
                                            <h5 class="mb-3"><?= __('CONFIG__GENERAL_PREFERENCES') ?></h5>

                                            <div class="form-group">
                                                <label for="website-url"><?= __('CONFIG__KEY_WEBSITE_URL') ?></label>
                                                <?= $this->Form->control('website_url', [
                                                    'label' => false,
                                                    'id' => 'website-url',
                                                    'type' => 'text',
                                                    'class' => 'form-control',
                                                    'value' => $config['website_url'],
                                                ]) ?>
                                            </div>

                                            <div class="form-group">
                                                <label for="config-name"><?= __('CONFIG__KEY_NAME') ?></label>
                                                <?= $this->Form->control('name', [
                                                    'label' => false,
                                                    'id' => 'config-name',
                                                    'type' => 'text',
                                                    'class' => 'form-control',
                                                    'value' => $config['name'],
                                                ]) ?>
                                            </div>

                                            <div class="form-group mb-0">
                                                <label for="config-email"><?= __('CONFIG__KEY_EMAIL') ?></label>
                                                <?= $this->Form->control('email', [
                                                    'label' => false,
                                                    'id' => 'config-email',
                                                    'type' => 'text',
                                                    'class' => 'form-control',
                                                    'value' => $config['email'],
                                                ]) ?>
                                            </div>
                                        </div>

                                        <?php if ($shopIsInstalled) { ?>
                                            <div class="border rounded p-3 mb-3">
                                                <h5 class="mb-3"><?= __('SHOP') ?></h5>

                                                <div class="form-group">
                                                    <label
                                                        for="money-name-singular"><?= __('CONFIG__KEY_MONEY_NAME_SINGULAR') ?></label>
                                                    <?= $this->Form->control('money_name_singular', [
                                                        'label' => false,
                                                        'id' => 'money-name-singular',
                                                        'type' => 'text',
                                                        'class' => 'form-control',
                                                        'value' => $config['money_name_singular'],
                                                    ]) ?>
                                                </div>

                                                <div class="form-group mb-0">
                                                    <label
                                                        for="money-name-plural"><?= __('CONFIG__KEY_MONEY_NAME_PLURAL') ?></label>
                                                    <?= $this->Form->control('money_name_plural', [
                                                        'label' => false,
                                                        'id' => 'money-name-plural',
                                                        'type' => 'text',
                                                        'class' => 'form-control',
                                                        'value' => $config['money_name_plural'],
                                                    ]) ?>
                                                </div>
                                            </div>
                                        <?php } ?>

                                        <div class="border rounded p-3 mb-3">
                                            <h5 class="mb-3"><?= __('CONFIG__CONDITION_TITLE') ?></h5>

                                            <div class="form-group mb-0">
                                                <label
                                                    for="config-condition"><?= __('CONFIG__CONDITION_TITLE') ?></label>
                                                <?= $this->Form->control('condition', [
                                                    'label' => false,
                                                    'id' => 'config-condition',
                                                    'type' => 'text',
                                                    'class' => 'form-control',
                                                    'value' => $config['condition'],
                                                ]) ?>
                                                <small
                                                    class="form-text text-danger"><?= __('CONFIG__CONDITION') ?></small>
                                            </div>
                                        </div>

                                        <div class="border rounded p-3 mb-0">
                                            <h5 class="mb-3"><?= __('CONFIG__KEY_VERSION') ?></h5>
                                            <div class="form-group mb-0">
                                                <label for="config-version"><?= __('CONFIG__KEY_VERSION') ?></label>
                                                <input
                                                    type="text"
                                                    id="config-version"
                                                    value="<?= file_get_contents(ROOT . DS . 'VERSION') ?>"
                                                    class="form-control"
                                                    disabled
                                                >
                                            </div>
                                        </div>

                                    </div>

                                    <div class="col-lg-6">

                                        <div class="border rounded p-3 mb-3">
                                            <h5 class="mb-3"><?= __('CONFIG__KEY_LANG') ?></h5>

                                            <?= $this->Html->script('bootstrap-4/plugins/bootstrap-select/bootstrap-select.min.js') ?>
                                            <?= $this->Html->css('bootstrap-4/plugins/bootstrap-select/bootstrap-select.min.css') ?>

                                            <div class="form-group mb-0">
                                                <label for="config-lang"><?= __('CONFIG__KEY_LANG') ?></label>

                                                <div class="d-flex flex-wrap align-items-start" style="gap: 10px;">
                                                    <div class="flex-grow-1">
                                                        <?= $this->Form->select(
                                                            'lang',
                                                            $config['languages_available'],
                                                            [
                                                                'id' => 'config-lang',
                                                                'data-live-search' => 'true',
                                                                'class' => 'selectpicker',
                                                                'value' => $config['lang'],
                                                                'title' => __('CONFIG__KEY_LANG'),
                                                            ]
                                                        ) ?>
                                                    </div>

                                                    <a
                                                        href="<?= $this->Url->build(['_name' => 'admin_configuration_edit_lang']) ?>"
                                                        class="btn btn-info"
                                                    >
                                                        <i class="fas fa-edit mr-1"></i>
                                                        <?= __('CONFIG__EDIT_LANG_FILE') ?>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="border rounded p-3 mb-3">
                                            <h5 class="mb-3"><?= __('CONFIG__KEY_PASSWORDS_HASH') ?></h5>

                                            <div class="form-group">
                                                <label
                                                    for="config-passwords-hash"><?= __('CONFIG__KEY_PASSWORDS_HASH') ?></label>
                                                <?= $this->Form->select(
                                                    'passwords_hash',
                                                    [
                                                        'sha256' => 'sha256',
                                                        'sha384' => 'sha384',
                                                        'sha512' => 'sha512',
                                                        'bcrypt' => 'bcrypt',
                                                    ],
                                                    [
                                                        'id' => 'config-passwords-hash',
                                                        'data-live-search' => 'true',
                                                        'class' => 'selectpicker',
                                                        'value' => $config['passwords_hash'],
                                                        'title' => __('CONFIG__KEY_PASSWORDS_HASH'),
                                                    ]
                                                ) ?>
                                            </div>

                                            <input
                                                type="hidden"
                                                id="passwords-salt"
                                                name="passwords_salt"
                                                value="<?= $config['passwords_salt'] ?>"
                                            >

                                            <div class="custom-control custom-switch">
                                                <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="passwords-salt-checkbox"
                                                    <?= $config['passwords_salt'] == '1' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="passwords-salt-checkbox">
                                                    <?= __('CONFIG__KEY_PASSWORDS_SALT') ?>
                                                </label>
                                            </div>

                                            <small class="form-text text-danger">
                                                <?= __('CONFIG__KEY_PASSWORDS_ADVERTISSEMENT') ?>
                                            </small>
                                        </div>

                                        <div class="border rounded p-3 mb-3">
                                            <h5 class="mb-3"><?= __('CONFIG__CHECK_UUID') ?></h5>

                                            <div class="custom-control custom-switch mb-0">
                                                <input
                                                    type="checkbox"
                                                    class="custom-control-input"
                                                    id="check-uuid-checkbox"
                                                    name="check_uuid"
                                                    <?= $config['check_uuid'] == '1' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="check-uuid-checkbox">
                                                    <?= __('CONFIG__CHECK_UUID_CHANGE') ?>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="border rounded p-3 mb-3">
                                            <h5 class="mb-2"><?= __('CONFIG__MICROSOFT_CONNECTION') ?></h5>
                                            <p class="mb-3 text-danger"><?= __('CONFIG__MICROSOFT_CONNECTION_REQUIREMENT') ?></p>

                                            <div class="row">
                                                <div class="col-lg-6">
                                                    <div class="form-group">
                                                        <label
                                                            for="microsoft-client-id"><?= __('CONFIG__MICROSOFT_CLIENT_ID') ?></label>
                                                        <?= $this->Form->control('microsoft_client_id', [
                                                            'label' => false,
                                                            'id' => 'microsoft-client-id',
                                                            'type' => 'text',
                                                            'class' => 'form-control',
                                                            'value' => $config['microsoft_client_id'],
                                                        ]) ?>
                                                    </div>
                                                </div>

                                                <div class="col-lg-6">
                                                    <div class="form-group mb-0">
                                                        <label
                                                            for="microsoft-client-secret"><?= __('CONFIG__MICROSOFT_CLIENT_SECRET') ?></label>
                                                        <?= $this->Form->control('microsoft_client_secret', [
                                                            'label' => false,
                                                            'id' => 'microsoft-client-secret',
                                                            'type' => 'text',
                                                            'class' => 'form-control',
                                                            'value' => $config['microsoft_client_secret'],
                                                        ]) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="border rounded p-3 mb-0">
                                            <h5 class="mb-3"><?= __('CONFIG__KEY_SESSION_TYPE') ?></h5>

                                            <div class="form-group mb-0">
                                                <label
                                                    for="config-session-type"><?= __('CONFIG__KEY_SESSION_TYPE') ?></label>
                                                <?= $this->Form->select(
                                                    'session_type',
                                                    [
                                                        'php' => __('CONFIG__KEY_SESSION_TYPE_PHP'),
                                                        'database' => __('CONFIG__KEY_SESSION_TYPE_DB'),
                                                    ],
                                                    [
                                                        'id' => 'config-session-type',
                                                        'data-live-search' => 'true',
                                                        'class' => 'selectpicker',
                                                        'value' => $config['session_type'] ?: 'php',
                                                        'title' => __('CONFIG__KEY_SESSION_TYPE'),
                                                    ]
                                                ) ?>
                                                <small
                                                    class="form-text text-info"><?= __('CONFIG__KEY_SESSION_TYPE_INFO') ?></small>
                                            </div>
                                        </div>

                                    </div>
                                </div>


                            </div>

                            <div
                                class="tab-pane fade"
                                id="tab-other"
                                role="tabpanel"
                                aria-labelledby="tab-other-link"
                            >
                                <div class="row">
                                    <div class="col-lg-6">

                                        <div class="border rounded p-3 mb-3">
                                            <h5 class="mb-3"><?= __('CONFIG__KEY_MEMBER_PAGE_TYPE') ?></h5>

                                            <p class="text-muted mb-2"><?= __('CONFIG__KEY_MEMBER_PAGE_TYPE_EXPLAIN') ?></p>

                                            <div class="custom-control custom-radio">
                                                <input
                                                    type="radio"
                                                    id="member-page-type-default"
                                                    name="member_page_type"
                                                    value="0"
                                                    class="custom-control-input"
                                                    <?= $config['member_page_type'] == '0' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="member-page-type-default">
                                                    <?= __('CONFIG__KEY_MEMBER_PAGE_TYPE_DEFAULT') ?>
                                                </label>
                                            </div>

                                            <div class="custom-control custom-radio mb-0">
                                                <input
                                                    type="radio"
                                                    id="member-page-type-search"
                                                    name="member_page_type"
                                                    value="1"
                                                    class="custom-control-input"
                                                    <?= $config['member_page_type'] == '1' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="member-page-type-search">
                                                    <?= __('CONFIG__KEY_MEMBER_PAGE_TYPE_SEARCH') ?>
                                                </label>
                                            </div>
                                        </div>

                                        <div class="border rounded p-3 mb-0">
                                            <h5 class="mb-3"><?= __('CONFIG__KEY_CONFIRM_MAIL_SIGNUP') ?></h5>

                                            <p class="text-muted mb-2"><?= __('CONFIG__CONFIRM_MAIL_SIGNUP_EXPLAIN') ?></p>

                                            <div class="custom-control custom-radio">
                                                <input
                                                    type="radio"
                                                    id="confirm-mail-signup-enable"
                                                    name="confirm_mail_signup"
                                                    value="1"
                                                    class="custom-control-input"
                                                    <?= $config['confirm_mail_signup'] == '1' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="confirm-mail-signup-enable">
                                                    <?= __('GLOBAL__ENABLE') ?>
                                                </label>
                                            </div>

                                            <div class="custom-control custom-radio">
                                                <input
                                                    type="radio"
                                                    id="confirm-mail-signup-disable"
                                                    name="confirm_mail_signup"
                                                    value="0"
                                                    class="custom-control-input"
                                                    <?= $config['confirm_mail_signup'] == '0' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="confirm-mail-signup-disable">
                                                    <?= __('GLOBAL__DISABLE') ?>
                                                </label>
                                            </div>

                                            <div
                                                id="confirm-mail-signup-block"
                                                class="mt-3"
                                                style="display:<?= $config['confirm_mail_signup'] == '1' ? 'block' : 'none' ?>;"
                                            >
                                                <div class="alert alert-info mb-0">
                                                    <div class="mb-2 font-weight-bold">
                                                        <?= __('CONFIG__KEY_CONFIRM_MAIL_SIGNUP_BLOCK') ?>
                                                    </div>

                                                    <div class="custom-control custom-radio">
                                                        <input
                                                            type="radio"
                                                            id="confirm-mail-signup-block-enable"
                                                            name="confirm_mail_signup_block"
                                                            value="1"
                                                            class="custom-control-input"
                                                            <?= $config['confirm_mail_signup_block'] == '1' ? 'checked' : '' ?>
                                                        >
                                                        <label class="custom-control-label"
                                                               for="confirm-mail-signup-block-enable">
                                                            <?= __('GLOBAL__ENABLE') ?>
                                                        </label>
                                                    </div>

                                                    <div class="custom-control custom-radio mb-0">
                                                        <input
                                                            type="radio"
                                                            id="confirm-mail-signup-block-disable"
                                                            name="confirm_mail_signup_block"
                                                            value="0"
                                                            class="custom-control-input"
                                                            <?= $config['confirm_mail_signup_block'] == '0' ? 'checked' : '' ?>
                                                        >
                                                        <label class="custom-control-label"
                                                               for="confirm-mail-signup-block-disable">
                                                            <?= __('GLOBAL__DISABLE') ?>
                                                        </label>
                                                    </div>
                                                </div>
                                            </div>

                                        </div>

                                    </div>

                                    <div class="col-lg-6">

                                        <div class="border rounded p-3 mb-3">
                                            <h5 class="mb-3"><?= __('CONFIG__KEY_CAPTCHA_TYPE') ?></h5>

                                            <div class="custom-control custom-radio">
                                                <input
                                                    type="radio"
                                                    id="captcha-type-normal"
                                                    name="captcha_type"
                                                    value="1"
                                                    class="custom-control-input"
                                                    <?= $config['captcha_type'] == '1' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="captcha-type-normal">
                                                    <?= __('GLOBAL__TYPE_NORMAL') ?>
                                                </label>
                                            </div>

                                            <div class="custom-control custom-radio">
                                                <input
                                                    type="radio"
                                                    id="captcha-type-google"
                                                    name="captcha_type"
                                                    value="2"
                                                    class="custom-control-input"
                                                    <?= $config['captcha_type'] == '2' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="captcha-type-google">
                                                    <?= __('CONFIG__TYPE_CAPTCHA_GOOGLE') ?>
                                                </label>
                                            </div>

                                            <div class="custom-control custom-radio">
                                                <input
                                                    type="radio"
                                                    id="captcha-type-hcaptcha"
                                                    name="captcha_type"
                                                    value="3"
                                                    class="custom-control-input"
                                                    <?= $config['captcha_type'] == '3' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="captcha-type-hcaptcha">
                                                    <?= __('CONFIG__TYPE_CAPTCHA_HCAPTCHA') ?>
                                                </label>
                                            </div>

                                            <div
                                                id="captcha-config"
                                                class="mt-3"
                                                style="display:<?= $config['captcha_type'] == '2' || $config['captcha_type'] == '3' ? 'block' : 'none' ?>;"
                                            >
                                                <div class="alert alert-info mb-0">
                                                    <div class="form-group">
                                                        <label
                                                            for="captcha-sitekey"><?= __('CONFIG__KEY_CAPTCHA_SITEKEY') ?></label>
                                                        <?= $this->Form->control('captcha_sitekey', [
                                                            'label' => false,
                                                            'id' => 'captcha-sitekey',
                                                            'type' => 'text',
                                                            'class' => 'form-control',
                                                            'value' => $config['captcha_sitekey'],
                                                        ]) ?>
                                                    </div>

                                                    <div class="form-group mb-0">
                                                        <label
                                                            for="captcha-secret"><?= __('CONFIG__KEY_CAPTCHA_SECRET') ?></label>
                                                        <?= $this->Form->control('captcha_secret', [
                                                            'label' => false,
                                                            'id' => 'captcha-secret',
                                                            'type' => 'text',
                                                            'class' => 'form-control',
                                                            'value' => $config['captcha_secret'],
                                                        ]) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                        <div class="border rounded p-3 mb-3">
                                            <h5 class="mb-3"><?= __('CONFIG__KEY_GOOGLE_ANALYTICS') ?></h5>

                                            <div class="form-group mb-0">
                                                <label
                                                    for="google-analytics"><?= __('CONFIG__KEY_GOOGLE_ANALYTICS') ?></label>
                                                <?= $this->Form->control('google_analytics', [
                                                    'label' => false,
                                                    'id' => 'google-analytics',
                                                    'type' => 'text',
                                                    'class' => 'form-control',
                                                    'value' => $config['google_analytics'],
                                                    'maxlength' => '15',
                                                ]) ?>
                                            </div>
                                        </div>

                                        <div class="border rounded p-3 mb-3">
                                            <h5 class="mb-3"><?= __('CONFIG__KEY_END_LAYOUT_COE') ?></h5>

                                            <div class="form-group mb-0">
                                                <label
                                                    for="end-layout-code"><?= __('CONFIG__KEY_END_LAYOUT_COE') ?></label>
                                                <?= $this->Form->textarea('end_layout_code', [
                                                    'label' => false,
                                                    'id' => 'end-layout-code',
                                                    'rows' => '6',
                                                    'class' => 'form-control',
                                                    'value' => $config['end_layout_code'],
                                                ]) ?>
                                            </div>
                                        </div>

                                        <div class="border rounded p-3 mb-0">
                                            <h5 class="mb-3"><?= __('CONFIG__KEY_EMAIL_SEND_TYPE') ?></h5>

                                            <div class="custom-control custom-radio">
                                                <input
                                                    type="radio"
                                                    id="email-send-type-normal"
                                                    name="email_send_type"
                                                    value="1"
                                                    class="custom-control-input"
                                                    <?= $config['email_send_type'] == '1' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="email-send-type-normal">
                                                    <?= __('GLOBAL__TYPE_NORMAL') ?>
                                                </label>
                                            </div>

                                            <div class="custom-control custom-radio">
                                                <input
                                                    type="radio"
                                                    id="email-send-type-smtp"
                                                    name="email_send_type"
                                                    value="2"
                                                    class="custom-control-input"
                                                    <?= $config['email_send_type'] == '2' ? 'checked' : '' ?>
                                                >
                                                <label class="custom-control-label" for="email-send-type-smtp">
                                                    <?= __('SMTP') ?>
                                                </label>
                                            </div>

                                            <div
                                                id="smtp-config"
                                                class="mt-3"
                                                style="display:<?= $config['email_send_type'] == '1' ? 'none' : 'block' ?>;"
                                            >
                                                <div class="alert alert-info mb-0">
                                                    <div class="form-group">
                                                        <label
                                                            for="smtp-host"><?= __('CONFIG__KEY_SMTP_HOST') ?></label>
                                                        <?= $this->Form->control('smtpHost', [
                                                            'label' => false,
                                                            'id' => 'smtp-host',
                                                            'type' => 'text',
                                                            'class' => 'form-control',
                                                            'value' => $config['smtpHost'],
                                                            'autocomplete' => 'off',
                                                        ]) ?>
                                                    </div>

                                                    <div class="form-group">
                                                        <label
                                                            for="smtp-username"><?= __('CONFIG__KEY_SMTP_USERNAME') ?></label>
                                                        <?= $this->Form->control('smtpUsername', [
                                                            'label' => false,
                                                            'id' => 'smtp-username',
                                                            'type' => 'text',
                                                            'class' => 'form-control',
                                                            'value' => $config['smtpUsername'],
                                                            'autocomplete' => 'off',
                                                        ]) ?>
                                                    </div>

                                                    <div class="form-group">
                                                        <label
                                                            for="smtp-port"><?= __('CONFIG__KEY_SMTP_PORT') ?></label>
                                                        <?= $this->Form->control('smtpPort', [
                                                            'label' => false,
                                                            'id' => 'smtp-port',
                                                            'type' => 'text',
                                                            'class' => 'form-control',
                                                            'value' => $config['smtpPort'],
                                                            'autocomplete' => 'off',
                                                        ]) ?>
                                                    </div>

                                                    <div class="form-group mb-0">
                                                        <label
                                                            for="smtp-password"><?= __('CONFIG__KEY_SMTP_PASSWORD') ?></label>
                                                        <?= $this->Form->control('smtpPassword', [
                                                            'label' => false,
                                                            'id' => 'smtp-password',
                                                            'type' => 'password',
                                                            'class' => 'form-control',
                                                            'value' => $config['smtpPassword'],
                                                            'autocomplete' => 'off',
                                                        ]) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>

                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>

            </div>
        </div>

        <?= $this->Form->end() ?>

    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const bindHiddenSwitch = (checkboxId, hiddenId) => {
            const checkbox = document.getElementById(checkboxId);
            const hidden = document.getElementById(hiddenId);
            if (!checkbox || !hidden) return;

            const sync = () => {
                hidden.value = checkbox.checked ? '1' : '0';
            };

            checkbox.addEventListener('change', sync);
            sync();
        };

        bindHiddenSwitch('passwords-salt-checkbox', 'passwords-salt');
        bindHiddenSwitch('check-uuid-checkbox', 'check-uuid');

        const toggleByRadioValue = (radioName, showValues, targetId) => {
            const target = document.getElementById(targetId);
            if (!target) return;

            const radios = Array.from(document.querySelectorAll(`input[name="${radioName}"]`));
            if (!radios.length) return;

            const refresh = () => {
                const selected = radios.find(r => r.checked);
                const value = selected ? selected.value : null;
                target.style.display = value && showValues.includes(value) ? 'block' : 'none';
            };

            radios.forEach(radio => radio.addEventListener('change', refresh));
            refresh();
        };

        toggleByRadioValue('confirm_mail_signup', ['1'], 'confirm-mail-signup-block');
        toggleByRadioValue('captcha_type', ['2', '3'], 'captcha-config');
        toggleByRadioValue('email_send_type', ['2'], 'smtp-config');
    });
</script>
