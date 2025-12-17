<?php
declare(strict_types=1);

$urlSwitchState = $this->Url->build(['_name' => 'admin_server_switch_state']);
$urlSwitchCacheState = $this->Url->build(['_name' => 'admin_server_switch_cache_state']);
$urlLinkAjax = $this->Url->build(['_name' => 'admin_server_link_ajax']);
?>
<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12 col-lg-6">
                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                            <h3 class="card-title mb-0">
                                <i class="fas fa-cogs mr-2"></i><?= __('SERVER__CONFIG_LABEL') ?>
                            </h3>

                            <div class="d-flex flex-wrap" style="gap: 8px;">
                                <a href="<?= h($urlSwitchState) ?>"
                                   class="btn btn-sm btn-<?= $isEnabled ? 'danger' : 'success' ?>">
                                    <i class="fas fa-power-off mr-2"></i>
                                    <?= $isEnabled ? __('SERVER__DISABLE_SERVER') : __('SERVER__ENABLE_SERVER') ?>
                                </a>

                                <a href="<?= h($urlSwitchCacheState) ?>"
                                   class="btn btn-sm btn-<?= $isCacheEnabled ? 'danger' : 'success' ?>">
                                    <i class="fas fa-bolt mr-2"></i>
                                    <?= $isCacheEnabled ? __('SERVER__DISABLE_CACHE') : __('SERVER__ENABLE_CACHE') ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <div class="card-body">
                        <?= $this->Form->create(null, [
                            'url' => ['_name' => 'admin_server_config'],
                            'method' => 'post',
                            'data-ajax' => 'true',
                        ]) ?>

                        <div class="ajax-msg"></div>

                        <div class="form-group mb-3">
                            <label for="server-timeout" class="mb-1"><?= __('SERVER__TIMEOUT') ?></label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text bg-white">
                                        <i class="far fa-clock text-muted"></i>
                                    </span>
                                </div>
                                <input
                                    type="text"
                                    id="server-timeout"
                                    class="form-control"
                                    name="timeout"
                                    value="<?= h((string)$timeout) ?>"
                                >
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i><?= __('GLOBAL__SUBMIT') ?>
                        </button>

                        <?= $this->Form->end() ?>
                    </div>
                </div>
            </div>

            <div class="col-12 col-lg-6">
                <div class="card card-outline card-info">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-flag mr-2"></i><?= __('SERVER__CONFIG_BANNER_MSG') ?>
                        </h3>
                    </div>

                    <div class="card-body">
                        <?= $this->Form->create(null, [
                            'url' => ['_name' => 'admin_server_edit_banner_msg'],
                            'method' => 'post',
                            'data-ajax' => 'true',
                        ]) ?>

                        <div class="ajax-msg"></div>

                        <div class="form-group mb-3">
                            <label class="sr-only" for="server-banner-msg"><?= __('SERVER__CONFIG_BANNER_MSG') ?></label>
                            <input
                                type="text"
                                id="server-banner-msg"
                                class="form-control"
                                name="msg"
                                value="<?= h((string)$bannerMsg) ?>"
                            >
                            <small class="form-text text-muted mt-2">
                                <?= __('CONFIG__LANG_AVAILABLE_VARIABLES') ?> : {ONLINE}, {ONLINE_LIMIT}
                            </small>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-save mr-2"></i><?= __('GLOBAL__SUBMIT') ?>
                        </button>

                        <?= $this->Form->end() ?>
                    </div>
                </div>
            </div>
        </div>

        <?php if (!empty($servers)) : ?>
            <div class="row">
                <div class="col-12">
                    <div class="callout callout-secondary mb-3">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                            <div class="text-sm mb-0">
                                <i class="fas fa-info-circle mr-2"></i><?= __('SERVER__LINKED_SERVERS_HINT') ?>
                            </div>
                            <span class="badge badge-light border">
                                <i class="fas fa-server mr-1"></i><?= (int)count((array)$servers) ?> <?= __('TABLE__ITEMS') ?>
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <?php foreach ($servers as $value) : ?>
                <?php
                $serverId = (int)$value['id'];
                $serverType = (string)($value['type'] ?? '0');
                $activeInBanner = !empty($value['activeInBanner']);
                $switchBannerUrl = $this->Url->build(['_name' => 'admin_server_switch_banner', $serverId]);
                $deleteUrl = $this->Url->build(['_name' => 'admin_server_delete', $serverId]);

                $rconPort = '';
                $rconPassword = '';
                if (!empty($value['data']) && is_array($value['data'])) {
                    $rconPort = isset($value['data']['rcon_port']) ? (string)$value['data']['rcon_port'] : '';
                    $rconPassword = isset($value['data']['rcon_password']) ? (string)$value['data']['rcon_password'] : '';
                }

                $serverTitle = (string)($value['name'] ?? '');
                if ($serverTitle === '') {
                    $serverTitle = 'Server #' . (string)$serverId;
                }

                $isCollapsed = true;
                ?>
                <div class="row">
                    <div class="col-12">
                        <div class="card card-outline card-secondary <?= $isCollapsed ? 'collapsed-card' : '' ?>" data-server-card="1">
                            <div class="card-header">
                                <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                                    <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                                        <h3 class="card-title mb-0">
                                            <i class="fas fa-server mr-2"></i><?= h($serverTitle) ?>
                                        </h3>

                                        <span class="badge badge-light border">
                                            <i class="fas fa-hashtag mr-1"></i><?= (int)$serverId ?>
                                        </span>

                                        <span class="badge badge-secondary">
                                            <i class="fas fa-plug mr-1"></i><?= __('SERVER__LINKED') ?>
                                        </span>
                                    </div>

                                    <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                        <button
                                            class="btn btn-sm switchBanner <?= $activeInBanner ? 'btn-danger' : 'btn-info' ?>"
                                            type="button"
                                            data-url="<?= h($switchBannerUrl) ?>"
                                        >
                                            <i class="fas fa-bullhorn mr-2"></i>
                                            <?= $activeInBanner ? __('SERVER__HIDE_BANNER') : __('SERVER__AFFICH_BANNER') ?>
                                        </button>

                                        <button type="button"
                                                class="btn btn-sm btn-outline-secondary btn-toggle-card"
                                                data-card-widget="collapse"
                                                data-text-show="<?= h((string)__('SERVER__SHOW_SERVER_SETTINGS')) ?>"
                                                data-text-hide="<?= h((string)__('SERVER__HIDE_SERVER_SETTINGS')) ?>">
                                            <i class="fas fa-chevron-down mr-2"></i>
                                            <span class="toggle-text"><?= __('SERVER__SHOW_SERVER_SETTINGS') ?></span>
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <div class="card-body" <?= $isCollapsed ? 'style="display:none;"' : '' ?>>

                                <?= $this->Form->create(null, [
                                    'url' => ['_name' => 'admin_server_link_ajax'],
                                    'method' => 'post',
                                    'data-ajax' => 'true',
                                ]) ?>

                                <div class="ajax-msg"></div>

                                <input type="hidden" name="id" value="<?= h((string)$serverId) ?>">

                                <div class="row">
                                    <div class="col-12 col-lg-6">
                                        <div class="form-group">
                                            <label for="server-type-<?= $serverId ?>" class="mb-1"><?= __('SERVER__TYPE') ?></label>
                                            <select
                                                class="form-control server-type"
                                                id="server-type-<?= $serverId ?>"
                                                name="type"
                                            >
                                                <option value="0"<?= $serverType === '0' ? ' selected' : '' ?>>
                                                    <?= __('SERVER__TYPE_DEFAULT') ?>
                                                </option>
                                                <option value="1"<?= $serverType === '1' ? ' selected' : '' ?>>
                                                    <?= __('SERVER__TYPE_QUERY') ?>
                                                </option>
                                                <option value="2"<?= $serverType === '2' ? ' selected' : '' ?>>
                                                    <?= __('SERVER__TYPE_RCON') ?>
                                                </option>
                                                <option value="3"<?= $serverType === '3' ? ' selected' : '' ?>>
                                                    <?= __('SERVER__TYPE_QUERY_MCPE') ?>
                                                </option>
                                            </select>
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="form-group">
                                            <label for="server-name-<?= $serverId ?>" class="mb-1"><?= __('GLOBAL__NAME') ?></label>
                                            <input
                                                type="text"
                                                id="server-name-<?= $serverId ?>"
                                                class="form-control"
                                                name="name"
                                                value="<?= h((string)($value['name'] ?? '')) ?>"
                                                placeholder="Ex: MineWeb"
                                            >
                                        </div>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-12 col-lg-6">
                                        <div class="form-group">
                                            <label for="server-host-<?= $serverId ?>" class="mb-1"><?= __('SERVER__HOST') ?></label>
                                            <input
                                                type="text"
                                                id="server-host-<?= $serverId ?>"
                                                class="form-control"
                                                name="host"
                                                value="<?= h((string)($value['ip'] ?? '')) ?>"
                                                placeholder="Ex: 127.0.0.1"
                                            >
                                        </div>
                                    </div>

                                    <div class="col-12 col-lg-6">
                                        <div class="form-group">
                                            <label for="server-port-<?= $serverId ?>" class="mb-1"><?= __('SERVER__PORT') ?></label>
                                            <input
                                                type="text"
                                                id="server-port-<?= $serverId ?>"
                                                class="form-control"
                                                name="port"
                                                value="<?= h((string)($value['port'] ?? '')) ?>"
                                                placeholder="Ex: 25565"
                                            >
                                        </div>
                                    </div>
                                </div>

                                <?php if ($serverType === '2') : ?>
                                    <div class="row">
                                        <div class="col-12 col-lg-6">
                                            <div class="form-group">
                                                <label for="server-rcon-port-<?= $serverId ?>" class="mb-1"><?= __('SERVER__RCON_PORT') ?></label>
                                                <input
                                                    type="text"
                                                    id="server-rcon-port-<?= $serverId ?>"
                                                    class="form-control"
                                                    name="server_data[rcon_port]"
                                                    value="<?= h($rconPort) ?>"
                                                    placeholder="Ex: 25575"
                                                >
                                            </div>
                                        </div>

                                        <div class="col-12 col-lg-6">
                                            <div class="form-group">
                                                <label for="server-rcon-password-<?= $serverId ?>" class="mb-1"><?= __('SERVER__RCON_PASSWORD') ?></label>
                                                <input
                                                    type="password"
                                                    id="server-rcon-password-<?= $serverId ?>"
                                                    class="form-control"
                                                    name="server_data[rcon_password]"
                                                    value="<?= h($rconPassword) ?>"
                                                    placeholder="**********"
                                                >
                                            </div>
                                        </div>
                                    </div>
                                <?php endif; ?>

                                <div class="d-flex align-items-center flex-wrap" style="gap: 8px;">
                                    <button type="submit" class="btn btn-success">
                                        <i class="fas fa-check mr-2"></i><?= __('GLOBAL__SUBMIT') ?>
                                    </button>

                                    <a href="<?= h($deleteUrl) ?>" class="btn btn-danger">
                                        <i class="fas fa-trash mr-2"></i><?= __('GLOBAL__DELETE') ?>
                                    </a>
                                </div>

                                <?= $this->Form->end() ?>

                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <div id="add_server_content"></div>

        <div class="row">
            <div class="col-12">
                <button
                    type="button"
                    class="btn btn-success btn-block mb-3"
                    id="add_server"
                    data-link-ajax-url="<?= h($urlLinkAjax) ?>"
                >
                    <i class="fas fa-plus mr-2"></i><?= __('SERVER__ADD') ?>
                </button>
            </div>
        </div>

    </div>
