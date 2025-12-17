<?php

$searchId = 'whitelist-search';
$tableId = 'whitelist-table';
$emptyId = 'whitelist-empty';
$countId = 'whitelist-count';

$pass = (array)$this->getRequest()->getParam('pass', []);
$currentServerId = isset($pass[0]) ? (int)$pass[0] : 0;

$serversCount = $currentServerId === 0 ? count($servers) : 1;

$selfBaseUrl = $this->Url->build(['_name' => 'admin_server_whitelist']);
$backUrl = $this->Url->build(['_name' => 'admin_server_link']);

$serverName = null;
if ($currentServerId !== 0) {
    foreach ((array)$servers as $s) {
        if ((int)($s['id'] ?? 0) === $currentServerId) {
            $serverName = (string)($s['name'] ?? '');
            break;
        }
    }
}
?>
<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-info">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                            <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-user-check mr-2"></i><?= __('SERVER__WHITELIST') ?>
                                </h3>

                                <span class="badge badge-secondary">
                                    <i class="fas fa-server mr-1"></i><?= $serversCount > 1 ? __('SERVER__COUNT_PLURAL', ['COUNT' => $serversCount]) : __('SERVER__COUNT_SINGLE', ['COUNT' => $serversCount]) ?>
                                </span>

                                <?php if ($currentServerId === 0): ?>
                                    <span class="badge badge-info">
                                        <i class="fas fa-layer-group mr-1"></i><?= __('SERVER__ALL_SERVERS') ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">
                                        <i class="fas fa-server mr-1"></i><?= h($serverName !== null && $serverName !== '' ? $serverName : ('#' . (string)$currentServerId)) ?>
                                    </span>
                                <?php endif; ?>
                            </div>

                            <a class="btn btn-outline-primary btn-sm" href="<?= h($backUrl) ?>">
                                <i class="fas fa-link mr-1"></i><?= __('SERVER__LINK_TITLE') ?>
                            </a>
                        </div>

                        <div class="mt-3">
                            <div class="d-flex align-items-center flex-wrap" style="gap: 6px;">
                                <a class="btn btn-sm <?= $currentServerId === 0 ? 'btn-info' : 'btn-outline-info' ?>"
                                   href="<?= h($selfBaseUrl) ?>">
                                    <i class="fas fa-layer-group mr-1"></i><?= __('SERVER__ALL_SERVERS') ?>
                                </a>

                                <?php foreach ($servers as $value): ?>
                                    <?php
                                    $sid = (int)$value['id'];
                                    $active = $sid === $currentServerId;
                                    $href = $active
                                        ? $selfBaseUrl
                                        : $this->Url->build(['_name' => 'admin_server_whitelist', $sid]);
                                    ?>
                                    <a class="btn btn-sm <?= $active ? 'btn-info' : 'btn-outline-info' ?>"
                                       href="<?= h($href) ?>">
                                        <i class="fas fa-server mr-1"></i><?= h((string)$value['name']) ?>
                                    </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">

                        <?php if ($list !== 'NEED_SERVER_ON'): ?>

                            <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap: 10px;">
                                <div class="input-group input-group-sm" style="max-width: 360px;">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text bg-white">
                                            <i class="fas fa-search text-muted"></i>
                                        </span>
                                    </div>
                                    <input id="<?= h($searchId) ?>"
                                           type="text"
                                           class="form-control"
                                           placeholder="<?= h((string)__('TABLE__SEARCH_PLACEHOLDER')) ?>"
                                           autocomplete="off"
                                           aria-label="<?= h((string)__('TABLE__SEARCH_PLACEHOLDER')) ?>">
                                    <div class="input-group-append">
                                        <button class="btn btn-default" type="button" id="<?= h($searchId) ?>-clear" title="Clear">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                </div>

                                <span id="<?= h($countId) ?>" class="badge badge-light border"></span>
                            </div>

                            <div class="table-responsive">
                                <table id="<?= h($tableId) ?>" class="table table-hover table-striped mb-0">
                                    <thead>
                                    <tr>
                                        <th class="text-muted text-uppercase text-sm" style="letter-spacing: .02em;">
                                            <?= __('USER__USERNAME') ?>
                                        </th>
                                    </tr>
                                    </thead>
                                    <tbody>
                                    <?php foreach ((array)$list as $v): ?>
                                        <tr>
                                            <td class="font-weight-bold"><?= h((string)$v) ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>

                            <div id="<?= h($emptyId) ?>" class="p-3 text-center text-muted border rounded mt-3" style="display:none;">
                                <i class="fas fa-info-circle mr-1"></i><?= __('TABLE__NO_RESULT') ?>
                            </div>

                            <script>
                                (function () {
                                    const input = document.getElementById("<?= h($searchId) ?>");
                                    const clearBtn = document.getElementById("<?= h($searchId) ?>-clear");
                                    const table = document.getElementById("<?= h($tableId) ?>");
                                    const empty = document.getElementById("<?= h($emptyId) ?>");
                                    const count = document.getElementById("<?= h($countId) ?>");
                                    if (!table) return;

                                    function norm(v) {
                                        return String(v || "").toLowerCase().trim();
                                    }

                                    function updateCount(shown, total) {
                                        if (!count) return;
                                        count.textContent = String(shown) + " / " + String(total) + " " + String("<?= h((string)__('TABLE__ITEMS')) ?>");
                                    }

                                    function filter() {
                                        const q = input ? norm(input.value) : "";
                                        const rows = table.querySelectorAll("tbody tr");
                                        let shown = 0;

                                        rows.forEach((tr) => {
                                            const txt = norm(tr.innerText);
                                            const ok = q === "" || txt.includes(q);
                                            tr.style.display = ok ? "" : "none";
                                            if (ok) shown += 1;
                                        });

                                        if (empty) empty.style.display = shown === 0 ? "" : "none";
                                        updateCount(shown, rows.length);
                                    }

                                    if (input) input.addEventListener("input", filter);

                                    if (clearBtn) {
                                        clearBtn.addEventListener("click", function () {
                                            if (!input) return;
                                            input.value = "";
                                            input.focus();
                                            filter();
                                        });
                                    }

                                    filter();
                                })();
                            </script>

                        <?php else: ?>

                            <div class="alert alert-danger mb-0">
                                <i class="fas fa-exclamation-triangle mr-2"></i><?= __('SERVER__MUST_BE_ON') ?>
                            </div>

                        <?php endif; ?>

                    </div>
                </div>

            </div>
        </div>
    </div>
</section>
