<?php

$searchId = 'cmd-search';
$tableId = 'cmd-table';
$emptyId = 'cmd-empty';

$serverNameById = [];
foreach ($search_server as $srv) {
    $serverNameById[(int)$srv['id']] = (string)$srv['name'];
}

$allowedServers = [];
foreach ($search_server as $srv) {
    $type = (int)($srv['type'] ?? 0);
    if ($type === 0 || $type === 2) {
        $allowedServers[] = $srv;
    }
}
?>
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-terminal mr-2"></i><?= __('SERVER__CMD_TITLE') ?>
                            </h3>

                            <div class="d-flex align-items-center" style="gap: 10px;">
                                <div class="input-group input-group-sm" style="min-width: 280px;">
                                    <input id="<?= h($searchId) ?>"
                                           type="text"
                                           class="form-control"
                                           placeholder="<?= h((string)__('TABLE__SEARCH_PLACEHOLDER')) ?>"
                                           autocomplete="off">
                                    <div class="input-group-append">
                                        <span class="btn btn-default">
                                            <i class="fas fa-search"></i>
                                        </span>
                                    </div>
                                </div>

                                <span class="badge badge-light border" id="cmd-count"></span>

                                <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#executeCommand">
                                    <i class="fas fa-plus"></i>
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table id="<?= h($tableId) ?>" class="table table-hover table-striped mb-0">
                                <thead>
                                <tr>
                                    <th style="width: 22%;"><?= __('SERVER__CMD_NAME') ?></th>
                                    <th><?= __('SERVER__COMMAND') ?></th>
                                    <th style="width: 22%;"><?= __('SERVER__TITLE') ?></th>
                                    <th class="text-right" style="width: 220px;"><?= __('GLOBAL__ACTIONS') ?></th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($search_cmd as $c) : ?>
                                    <?php
                                    $cmdName = (string)($c['name'] ?? '');
                                    $cmd = (string)($c['cmd'] ?? '');
                                    $serverId = (int)($c['server_id'] ?? 0);
                                    $serverName = $serverNameById[$serverId] ?? '';
                                    $cmdId = (int)($c['id'] ?? 0);
                                    ?>
                                    <tr>
                                        <td class="font-weight-bold"><?= h($cmdName) ?></td>
                                        <td>
                                            <span class="badge badge-light border mr-2"><i class="fas fa-chevron-right"></i></span>
                                            <span class="text-monospace"><?= h($cmd) ?></span>
                                        </td>
                                        <td>
                                            <?php if ($serverName !== '') : ?>
                                                <span class="badge badge-secondary">
                                                    <i class="fas fa-server mr-1"></i><?= h($serverName) ?>
                                                </span>
                                            <?php else : ?>
                                                <span class="text-muted"><?= __('SERVER__UNKNOWN_SERVER') ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-right">
                                            <?= $this->Form->create(
                                                null,
                                                [
                                                    'url' => ['_name' => 'admin_server_execute_cmd'],
                                                    'method' => 'post',
                                                    'data-ajax' => 'true',
                                                    'data-success-msg' => 'true',
                                                    'data-redirect-url' => $this->Url->build(['_name' => 'admin_server_cmd']),
                                                    'class' => 'd-inline-block mr-1',
                                                ]
                                            ) ?>

                                            <input type="hidden" name="cmd" value="<?= h($cmd) ?>">
                                            <input type="hidden" name="server_id" value="<?= (int)$serverId ?>">

                                            <button class="btn btn-success btn-sm" type="submit" title="<?= h((string)__('SERVER__CMD_EXECUTE')) ?>">
                                                <i class="fas fa-play"></i>
                                            </button>

                                            <?= $this->Form->end() ?>

                                            <button type="button"
                                                    class="btn btn-outline-danger btn-sm"
                                                    data-confirm-del="1"
                                                    data-url="<?= h($this->Url->build(['_name' => 'admin_server_delete_cmd', $cmdId])) ?>"
                                                    title="<?= h((string)__('GLOBAL__DELETE')) ?>">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <div id="<?= h($emptyId) ?>" class="p-3 text-center text-muted" style="display:none;">
                            <i class="fas fa-info-circle mr-1"></i><?= __('TABLE__NO_RESULT') ?>
                        </div>
                    </div>

                    <div class="card-footer d-flex align-items-center justify-content-between">
                        <div class="text-muted text-sm">
                            <i class="fas fa-info-circle mr-1"></i><?= __('SERVER__CMD_HINT') ?>
                        </div>

                        <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#executeCommand">
                            <i class="fas fa-plus mr-1"></i><?= __('SERVER__CMD_ADD') ?>
                        </button>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="executeCommand" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="fas fa-plus-circle mr-2"></i><?= __('SERVER__CMD_ADD') ?>
                </h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= __('GLOBAL__CANCEL') ?>">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>

            <div class="modal-body">

                <?= $this->Form->create(
                    null,
                    [
                        'url' => ['_name' => 'admin_server_add_cmd'],
                        'method' => 'post',
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_server_cmd']),
                    ]
                ) ?>

                <div class="ajax-msg" aria-live="polite"></div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cmd-name-1"><?= __('GLOBAL__NAME') ?></label>
                            <input id="cmd-name-1" name="name" class="form-control" type="text" autocomplete="off">
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="form-group">
                            <label for="cmd-server-1"><?= __('SERVER__TITLE') ?></label>
                            <select id="cmd-server-1" class="form-control" name="server_id">
                                <?php foreach ($allowedServers as $srv) : ?>
                                    <option value="<?= (int)$srv['id'] ?>"><?= h((string)$srv['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label for="cmd-command-1"><?= __('SERVER__COMMAND') ?></label>
                    <input id="cmd-command-1"
                           name="cmd"
                           class="form-control"
                           type="text"
                           autocomplete="off"
                           placeholder="<?= h((string)__('SERVER__CMD_PLACEHOLDER')) ?>">
                    <small class="form-text text-muted">
                        <?= __('SERVER__CMD_HELP') ?>
                    </small>
                </div>

                <div class="d-flex align-items-center justify-content-end" style="gap: 8px;">
                    <button type="button" class="btn btn-default" data-dismiss="modal">
                        <i class="fas fa-times mr-1"></i><?= __('GLOBAL__CANCEL') ?>
                    </button>
                    <button class="btn btn-primary" type="submit">
                        <i class="fas fa-check mr-1"></i><?= __('GLOBAL__SUBMIT') ?>
                    </button>
                </div>

                <?= $this->Form->end() ?>

            </div>
        </div>
    </div>
</div>

<script>
    (function () {
        const input = document.getElementById("<?= h($searchId) ?>");
        const table = document.getElementById("<?= h($tableId) ?>");
        const empty = document.getElementById("<?= h($emptyId) ?>");
        const count = document.getElementById("cmd-count");

        function norm(v) {
            return String(v || "").toLowerCase().trim();
        }

        function updateCount(shown, total) {
            if (!count) return;
            count.textContent = String(shown) + " / " + String(total) + " " + String("<?= h((string)__('TABLE__ITEMS')) ?>");
        }

        function filter() {
            if (!input || !table) return;

            const q = norm(input.value);
            const rows = table.querySelectorAll("tbody tr");
            let shown = 0;

            rows.forEach((tr) => {
                const txt = norm(tr.innerText);
                const ok = q === "" || txt.includes(q);
                tr.style.display = ok ? "" : "none";
                if (ok) shown += 1;
            });

            if (empty) {
                empty.style.display = shown === 0 ? "" : "none";
            }

            updateCount(shown, rows.length);
        }

        function bindDeleteButtons() {
            document.querySelectorAll('button[data-confirm-del="1"]').forEach((btn) => {
                if (btn.getAttribute("data-bound") === "1") return;
                btn.setAttribute("data-bound", "1");

                btn.addEventListener("click", () => {
                    const url = btn.getAttribute("data-url") || "";
                    if (!url) return;

                    if (typeof window.confirmDel === "function") {
                        window.confirmDel(url);
                        return;
                    }

                    if (window.confirm(String("<?= h((string)__('GLOBAL__CONFIRM_DELETE')) ?>"))) {
                        window.location.href = url;
                    }
                });
            });
        }

        if (input) input.addEventListener("input", filter);

        bindDeleteButtons();
        filter();
    })();
</script>