</section>

<script>
    (function () {
        const i18n = {
            showBanner: <?= json_encode(__('SERVER__AFFICH_BANNER'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            hideBanner: <?= json_encode(__('SERVER__HIDE_BANNER'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            typeDefaultInfos: <?= json_encode(__('SERVER__TYPE_DEFAULT_INFOS'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            typeQueryInfos: <?= json_encode(__('SERVER__TYPE_QUERY_INFOS'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            typeRconInfos: <?= json_encode(__('SERVER__TYPE_RCON_INFOS'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            linkTitle: <?= json_encode(__('SERVER__LINK_TITLE'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            typeLabel: <?= json_encode(__('SERVER__TYPE'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            typeDefault: <?= json_encode(__('SERVER__TYPE_DEFAULT'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            typeQuery: <?= json_encode(__('SERVER__TYPE_QUERY'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            typeRcon: <?= json_encode(__('SERVER__TYPE_RCON'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            typeQueryMcpe: <?= json_encode(__('SERVER__TYPE_QUERY_MCPE'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            nameLabel: <?= json_encode(__('GLOBAL__NAME'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            hostLabel: <?= json_encode(__('SERVER__HOST'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            portLabel: <?= json_encode(__('SERVER__PORT'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            rconPortLabel: <?= json_encode(__('SERVER__RCON_PORT'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            rconPasswordLabel: <?= json_encode(__('SERVER__RCON_PASSWORD'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            submit: <?= json_encode(__('GLOBAL__SUBMIT'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            showSettings: <?= json_encode(__('SERVER__SHOW_SERVER_SETTINGS'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>,
            hideSettings: <?= json_encode(__('SERVER__HIDE_SERVER_SETTINGS'), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>
        };

        function closest(el, selector) {
            while (el && el.nodeType === 1) {
                if (el.matches(selector)) return el;
                el = el.parentElement;
            }
            return null;
        }

        function removeIfExists(node) {
            if (node && node.parentNode) node.parentNode.removeChild(node);
        }

        function makeAlertInfo(html) {
            const div = document.createElement('div');
            div.className = 'infos-type mt-2';

            const alert = document.createElement('div');
            alert.className = 'alert alert-info mb-0';
            alert.innerHTML = String(html || '');

            div.appendChild(alert);
            return div;
        }

        function removeRconFields(form) {
            const port = form.querySelector('input[name="server_data[rcon_port]"]');
            const pwd = form.querySelector('input[name="server_data[rcon_password]"]');
            removeIfExists(port ? closest(port, '.form-group') : null);
            removeIfExists(pwd ? closest(pwd, '.form-group') : null);
        }

        function addRconFields(form, beforeNode) {
            const fgPort = document.createElement('div');
            fgPort.className = 'form-group';
            const lblPort = document.createElement('label');
            lblPort.textContent = i18n.rconPortLabel;
            const inpPort = document.createElement('input');
            inpPort.type = 'text';
            inpPort.className = 'form-control';
            inpPort.name = 'server_data[rcon_port]';
            inpPort.placeholder = 'Ex: 25575';
            fgPort.appendChild(lblPort);
            fgPort.appendChild(inpPort);

            const fgPwd = document.createElement('div');
            fgPwd.className = 'form-group';
            const lblPwd = document.createElement('label');
            lblPwd.textContent = i18n.rconPasswordLabel;
            const inpPwd = document.createElement('input');
            inpPwd.type = 'password';
            inpPwd.className = 'form-control';
            inpPwd.name = 'server_data[rcon_password]';
            inpPwd.placeholder = '**********';
            fgPwd.appendChild(lblPwd);
            fgPwd.appendChild(inpPwd);

            if (beforeNode && beforeNode.parentNode) {
                beforeNode.parentNode.insertBefore(fgPort, beforeNode);
                beforeNode.parentNode.insertBefore(fgPwd, beforeNode);
            } else {
                form.appendChild(fgPort);
                form.appendChild(fgPwd);
            }
        }

        function selectInfos(selectEl, init) {
            const form = closest(selectEl, 'form');
            if (!form) return;

            const fg = closest(selectEl, '.form-group');
            if (!fg) return;

            removeIfExists(fg.querySelector('.infos-type'));

            const type = String(selectEl.value || '0');
            let infoHtml = '';

            if (type === '0') {
                infoHtml = i18n.typeDefaultInfos;
                removeRconFields(form);
            } else if (type === '1' || type === '3') {
                infoHtml = i18n.typeQueryInfos;
                removeRconFields(form);
            } else if (type === '2') {
                infoHtml = i18n.typeRconInfos;

                const hasPort = !!form.querySelector('input[name="server_data[rcon_port]"]');
                const hasPwd = !!form.querySelector('input[name="server_data[rcon_password]"]');

                if ((!hasPort || !hasPwd) && !init) {
                    const firstButton = form.querySelector('button[type="submit"], input[type="submit"]');
                    addRconFields(form, firstButton);
                }
            }

            fg.appendChild(makeAlertInfo(infoHtml));
        }

        function onTypeChange(e) {
            selectInfos(e.target, false);
        }

        function initSelectInfos(root) {
            (root || document).querySelectorAll('select[name="type"]').forEach(function (sel) {
                sel.removeEventListener('change', onTypeChange);
                sel.addEventListener('change', onTypeChange);
            });
        }

        function initSelectInfosInitial(root) {
            (root || document).querySelectorAll('select[name="type"]').forEach(function (sel) {
                selectInfos(sel, true);
            });
        }

        function onSwitchBanner(e) {
            e.preventDefault();

            const btn = e.currentTarget;
            const url = btn.getAttribute('data-url') || '';

            if (btn.classList.contains('btn-danger')) {
                btn.classList.remove('btn-danger');
                btn.classList.add('btn-info');
                btn.innerHTML = '<i class="fas fa-bullhorn mr-2"></i>' + i18n.showBanner;
            } else {
                btn.classList.remove('btn-info');
                btn.classList.add('btn-danger');
                btn.innerHTML = '<i class="fas fa-bullhorn mr-2"></i>' + i18n.hideBanner;
            }

            if (url) {
                fetch(url, {
                    method: 'GET',
                    credentials: 'same-origin',
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                }).catch(function () {});
            }

            return false;
        }

        function bindSwitchBanner(root) {
            (root || document).querySelectorAll('.switchBanner').forEach(function (btn) {
                btn.removeEventListener('click', onSwitchBanner);
                btn.addEventListener('click', onSwitchBanner);
            });
        }

        function updateToggleButton(btn, isCollapsed) {
            if (!btn) return;
            const textEl = btn.querySelector('.toggle-text');
            const icon = btn.querySelector('i');
            const tShow = btn.getAttribute('data-text-show') || i18n.showSettings;
            const tHide = btn.getAttribute('data-text-hide') || i18n.hideSettings;

            if (textEl) textEl.textContent = isCollapsed ? tShow : tHide;

            if (icon) {
                icon.classList.remove('fa-chevron-down', 'fa-chevron-up');
                icon.classList.add(isCollapsed ? 'fa-chevron-down' : 'fa-chevron-up');
            }
        }

        function syncToggleButtons(root) {
            (root || document).querySelectorAll('.card[data-server-card="1"]').forEach(function (card) {
                const btn = card.querySelector('.btn-toggle-card');
                const isCollapsed = card.classList.contains('collapsed-card');
                updateToggleButton(btn, isCollapsed);
            });
        }

        function bindToggleButtons(root) {
            (root || document).querySelectorAll('.btn-toggle-card').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    const card = closest(btn, '.card');
                    if (!card) return;
                    setTimeout(function () {
                        updateToggleButton(btn, card.classList.contains('collapsed-card'));
                    }, 0);
                });
            });
        }

        let i = 0;

        function buildNewServerCard(actionUrl, index) {
            const row = document.createElement('div');
            row.className = 'row';

            const col = document.createElement('div');
            col.className = 'col-12';

            const card = document.createElement('div');
            card.className = 'card card-outline card-secondary';

            const header = document.createElement('div');
            header.className = 'card-header';

            const headerWrap = document.createElement('div');
            headerWrap.className = 'd-flex align-items-center justify-content-between flex-wrap';
            headerWrap.style.gap = '10px';

            const h3 = document.createElement('h3');
            h3.className = 'card-title mb-0';
            h3.innerHTML = '<i class="fas fa-link mr-2"></i>' + i18n.linkTitle;

            const tools = document.createElement('div');
            tools.className = 'd-flex align-items-center flex-wrap';
            tools.style.gap = '8px';

            const toggleBtn = document.createElement('button');
            toggleBtn.type = 'button';
            toggleBtn.className = 'btn btn-sm btn-outline-secondary btn-toggle-card';
            toggleBtn.setAttribute('data-card-widget', 'collapse');
            toggleBtn.setAttribute('data-text-show', i18n.showSettings);
            toggleBtn.setAttribute('data-text-hide', i18n.hideSettings);
            toggleBtn.innerHTML = '<i class="fas fa-chevron-up mr-2"></i><span class="toggle-text">' + i18n.hideSettings + '</span>';

            tools.appendChild(toggleBtn);
            headerWrap.appendChild(h3);
            headerWrap.appendChild(tools);
            header.appendChild(headerWrap);

            const body = document.createElement('div');
            body.className = 'card-body';

            const form = document.createElement('form');
            form.setAttribute('id', String(index));
            form.setAttribute('action', actionUrl);
            form.setAttribute('method', 'post');
            form.setAttribute('data-ajax', 'true');

            const ajaxMsg = document.createElement('div');
            ajaxMsg.className = 'ajax-msg';
            form.appendChild(ajaxMsg);

            const fgType = document.createElement('div');
            fgType.className = 'form-group';

            const lblType = document.createElement('label');
            lblType.textContent = i18n.typeLabel;

            const sel = document.createElement('select');
            sel.className = 'form-control';
            sel.name = 'type';

            const opt0 = document.createElement('option');
            opt0.value = '0';
            opt0.textContent = i18n.typeDefault;

            const opt1 = document.createElement('option');
            opt1.value = '1';
            opt1.textContent = i18n.typeQuery;

            const opt2 = document.createElement('option');
            opt2.value = '2';
            opt2.textContent = i18n.typeRcon;

            const opt3 = document.createElement('option');
            opt3.value = '3';
            opt3.textContent = i18n.typeQueryMcpe;

            sel.appendChild(opt0);
            sel.appendChild(opt1);
            sel.appendChild(opt2);
            sel.appendChild(opt3);

            fgType.appendChild(lblType);
            fgType.appendChild(sel);
            form.appendChild(fgType);

            const fgName = document.createElement('div');
            fgName.className = 'form-group';
            const lblName = document.createElement('label');
            lblName.textContent = i18n.nameLabel;
            const inpName = document.createElement('input');
            inpName.type = 'text';
            inpName.className = 'form-control';
            inpName.name = 'name';
            inpName.placeholder = 'Ex: MineWeb';
            fgName.appendChild(lblName);
            fgName.appendChild(inpName);
            form.appendChild(fgName);

            const fgHost = document.createElement('div');
            fgHost.className = 'form-group';
            const lblHost = document.createElement('label');
            lblHost.textContent = i18n.hostLabel;
            const inpHost = document.createElement('input');
            inpHost.type = 'text';
            inpHost.className = 'form-control';
            inpHost.name = 'host';
            inpHost.placeholder = 'Ex: 127.0.0.1';
            fgHost.appendChild(lblHost);
            fgHost.appendChild(inpHost);
            form.appendChild(fgHost);

            const fgPort = document.createElement('div');
            fgPort.className = 'form-group';
            const lblPort = document.createElement('label');
            lblPort.textContent = i18n.portLabel;
            const inpPort = document.createElement('input');
            inpPort.type = 'text';
            inpPort.className = 'form-control';
            inpPort.name = 'port';
            inpPort.placeholder = 'Ex: 25565';
            fgPort.appendChild(lblPort);
            fgPort.appendChild(inpPort);
            form.appendChild(fgPort);

            const btn = document.createElement('button');
            btn.type = 'submit';
            btn.className = 'btn btn-success';
            btn.innerHTML = '<i class="fas fa-check mr-2"></i>' + i18n.submit;
            form.appendChild(btn);

            body.appendChild(form);
            card.appendChild(header);
            card.appendChild(body);
            col.appendChild(card);
            row.appendChild(col);

            return row;
        }

        function onAddServerClick() {
            i++;

            const addBtn = document.getElementById('add_server');
            const actionUrl = addBtn ? (addBtn.getAttribute('data-link-ajax-url') || '') : '';
            if (!actionUrl) return;

            const container = document.getElementById('add_server_content');
            if (!container) return;

            const card = buildNewServerCard(actionUrl, i);
            container.appendChild(card);

            initSelectInfos(card);
            initSelectInfosInitial(card);
            bindSwitchBanner(card);
            bindToggleButtons(card);
            syncToggleButtons(card);

            if (window.AjaxForms && typeof window.AjaxForms.init === 'function') {
                window.AjaxForms.init(card);
            }
        }

        document.addEventListener('DOMContentLoaded', function () {
            bindSwitchBanner();
            initSelectInfos();
            initSelectInfosInitial();
            bindToggleButtons();
            syncToggleButtons();

            const addBtn = document.getElementById('add_server');
            if (addBtn) addBtn.addEventListener('click', onAddServerClick);

            if (window.AjaxForms && typeof window.AjaxForms.init === 'function') {
                window.AjaxForms.init(document);
            }
        });
    })();
</script>
