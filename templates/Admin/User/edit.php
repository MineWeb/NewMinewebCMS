<?php
declare(strict_types=1);

$backUrl = $this->Url->build(['_name' => 'admin_user_index']);

$confirmEnabled = (bool)$this->Config->get('confirm_mail_signup');
$confirmed = !empty($searchUser['confirmed']);
$confirmUrl = $confirmed
    ? '#'
    : $this->Url->build(['_name' => 'admin_auth_confirm', $searchUser['id']]);

$userId = (int)($searchUser['id'] ?? 0);
$username = (string)($searchUser['username'] ?? '');
$email = (string)($searchUser['email'] ?? '');
$uuid = (string)($searchUser['uuid'] ?? '');
$ip = (string)($searchUser['ip'] ?? '');
$createdAt = (string)($searchUser['created_at'] ?? '');
$rank = (string)($searchUser['rank'] ?? '');
$money = (string)($searchUser['money'] ?? '');
?>

<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-user-edit mr-2"></i><?= __('USER__EDIT_TITLE') ?>
                            </h3>

                            <a href="<?= h($backUrl) ?>" class="btn btn-default btn-sm mt-2 mt-sm-0">
                                <i class="fas fa-arrow-left mr-2"></i><?= __('GLOBAL__BACK') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">

                        <?= $this->Form->create(null, [
                            'url' => ['_name' => 'admin_user_edit_ajax'],
                            'method' => 'post',
                            'data-ajax' => 'true',
                            'data-redirect-url' => $backUrl,
                        ]) ?>

                        <input type="hidden" value="<?= $userId ?>" name="id">

                        <div class="p-3 p-md-4">

                            <div class="ajax-msg"></div>

                            <div class="alert alert-light border d-flex align-items-start mb-4">
                                <i class="fas fa-info-circle mt-1 mr-2"></i>
                                <div class="text-sm mb-0">
                                    <span class="font-weight-bold"><?= h($username) ?></span>
                                    <span class="text-muted ml-2">ID: <?= $userId ?></span>
                                    <?php if ($createdAt !== '') : ?>
                                        <span class="text-muted ml-2">| <?= __('USER__REGISTER_DATE') ?>: <?= h($createdAt) ?></span>
                                    <?php endif; ?>
                                    <?php if ($ip !== '') : ?>
                                        <span class="text-muted ml-2">| IP: <?= h($ip) ?></span>
                                    <?php endif; ?>
                                    <?php if ($confirmEnabled) : ?>
                                        <span class="ml-2 badge badge-<?= $confirmed ? 'success' : 'warning' ?>">
                                            <i class="fas <?= $confirmed ? 'fa-check' : 'fa-clock' ?> mr-1"></i>
                                            <?= $confirmed ? __('USER__EMAIL_CONFIRMED') : __('USER__CONFIRM_EMAIL') ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <div class="row">

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">

                                            <div class="form-group">
                                                <label for="user-username" class="mb-1"><?= __('USER__USERNAME') ?></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-user"></i></span>
                                                    </div>
                                                    <input
                                                        id="user-username"
                                                        name="username"
                                                        class="form-control"
                                                        value="<?= h($username) ?>"
                                                        type="text"
                                                        autocomplete="off"
                                                    >
                                                </div>
                                            </div>

                                            <div class="form-group mb-0">
                                                <label for="user-uuid" class="mb-1">UUID</label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-fingerprint"></i></span>
                                                    </div>
                                                    <input
                                                        id="user-uuid"
                                                        name="uuid"
                                                        class="form-control"
                                                        value="<?= h($uuid) ?>"
                                                        type="text"
                                                        autocomplete="off"
                                                    >
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">

                                            <div class="form-group">
                                                <label for="user-email" class="mb-1"><?= __('USER__EMAIL') ?></label>

                                                <?php if (!$confirmEnabled) : ?>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-envelope"></i></span>
                                                        </div>
                                                        <input
                                                            id="user-email"
                                                            name="email"
                                                            class="form-control"
                                                            value="<?= h($email) ?>"
                                                            type="email"
                                                            autocomplete="off"
                                                        >
                                                    </div>
                                                <?php else : ?>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="far fa-envelope"></i></span>
                                                        </div>
                                                        <input
                                                            id="user-email"
                                                            value="<?= h($email) ?>"
                                                            type="email"
                                                            name="email"
                                                            class="form-control"
                                                            autocomplete="off"
                                                        >
                                                        <div class="input-group-append">
                                                            <a class="btn btn-success<?= $confirmed ? ' disabled' : '' ?>"
                                                               href="<?= h($confirmUrl) ?>"
                                                                <?= $confirmed ? 'aria-disabled="true"' : '' ?>>
                                                                <i class="fas <?= $confirmed ? 'fa-check' : 'fa-envelope-open-text' ?> mr-1"></i>
                                                                <?= $confirmed ? __('USER__EMAIL_CONFIRMED') : __('USER__CONFIRM_EMAIL') ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div class="form-group mb-0">
                                                <label for="user-password" class="mb-1"><?= __('USER__PASSWORD') ?></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-key"></i></span>
                                                    </div>
                                                    <input
                                                        id="user-password"
                                                        name="password"
                                                        class="form-control"
                                                        type="password"
                                                        autocomplete="new-password"
                                                        placeholder="<?= __('USER__PASSWORD') ?>"
                                                    >
                                                </div>
                                                <small class="form-text text-muted mt-2 mb-0"><?= __('GLOBAL__LEAVE_EMPTY_TO_KEEP') ?></small>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card mb-3 mb-lg-0">
                                        <div class="card-body">

                                            <div class="form-group mb-0">
                                                <label for="user-rank" class="mb-1"><?= __('USER__RANK') ?></label>
                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text"><i class="fas fa-user-shield"></i></span>
                                                    </div>
                                                    <select id="user-rank" class="form-control" name="rank">
                                                        <?php foreach ($optionsRanks as $key => $value) : ?>
                                                            <option value="<?= h((string)$key) ?>"<?= $rank === (string)$key ? ' selected' : '' ?>>
                                                                <?= h((string)$value) ?>
                                                            </option>
                                                        <?php endforeach; ?>
                                                    </select>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                                <?php if ($this->Plugin->isInstalled('eywek.shop')) : ?>
                                    <div class="col-lg-6">
                                        <div class="card mb-0">
                                            <div class="card-body">

                                                <div class="form-group mb-0">
                                                    <label for="user-money" class="mb-1"><?= __('USER__MONEY') ?></label>
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text"><i class="fas fa-coins"></i></span>
                                                        </div>
                                                        <input
                                                            id="user-money"
                                                            name="money"
                                                            class="form-control"
                                                            value="<?= h($money) ?>"
                                                            type="text"
                                                        >
                                                    </div>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <?= $this->Module->load('admin_user_edit_form') ?>

                            </div>

                        </div>

                        <div class="card-footer bg-white">
                            <div class="d-flex justify-content-end">
                                <a href="<?= h($backUrl) ?>" class="btn btn-default mr-2">
                                    <i class="fas fa-times mr-1"></i><?= __('GLOBAL__CANCEL') ?>
                                </a>
                                <button class="btn btn-primary" type="submit">
                                    <i class="fas fa-save mr-2"></i><?= __('GLOBAL__SUBMIT') ?>
                                </button>
                            </div>
                        </div>

                        <?= $this->Form->end() ?>

                    </div>
                </div>

                <div class="card card-outline card-secondary mt-3">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-history mr-2"></i><?= __('USER__HIS_HISTORIES') ?>
                        </h3>
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
                            <?php foreach ($searchUser['History'] as $key => $v) : ?>
                                <tr>
                                    <td><?= h((string)$key) ?></td>
                                    <td><?= h((string)$v) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <?= $this->Module->load('admin_user_edit') ?>

            </div>
        </div>

    </div>
</section>
