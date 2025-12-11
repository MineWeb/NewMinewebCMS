<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('SERVER__CONFIG_LABEL') ?></h3>
                </div>
                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_server_config'],
                        'method' => 'post',
                        'data-ajax' => 'true'
                    ]) ?>

                    <div class="ajax-msg"></div>

                    <div class="form-group">
                        <label for="server-timeout"><?= __('SERVER__TIMEOUT') ?></label>
                        <input
                            type="text"
                            id="server-timeout"
                            class="form-control"
                            name="timeout"
                            value="<?= $timeout ?>"
                        >
                    </div>

                    <button type="submit" class="btn btn-primary"><?= __('GLOBAL__SUBMIT') ?></button>
                    <a href="<?= $this->Url->build(['_name' => 'admin_server_switch_state']) ?>"
                       class="btn btn-<?= $isEnabled ? 'danger' : 'success' ?>">
                        <?= $isEnabled ? __('SERVER__DISABLE_SERVER') : __('SERVER__ENABLE_SERVER') ?>
                    </a>
                    <a href="<?= $this->Url->build(['_name' => 'admin_server_switch_cache_state']) ?>"
                       class="btn btn-<?= $isCacheEnabled ? 'danger' : 'success' ?>">
                        <?= $isCacheEnabled ? __('SERVER__DISABLE_CACHE') : __('SERVER__ENABLE_CACHE') ?>
                    </a>

                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('SERVER__CONFIG_BANNER_MSG') ?></h3>
                </div>
                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_server_edit_banner_msg'],
                        'method' => 'post',
                        'data-ajax' => 'true'
                    ]) ?>

                    <div class="ajax-msg"></div>

                    <div class="form-group">
                        <input
                            type="text"
                            class="form-control"
                            name="msg"
                            value="<?= $bannerMsg ?>"
                        >
                        <small>
                            <?= __('CONFIG__LANG_AVAILABLE_VARIABLES') ?> : {ONLINE}, {ONLINE_LIMIT}
                        </small>
                    </div>

                    <button type="submit" class="btn btn-primary"><?= __('GLOBAL__SUBMIT') ?></button>

                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>
    </div>
    <?php if (!empty($servers)) { ?>
        <?php foreach ($servers as $key => $value) { ?>
            <div class="row">
                <div class="col-md-12">
                    <div class="card">
                        <div class="card-header with-border">
                            <h3 class="card-title"><?= __('SERVER__LINK') ?></h3>
                        </div>
                        <div class="card-body">

                            <?= $this->Form->create(null, [
                                'url' => ['_name' => 'admin_server_link_ajax'],
                                'method' => 'post',
                                'data-ajax' => 'true'
                            ]) ?>

                            <div class="ajax-msg"></div>

                            <input type="hidden" name="id" value="<?= $value['id'] ?>">

                            <div class="form-group">
                                <label for="server-type-<?= $value['id'] ?>"><?= __('SERVER__TYPE') ?></label>
                                <select
                                    class="form-control"
                                    id="server-type-<?= $value['id'] ?>"
                                    name="type"
                                >
                                    <option value="0"<?= $value['type'] == '0' ? ' selected' : '' ?>>
                                        <?= __('SERVER__TYPE_DEFAULT') ?>
                                    </option>
                                    <option value="1"<?= $value['type'] == '1' ? ' selected' : '' ?>>
                                        <?= __('SERVER__TYPE_QUERY') ?>
                                    </option>
                                    <option value="2"<?= $value['type'] == '2' ? ' selected' : '' ?>>
                                        <?= __('SERVER__TYPE_RCON') ?>
                                    </option>
                                    <option value="3"<?= $value['type'] == '3' ? ' selected' : '' ?>>
                                        <?= __('SERVER__TYPE_QUERY_MCPE') ?>
                                    </option>
                                </select>
                            </div>

                            <div class="form-group">
                                <label for="server-name-<?= $value['id'] ?>"><?= __('GLOBAL__NAME') ?></label>
                                <input
                                    type="text"
                                    id="server-name-<?= $value['id'] ?>"
                                    class="form-control"
                                    name="name"
                                    value="<?= $value['name'] ?>"
                                    placeholder="Ex: MineWeb"
                                >
                            </div>

                            <div class="form-group">
                                <label for="server-host-<?= $value['id'] ?>"><?= __('SERVER__HOST') ?></label>
                                <input
                                    type="text"
                                    id="server-host-<?= $value['id'] ?>"
                                    class="form-control"
                                    name="host"
                                    value="<?= $value['ip'] ?>"
                                    placeholder="Ex: 127.0.0.1"
                                >
                            </div>

                            <div class="form-group">
                                <label for="server-port-<?= $value['id'] ?>"><?= __('SERVER__PORT') ?></label>
                                <input
                                    type="text"
                                    id="server-port-<?= $value['id'] ?>"
                                    class="form-control"
                                    name="port"
                                    value><?= $value['port'] ?>
                                placeholder="Ex: 25565"
                                >
                            </div>

                            <?php if ($value['type'] == '2'): ?>
                                <div class="form-group">
                                    <label for="server-rcon-port-<?= $value['id'] ?>">
                                        <?= __('SERVER__RCON_PORT') ?>
                                    </label>
                                    <input
                                        type="text"
                                        id="server-rcon-port-<?= $value['id'] ?>"
                                        class="form-control"
                                        name="server_data[rcon_port]"
                                        value="<?= $value['data']['rcon_port'] ?>"
                                        placeholder="Ex: 25575"
                                    >
                                </div>

                                <div class="form-group">
                                    <label for="server-rcon-password-<?= $value['id'] ?>">
                                        <?= __('SERVER__RCON_PASSWORD') ?>
                                    </label>
                                    <input
                                        type="password"
                                        id="server-rcon-password-<?= $value['id'] ?>"
                                        class="form-control"
                                        name="server_data[rcon_password]"
                                        value="<?= $value['data']['rcon_password'] ?>"
                                        placeholder="**********"
                                    >
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-success"><?= __('GLOBAL__SUBMIT') ?></button>
                            <a href="<?= $this->Url->build(['_name' => 'admin_server_delete', $value['id']]) ?>"
                               class="btn btn-danger">
                                <?= __('GLOBAL__DELETE') ?>
                            </a>

                            <button
                                class="btn switchBanner float-right <?= isset($value['activeInBanner']) && $value['activeInBanner'] ? 'btn-danger' : 'btn-info' ?>"
                                id="switchBanner-<?= $value['id'] ?>"
                                type="button"
                                data-url="<?= $this->Url->build(['_name' => 'admin_server_switch_banner', $value['id']]) ?>"
                            >
                                <?= isset($value['activeInBanner']) && $value['activeInBanner']
                                    ? __('SERVER__HIDE_BANNER')
                                    : __('SERVER__AFFICH_BANNER') ?>
                            </button>

                            <?= $this->Form->end() ?>

                        </div>
                    </div>
                </div>
            </div>
        <?php } ?>
    <?php } ?>

    <div id="add_server_content"></div>
    <div
        class="btn btn-success btn-block mb-3"
        id="add_server"
        data-link-ajax-url="<?= $this->Url->build(['_name' => 'admin_server_link_ajax']) ?>"
    >
        <?= __('SERVER__ADD') ?>
    </div>
