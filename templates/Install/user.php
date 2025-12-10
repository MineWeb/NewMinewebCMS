<?php

use Cake\Routing\Router;

?>
<div id="tabsleft" class="tabbable tabs-left">
    <ul class="nav nav-tabs nav-pills nav-stacked col-xs-6 col-sm-3" style="max-width: 300px;">
        <li role="presentation" class="">
            <a href="#tabsleft-tab2" data-toggle="tab"
               title="<?= __('INSTALL__NO_SKIP') ?>"><?= __('INSTALL__STEP_1_TITLE') ?></a>
        </li>
        <li role="presentation" class="">
            <a href="#tabsleft-tab3" data-toggle="tab"
               title="<?= __('INSTALL__NO_SKIP') ?>"><?= __('INSTALL__STEP_2_TITLE') ?></a>
        </li>
    </ul>

    <div class="col-xs-12 col-sm-9">
        <div class="tab-content">
            <div class="tab-pane active" id="tabsleft-tab2">
                <h1><?= __('INSTALL__STEP_1_TITLE') ?></h1>
                <p><?= __('INSTALL__STEP_1_DESC') ?></p>

                <form id="step3" data-user-url="<?= Router::url(['controller' => 'Install', 'action' => 'user']) ?>">
                    <div class="ajax-msg-step3"></div>
                    <div class="form-group">
                        <label><?= __('USER__USERNAME') ?></label>
                        <input type="text" class="form-control" name="pseudo"<?php if (!empty($admin_pseudo)) {
                            echo ' value="' . $admin_pseudo . '"';
                        } ?> placeholder="<?= __('USER__USERNAME_LABEL') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= __('USER__PASSWORD') ?></label>
                        <input type="password" class="form-control" name="password"<?php if (!empty($admin_password)) {
                            echo ' value="*********"';
                        } ?> placeholder="<?= __('USER__PASSWORD_LABEL') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= __('USER__PASSWORD_CONFIRM') ?></label>
                        <input type="password" class="form-control"
                               name="password_confirmation"<?php if (!empty($admin_password)) {
                            echo ' value="*********"';
                        } ?> placeholder="<?= __('USER__PASSWORD_CONFIRM_LABEL') ?>">
                    </div>
                    <div class="form-group">
                        <label><?= __('USER__EMAIL') ?></label>
                        <input type="email" class="form-control" name="email"<?php if (!empty($admin_email)) {
                            echo ' value="' . $admin_email . '"';
                        } ?> placeholder="<?= __('USER__EMAIL_LABEL') ?>">
                    </div>
                    <?php if (!empty($admin_pseudo)) { ?>
                        <input type="hidden" name="step3" value="true">
                    <?php } ?>
                    <div id="input"></div>
                    <li class="next finish hidden" style="display: none;">
                        <a href="javascript:"><?= __('GLOBAL__END') ?></a>
                    </li>
                    <ul class="pager wizard">
                        <li class="next" style="display: inline;">
                            <a id="tabsleft-link" href="javascript:"><?= __('GLOBAL__NEXT') ?></a>
                        </li>
                        <li class="next finish hidden" style="display: none;">
                            <a href="javascript:"><?= __('GLOBAL__END') ?></a>
                        </li>
                    </ul>
                </form>

            </div>

            <div class="tab-pane" id="tabsleft-tab3">
                <h1><?= __('INSTALL__STEP_2_TITLE') ?></h1>
                <div class="alert alert-success"><?= __('INSTALL__STEP_2_DESC') ?></div>

                <p>
                    <a href="<?= Router::url(['controller' => 'install', 'action' => 'end']) ?>"
                       class="btn btn-block btn-success"><?= __('INSTALL__GO_TO_INDEX') ?></a>
                <ul class="pager wizard">
                    <li class="previous disabled">
                        <a href="javascript:"><?= __('GLOBAL__PREVIOUS') ?></a>
                    </li>
                    <li class="next" style="display: inline;">
                        <a id="tabsleft-link" href="javascript:"><?= __('GLOBAL__NEXT') ?></a>
                    </li>
                </ul>
                </p>
            </div>

            <div class="progress">
                <div class="progress-bar progress-bar-info progress-bar-striped active" role="progressbar"
                     aria-valuenow="45" aria-valuemin="0" aria-valuemax="100" style="width: 45%"></div>
            </div>
        </div>
    </div>
</div>
