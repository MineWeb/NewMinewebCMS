<section class="content">
    <div class="container-fluid">

        <div class="row mb-3">
            <div class="col-12">
                <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-md-between">
                    <div>
                        <h1 class="m-0"><?= __('PERMISSIONS__LABEL') ?></h1>
                        <small class="text-muted"><?= __('ROLES__MANAGE_DESC') ?></small>
                    </div>

                    <button class="btn btn-success mt-2 mt-md-0"
                            type="button"
                            data-toggle="modal"
                            data-target="#addRoleModal"
                            aria-controls="addRoleModal"
                            aria-expanded="false">
                        <i class="fas fa-plus mr-1"></i>
                        <?= __('ROLES__ADD') ?>
                    </button>
                </div>
            </div>
        </div>

        <?php if (!empty($messages)) : ?>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle mr-1"></i>
                        <?= h($messages) ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                </div>
            </div>
        <?php endif ?>

        <?= $this->Form->create(null, [
            'method' => 'post',
            'url' => ['_name' => 'admin_roles_index'],
        ]) ?>

        <div class="card card-outline card-primary">
            <div class="card-header">
                <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-lg-between">
                    <h3 class="card-title">
                        <i class="fas fa-user-shield mr-1"></i>
                        <?= __('PERMISSIONS__LABEL') ?>
                    </h3>

                    <div class="input-group input-group-sm mt-2 mt-lg-0" style="width:320px">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input id="perm-search"
                               type="text"
                               class="form-control"
                               placeholder="<?= h(__('GLOBAL__SEARCH')) ?>">
                    </div>
                </div>
            </div>

            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered table-hover mb-0" id="permissions-table">
                        <thead class="thead-light">
                        <tr>
                            <th style="min-width:260px"><?= __('PERMISSIONS__LABEL') ?></th>

                            <?php foreach ($roles as $role) : ?>
                                <th class="text-center" style="min-width:150px">
                                    <strong><?= h($role->display_name) ?></strong><br>

                                    <?php if ((string)$role->slug === 'admin') : ?>
                                        <span class="badge badge-danger"><?= __('ROLES__ADMIN') ?></span>
                                    <?php elseif ($role->is_default) : ?>
                                        <span class="badge badge-primary"><?= __('ROLES__DEFAULT') ?></span>
                                    <?php elseif ($role->is_system) : ?>
                                        <span class="badge badge-secondary"><?= __('ROLES__SYSTEM') ?></span>
                                    <?php endif ?>
                                </th>
                            <?php endforeach ?>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($matrix as $permission => $byRole) : ?>
                            <tr data-perm-row="1"
                                data-perm-name="<?= h(strtolower((string)$permission)) ?>"
                                data-perm-label="<?= h(strtolower((string)__('PERMISSIONS__' . $permission))) ?>">

                                <td>
                                    <div class="font-weight-semibold"><?= __('PERMISSIONS__' . $permission) ?></div>
                                    <small class="text-muted"><?= h($permission) ?></small>
                                </td>

                                <?php foreach ($roles as $role) : ?>
                                    <?php
                                    $rid = (int)$role->id;
                                    $isAdmin = ((string)$role->slug === 'admin');
                                    $checked = $isAdmin || !empty($byRole[$rid]);
                                    $inputId = 'perm-' . $permission . '-' . $rid;
                                    ?>
                                    <td class="text-center align-middle">
                                        <div class="icheck-primary d-inline">
                                            <input type="checkbox"
                                                   id="<?= h($inputId) ?>"
                                                   name="<?= h($permission) ?>-<?= $rid ?>"
                                                <?= $checked ? 'checked="checked"' : '' ?>
                                                <?= $isAdmin ? 'disabled="disabled"' : '' ?>>
                                            <label for="<?= h($inputId) ?>"></label>
                                        </div>
                                    </td>
                                <?php endforeach ?>
                            </tr>
                        <?php endforeach ?>

                        <tr class="bg-light">
                            <td class="text-muted"><?= __('ROLES__ACTIONS') ?></td>

                            <?php foreach ($roles as $role) : ?>
                                <td class="text-center">
                                    <?php if (!$role->is_system && !$role->is_default && (string)$role->slug !== 'admin') : ?>
                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger js-delete-role"
                                                data-id="<?= (int)$role->id ?>">
                                            <?= __('GLOBAL__DELETE') ?>
                                        </button>
                                    <?php else : ?>
                                        <span class="text-muted">-</span>
                                    <?php endif ?>
                                </td>
                            <?php endforeach ?>
                        </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card-footer d-flex justify-content-between align-items-center">
                <small class="text-muted">
                    <i class="fas fa-info-circle mr-1"></i>
                    <?= __('ROLES__SAVE_NOTICE') ?>
                </small>

                <button class="btn btn-primary" type="submit">
                    <i class="fas fa-save mr-1"></i>
                    <?= __('GLOBAL__SUBMIT') ?>
                </button>
            </div>
        </div>

        <?= $this->Form->end() ?>
    </div>
