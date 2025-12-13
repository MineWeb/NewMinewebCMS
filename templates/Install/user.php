<div id="tabsleft" class="tabbable tabs-left">

    <ul class="nav nav-tabs nav-pills nav-stacked col-xs-6 col-sm-3" style="max-width:300px;">
        <li role="presentation">
            <a href="#tabsleft-tab2"
               data-bs-toggle="tab"
               title="<?= __('INSTALL__NO_SKIP') ?>">
                <?= __('INSTALL__STEP_1_TITLE') ?>
            </a>
        </li>

        <li role="presentation">
            <a href="#tabsleft-tab3"
               data-bs-toggle="tab"
               title="<?= __('INSTALL__NO_SKIP') ?>">
                <?= __('INSTALL__STEP_2_TITLE') ?>
            </a>
        </li>
    </ul>

    <div class="col-xs-12 col-sm-9">
        <div class="tab-content">

            <div class="tab-pane active" id="tabsleft-tab2">

                <h1><?= __('INSTALL__STEP_1_TITLE') ?></h1>
                <p><?= __('INSTALL__STEP_1_DESC') ?></p>

                <?= $this->Form->create(null, [
                    'id' => 'step3',
                    'data-user-url' => $this->Url->build(['_name' => 'install_user']),
                ]) ?>

                <div class="ajax-msg-step3"></div>

                <div class="form-group">
                    <label for="username"><?= __('USER__USERNAME') ?></label>

                    <input
                        id="username"
                        type="text"
                        name="username"
                        class="form-control"
                        placeholder="<?= __('USER__USERNAME_LABEL') ?>"
                        value="<?= !empty($admin_username) ? h($admin_username) : '' ?>"
                        autocomplete="off"
                    >
                </div>

                <div class="form-group">
                    <label for="password"><?= __('USER__PASSWORD') ?></label>

                    <input
                        id="password"
                        type="password"
                        name="password"
                        class="form-control"
                        placeholder="<?= __('USER__PASSWORD_LABEL') ?>"
                        value="<?= !empty($admin_password) ? '*********' : '' ?>"
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-group">
                    <label for="password-confirm"><?= __('USER__PASSWORD_CONFIRM') ?></label>

                    <input
                        id="password-confirm"
                        type="password"
                        name="password_confirmation"
                        class="form-control"
                        placeholder="<?= __('USER__PASSWORD_CONFIRM_LABEL') ?>"
                        value="<?= !empty($admin_password) ? '*********' : '' ?>"
                        autocomplete="new-password"
                    >
                </div>

                <div class="form-group">
                    <label for="email"><?= __('USER__EMAIL') ?></label>

                    <input
                        id="email"
                        type="email"
                        name="email"
                        class="form-control"
                        placeholder="<?= __('USER__EMAIL_LABEL') ?>"
                        value="<?= !empty($admin_email) ? h($admin_email) : '' ?>"
                        autocomplete="email"
                    >
                </div>

                <?php if (!empty($admin_username)) : ?>
                    <input type="hidden" name="step3" value="true">
                <?php endif; ?>

                <div id="input"></div>

                <ul class="pager wizard">
                    <li class="next" style="display:inline;">
                        <a id="tabsleft-link-step1" href="javascript:">
                            <?= __('GLOBAL__NEXT') ?>
                        </a>
                    </li>

                    <li class="next finish hidden" style="display:none;">
                        <a href="javascript:"><?= __('GLOBAL__END') ?></a>
                    </li>
                </ul>

                <?= $this->Form->end() ?>

            </div>

            <div class="tab-pane" id="tabsleft-tab3">

                <h1><?= __('INSTALL__STEP_2_TITLE') ?></h1>

                <div class="alert alert-success">
                    <?= __('INSTALL__STEP_2_DESC') ?>
                </div>

                <p>
                    <a href="<?= $this->Url->build('/') ?>"
                       class="btn btn-block btn-success">
                        <?= __('INSTALL__GO_TO_INDEX') ?>
                    </a>
                </p>

                <ul class="pager wizard">

                    <li class="previous disabled">
                        <a href="javascript:"><?= __('GLOBAL__PREVIOUS') ?></a>
                    </li>

                    <li class="next" style="display:inline;">
                        <a id="tabsleft-link-step2" href="javascript:">
                            <?= __('GLOBAL__NEXT') ?>
                        </a>
                    </li>

                </ul>

            </div>

            <div class="progress">
                <div class="progress-bar progress-bar-info progress-bar-striped active"
                     role="progressbar"
                     aria-valuenow="45"
                     aria-valuemin="0"
                     aria-valuemax="100"
                     style="width:45%">
                </div>
            </div>

        </div>
    </div>

</div>