</section>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        var switchButtons = document.querySelectorAll('.switchBanner');
        switchButtons.forEach(function (btn) {
            btn.addEventListener('click', function (e) {
                e.preventDefault();

                var isDanger = btn.classList.contains('btn-danger');
                if (isDanger) {
                    btn.classList.remove('btn-danger');
                    btn.classList.add('btn-info');
                    btn.textContent = '<?= __('SERVER__AFFICH_BANNER') ?>';
                } else {
                    btn.classList.remove('btn-info');
                    btn.classList.add('btn-danger');
                    btn.textContent = '<?= __('SERVER__HIDE_BANNER') ?>';
                }

                var url = btn.getAttribute('data-url');
                if (!url) {
                    return;
                }

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                });
            });
        });

        function selectInfos(select, init) {
            var type = select.value;

            var existingInfos = select.parentElement.querySelector('.infos-type');
            if (existingInfos) {
                existingInfos.remove();
            }

            var form = select.closest('form');
            if (form) {
                var rconPortInput = form.querySelector('input[name="server_data[rcon_port]"]');
                if (rconPortInput && rconPortInput.parentElement && type !== '2') {
                    rconPortInput.parentElement.remove();
                }

                var rconPasswordInput = form.querySelector('input[name="server_data[rcon_password]"]');
                if (rconPasswordInput && rconPasswordInput.parentElement && type !== '2') {
                    rconPasswordInput.parentElement.remove();
                }
            }

            var infos;
            if (type === '0') {
                infos = '<div class="alert alert-info"><?= addslashes(__('SERVER__TYPE_DEFAULT_INFOS')) ?></div>';
            } else if (type === '1' || type === '3') {
                infos = '<div class="alert alert-info"><?= addslashes(__('SERVER__TYPE_QUERY_INFOS')) ?></div>';
            } else if (type === '2') {
                infos = '<div class="alert alert-info"><?= addslashes(__('SERVER__TYPE_RCON_INFOS')) ?></div>';
                if (!init && form) {
                    var buttons = form.querySelectorAll('button');
                    var insertBeforeElement = buttons.length > 0 ? buttons[0].parentElement || buttons[0] : null;

                    var wrapper = document.createElement('div');
                    wrapper.innerHTML = ''
                        + '<div class="form-group">'
                        + '<label><?= __('SERVER__RCON_PORT') ?></label>'
                        + '<input type="text" class="form-control" name="server_data[rcon_port]" placeholder="Ex: 25575">'
                        + '</div>'
                        + '<div class="form-group">'
                        + '<label><?= __('SERVER__RCON_PASSWORD') ?></label>'
                        + '<input type="password" class="form-control" name="server_data[rcon_password]" placeholder="**********">'
                        + '</div>';

                    while (wrapper.firstChild) {
                        if (insertBeforeElement && insertBeforeElement.parentElement) {
                            insertBeforeElement.parentElement.insertBefore(wrapper.firstChild, insertBeforeElement);
                        } else {
                            form.appendChild(wrapper.firstChild);
                        }
                    }
                }
            }

            if (infos) {
                var infosDiv = document.createElement('div');
                infosDiv.className = 'infos-type';
                infosDiv.innerHTML = '<br>' + infos;
                select.parentElement.insertAdjacentElement('afterend', infosDiv);
            }
        }

        function initSelectInfos() {
            var selects = document.querySelectorAll('select[name="type"]');
            selects.forEach(function (select) {
                if (select.dataset.initSelectBound === '1') {
                    return;
                }
                select.dataset.initSelectBound = '1';

                select.addEventListener('change', function () {
                    selectInfos(select, false);
                });

                selectInfos(select, true);
            });
        }

        initSelectInfos();

        var i = 0;
        var addServerBtn = document.getElementById('add_server');
        if (addServerBtn) {
            var linkAjaxUrl = addServerBtn.getAttribute('data-link-ajax-url') || '';

            addServerBtn.addEventListener('click', function () {
                i++;

                var newServerHtml = ''
                    + '<div class="row">'
                    + '<div class="col-md-12">'
                    + '<div class="card">'
                    + '<div class="card-header with-border">'
                    + '<h3 class="card-title"><?= __('SERVER__LINK') ?></h3>'
                    + '</div>'
                    + '<div class="card-body">'
                    + '<form id="' + i + '" action="' + linkAjaxUrl + '" method="post" data-ajax="true">'
                    + '<div class="ajax-msg"></div>'
                    + '<div class="form-group">'
                    + '<label><?= __('SERVER__TYPE') ?></label>'
                    + '<select class="form-control" name="type">'
                    + '<option value="0"><?= __('SERVER__TYPE_DEFAULT') ?></option>'
                    + '<option value="1"><?= __('SERVER__TYPE_QUERY') ?></option>'
                    + '<option value="2"><?= __('SERVER__TYPE_RCON') ?></option>'
                    + '<option value="3"><?= __('SERVER__TYPE_QUERY_MCPE') ?></option>'
                    + '</select>'
                    + '</div>'
                    + '<div class="form-group">'
                    + '<label><?= __('GLOBAL__NAME') ?></label>'
                    + '<input type="text" class="form-control" name="name" placeholder="Ex: MineWeb">'
                    + '</div>'
                    + '<div class="form-group">'
                    + '<label><?= __('SERVER__HOST') ?></label>'
                    + '<input type="text" class="form-control" name="host" placeholder="Ex: 127.0.0.1">'
                    + '</div>'
                    + '<div class="form-group">'
                    + '<label><?= __('SERVER__PORT') ?></label>'
                    + '<input type="text" class="form-control" name="port" placeholder="Ex: 25565">'
                    + '</div>'
                    + '<button type="submit" class="btn btn-success"><?= __('GLOBAL__SUBMIT') ?></button>'
                    + '</form>'
                    + '</div>'
                    + '</div>'
                    + '</div>'
                    + '</div>';

                var container = document.getElementById('add_server_content');
                if (!container) {
                    return;
                }

                var wrapper = document.createElement('div');
                wrapper.innerHTML = newServerHtml;
                while (wrapper.firstChild) {
                    container.appendChild(wrapper.firstChild);
                }

                initSelectInfos();
            });
        }
    });
</script>
