<?php

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('USER__EDIT_TITLE') ?></h3>
                </div>
                <div class="card-body">
                    <form action="<?= $this->Url->build(['_name' => 'admin_user_edit_ajax']) ?>"
                          method="post"
                          data-ajax="true"
                          data-redirect-url="<?= $this->Url->build(['_name' => 'admin_user_index']) ?>">

                        <input type="hidden" value="<?= $searchUser['id'] ?>" name="id">

                        <div class="form-group">
                            <label for="user-username"><?= __('USER__USERNAME') ?></label>
                            <input id="user-username"
                                   name="pseudo"
                                   class="form-control"
                                   value="<?= $searchUser['pseudo'] ?>"
                                   type="text"
                                   autocomplete="off">
                        </div>

                        <div class="form-group">
                            <label for="user-uuid">UUID</label>
                            <input id="user-uuid"
                                   name="uuid"
                                   class="form-control"
                                   value="<?= $searchUser['uuid'] ?>"
                                   type="text"
                                   autocomplete="off">
                        </div>

                        <?php if (!$Configuration->getKey('confirm_mail_signup')) { ?>
                            <div class="form-group">
                                <label for="user-email"><?= __('USER__EMAIL') ?></label>
                                <input id="user-email"
                                       name="email"
                                       class="form-control"
                                       value="<?= $searchUser['email'] ?>"
                                       type="email"
                                       autocomplete="off">
                            </div>
                        <?php } else { ?>
                            <div class="form-group">
                                <label for="user-email"><?= __('USER__EMAIL') ?></label>
                                <div class="input-group mb-3">
                                    <input id="user-email"
                                           value="<?= $searchUser['email'] ?>"
                                           type="email"
                                           name="email"
                                           class="form-control">
                                    <div class="input-group-append">
                                        <a class="btn btn-success<?= ($searchUser['confirmed']) ? ' disabled' : '' ?>"
                                           href="<?= ($searchUser['confirmed'])
                                               ? '#'
                                               : $this->Url->build(['_name' => 'admin_auth_confirm', $searchUser['id']]) ?>">
                                            <?= ($searchUser['confirmed'])
                                                ? __('USER__EMAIL_CONFIRMED')
                                                : __('USER__CONFIRM_EMAIL') ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php } ?>

                        <div class="form-group">
                            <label for="user-password"><?= __('USER__PASSWORD') ?></label>
                            <input id="user-password"
                                   name="password"
                                   class="form-control"
                                   type="password"
                                   autocomplete="new-password">
                        </div>

                        <div class="form-group">
                            <label for="user-rank"><?= __('USER__RANK') ?></label>
                            <select id="user-rank" class="form-control" name="rank">
                                <?php foreach ($optionsRanks as $key => $value) { ?>
                                    <option value="<?= $key ?>"<?= ($searchUser['rank'] == $key) ? ' selected' : '' ?>>
                                        <?= $value ?>
                                    </option>
                                <?php } ?>
                            </select>
                        </div>

                        <?php if ($EyPlugin->isInstalled('eywek.shop')) { ?>
                            <div class="form-group">
                                <label for="user-money"><?= __('USER__MONEY') ?></label>
                                <input id="user-money"
                                       name="money"
                                       class="form-control"
                                       value="<?= $searchUser['money'] ?>"
                                       type="text">
                            </div>
                        <?php } ?>

                        <div class="form-group">
                            <label for="user-ip">IP</label>
                            <input id="user-ip"
                                   class="form-control"
                                   value="<?= $searchUser['ip'] ?>"
                                   type="text"
                                   disabled>
                        </div>

                        <div class="form-group">
                            <label for="user-created"><?= __('USER__REGISTER_DATE') ?></label>
                            <input id="user-created"
                                   class="form-control"
                                   value="<?= $searchUser['created'] ?>"
                                   type="text"
                                   disabled>
                        </div>

                        <?= $Module->loadModules('admin_user_edit_form') ?>

                        <div class="float-right">
                            <a href="<?= $this->Url->build(['_name' => 'admin_user_index']) ?>"
                               class="btn btn-default">
                                <?= __('GLOBAL__CANCEL') ?>
                            </a>
                            <button class="btn btn-primary" type="submit">
                                <?= __('GLOBAL__SUBMIT') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('USER__HIS_HISTORIES') ?></h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered dataTable">
                        <thead>
                        <tr>
                            <th scope="col">ID</th>
                            <th scope="col"><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($searchUser['History'] as $key => $v) { ?>
                            <tr>
                                <td><?= $key ?></td>
                                <td><?= $v ?></td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <?= $Module->loadModules('admin_user_edit') ?>
</section>
