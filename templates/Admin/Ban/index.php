<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                            <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-user-slash mr-2"></i><?= __('BAN__HOME') ?>
                                </h3>

                                <span class="badge badge-light border">
                                    <i class="fas fa-list mr-1"></i><?= is_countable($banned_users) ? (int)count($banned_users) : 0 ?> <?= __('TABLE__ITEMS') ?>
                                </span>
                            </div>

                            <a class="btn btn-primary btn-sm"
                               href="<?= $this->Url->build(['_name' => 'admin_ban_add']) ?>">
                                <i class="fas fa-plus mr-2"></i><?= __('BAN__ADD') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <?php if (!empty($banned_users) && count($banned_users) > 0) { ?>
                                <div class="d-flex align-items-center justify-content-between flex-wrap mb-3" style="gap: 10px;">
                                    <div class="input-group input-group-sm" style="max-width: 360px;">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white">
                                                <i class="fas fa-search text-muted"></i>
                                            </span>
                                        </div>
                                        <input id="ban-search"
                                               type="text"
                                               class="form-control"
                                               placeholder="<?= h((string)__('TABLE__SEARCH_PLACEHOLDER')) ?>"
                                               autocomplete="off">
                                        <div class="input-group-append">
                                            <button class="btn btn-default" type="button" id="ban-search-clear" title="Clear">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <span id="ban-count" class="badge badge-light border"></span>
                                </div>
                            <?php } ?>

                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0" id="ban-table">
                                    <thead>
                                    <tr>
                                        <th class="text-muted text-uppercase text-sm" style="letter-spacing: .02em;">
                                            <?= __('USER__USERNAME') ?>
                                        </th>
                                        <th class="text-muted text-uppercase text-sm" style="letter-spacing: .02em;">
                                            <?= __('BAN__REASON') ?>
                                        </th>
                                        <th class="text-muted text-uppercase text-sm" style="letter-spacing: .02em;">
                                            <?= __('BAN__IS_BAN_IP') ?>
                                        </th>
                                        <th class="text-muted text-uppercase text-sm text-right" style="letter-spacing: .02em; width: 1%; white-space: nowrap;">
                                            <?= __('GLOBAL__ACTIONS') ?>
                                        </th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php if (empty($banned_users) || count($banned_users) === 0) { ?>
                                        <tr>
                                            <td colspan="4" class="text-center text-muted py-4">
                                                <i class="far fa-folder-open mr-2"></i><?= __('TABLE__NO_RESULT') ?>
                                            </td>
                                        </tr>
                                    <?php } else { ?>
                                        <?php foreach ($banned_users as $v) { ?>
                                            <?php
                                            $username = (string)($v['username'] ?? '');
                                            $reason = (string)($v['reason'] ?? '');
                                            $ip = (string)($v['ip'] ?? '');
                                            ?>
                                            <tr>
                                                <td class="align-middle font-weight-bold">
                                                    <?= h($username) ?>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if ($reason !== '') { ?>
                                                        <span class="text-muted"><?= h($reason) ?></span>
                                                    <?php } else { ?>
                                                        <span class="text-muted text-sm"><?= __('TABLE__NO_RESULT') ?></span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle">
                                                    <?php if ($ip !== '') { ?>
                                                        <span class="badge badge-warning">
                                                            <i class="fas fa-network-wired mr-1"></i><?= h($ip) ?>
                                                        </span>
                                                    <?php } else { ?>
                                                        <span class="badge badge-light border"><?= __('BAN__NOT_BAN_IP') ?></span>
                                                    <?php } ?>
                                                </td>

                                                <td class="align-middle text-right text-nowrap">
                                                    <a href="#"
                                                       onclick="confirmDel('<?= $this->Url->build(['_name' => 'admin_ban_unban', (int)$v['id']]) ?>'); return false;"
                                                       class="btn btn-danger btn-sm">
                                                        <i class="fas fa-unlock mr-1"></i><?= __('BAN__UNBAN') ?>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php } ?>
                                    <?php } ?>
                                    </tbody>
                                </table>
                            </div>

                            <?php if (!empty($banned_users) && count($banned_users) > 0) { ?>
                                <div id="ban-empty" class="p-3 text-center text-muted border rounded mt-3" style="display:none;">
                                    <i class="fas fa-info-circle mr-1"></i><?= __('TABLE__NO_RESULT') ?>
                                </div>
                            <?php } ?>

                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<?php if (!empty($banned_users) && count($banned_users) > 0) { ?>
    <script>
        (function () {
            const input = document.getElementById("ban-search");
            const clearBtn = document.getElementById("ban-search-clear");
            const table = document.getElementById("ban-table");
            const empty = document.getElementById("ban-empty");
            const count = document.getElementById("ban-count");
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
<?php } ?>