</section>

<div class="modal fade" id="addRoleModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-user-plus mr-1"></i>
                    <?= __('ROLES__ADD') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">
                <?= $this->Form->create(null, [
                    'url' => ['_name' => 'admin_roles_add'],
                    'method' => 'post',
                    'data-ajax' => 'true',
                    'data-redirect-url' => $this->Url->build(['_name' => 'admin_roles_index']),
                ]) ?>

                <div class="ajax-msg mb-3"></div>

                <div class="form-group">
                    <label for="role-name" class="mb-1"><?= __('GLOBAL__NAME') ?></label>
                    <input id="role-name"
                           type="text"
                           class="form-control"
                           name="name"
                           required
                           autocomplete="off">
                    <small class="text-muted"><?= __('ROLES__NAME_HELP') ?></small>
                </div>

                <div class="d-flex justify-content-end">
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-check mr-1"></i>
                        <?= __('GLOBAL__SUBMIT') ?>
                    </button>
                </div>

                <?= $this->Form->end() ?>
            </div>

        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const csrfInput = document.querySelector('input[name="_csrfToken"]');
        const csrfToken = csrfInput ? csrfInput.value : '';

        const search = document.getElementById('perm-search');
        const table = document.getElementById('permissions-table');

        function normalize(v) {
            return String(v || '').toLowerCase().trim();
        }

        function applySearch() {
            if (!table) return;

            const q = normalize(search ? search.value : '');
            const rows = table.querySelectorAll('tbody tr[data-perm-row="1"]');

            rows.forEach((tr) => {
                const key = normalize(tr.getAttribute('data-perm-name'));
                const label = normalize(tr.getAttribute('data-perm-label'));
                const ok = !q || key.includes(q) || label.includes(q);
                tr.style.display = ok ? '' : 'none';
            });
        }

        if (search) {
            search.addEventListener('input', applySearch);
        }

        document.addEventListener('click', async (e) => {
            const btn = e.target.closest('.js-delete-role');
            if (!btn) return;

            const roleId = btn.getAttribute('data-id');
            if (!roleId) return;

            if (!confirm('<?= addslashes((string)__('GLOBAL__CONFIRM_DELETE')) ?>')) {
                return;
            }

            btn.disabled = true;

            try {
                const url = '<?= $this->Url->build(['_name' => 'admin_roles_delete', 0]) ?>'.replace('/0', '/' + roleId);

                const res = await fetch(url, {
                    method: 'POST',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'
                    },
                    body: '_csrfToken=' + encodeURIComponent(csrfToken)
                });

                const json = await res.json().catch(() => null);

                if (!json || json.status !== true) {
                    alert((json && json.messages) ? json.messages : '<?= addslashes(__('ERROR__INTERNAL_ERROR')) ?>');
                    btn.disabled = false;
                    return;
                }

                window.location.reload();
            } catch (err) {
                alert('<?= addslashes(__('ERROR__INTERNAL_ERROR')) ?>');
                btn.disabled = false;
            }
        });
    });
</script>
