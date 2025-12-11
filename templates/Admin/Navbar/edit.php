<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NAVBAR__EDIT_TITLE') ?></h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_navbar_edit_ajax'],
                        'method' => 'post',
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_navbar_index']),
                        'data-custom-function' => 'formatteData',
                        'id' => 'navbar-edit-form'
                    ]) ?>

                    <input type="hidden" name="id" value="<?= h($nav['id']) ?>">

                    <div class="form-group">
                        <label for="nav-name"><?= __('GLOBAL__NAME') ?></label>
                        <input
                            id="nav-name"
                            name="name"
                            class="form-control"
                            type="text"
                            value="<?= h($nav['name']) ?>"
                        >
                    </div>

                    <div class="form-group">
                        <label for="nav-icon"><?= __('NAVBAR__ICON') ?></label>
                        <p>
                            <?= __('NAVBAR__ICON__DESC') ?>
                            <a target="_blank" href="https://fontawesome.com/">https://fontawesome.com/</a>
                        </p>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">FA</span>
                            </div>
                            <input
                                id="nav-icon"
                                name="icon"
                                class="form-control"
                                type="text"
                                value="<?= h($nav['icon']) ?>"
                            >
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= __('GLOBAL__TYPE') ?></label>
                        <div class="radio">
                            <input
                                type="radio"
                                id="nav-type-normal"
                                name="type"
                                value="normal"
                                <?= ($nav['type'] === "1") ? 'checked' : '' ?>
                            >
                            <label for="nav-type-normal"><?= __('NAVBAR__LINK_TYPE_DEFAULT') ?></label>
                        </div>
                        <div class="radio">
                            <input
                                type="radio"
                                id="nav-type-dropdown"
                                name="type"
                                value="dropdown"
                                <?= ($nav['type'] === "2") ? 'checked' : '' ?>
                            >
                            <label for="nav-type-dropdown"><?= __('NAVBAR__LINK_TYPE_DROPDOWN') ?></label>
                        </div>
                    </div>

                    <div id="type-normal" class="<?= ($nav['type'] === "1") ? '' : 'd-none' ?>">
                        <div class="form-group">
                            <label><?= __('URL') ?></label>

                            <div class="radio">
                                <input
                                    type="radio"
                                    id="url-type-plugin"
                                    class="url-type-plugin"
                                    name="url_type"
                                    value="plugin"
                                    <?= ($nav['urlData']['type'] === "plugin") ? 'checked' : '' ?>
                                >
                                <label for="url-type-plugin"><?= __('NAVBAR__LINK_TYPE_PLUGIN') ?></label>
                            </div>
                            <div class="plugin<?= ($nav['urlData']['type'] === "plugin") ? '' : ' d-none' ?>">
                                <label for="url-plugin-select" class="sr-only"><?= __('NAVBAR__LINK_TYPE_PLUGIN') ?></label>
                                <select class="form-control" name="url_plugin" id="url-plugin-select">
                                    <?php
                                    foreach ($url_plugins as $pluginId => $data) {
                                        echo '<option disabled>' . h($data->name) . '</option>';
                                        foreach ($data->routes as $name => $route) {
                                            $selected = ($nav['urlData']['type'] === 'plugin'
                                                && $nav['urlData']['id'] === $pluginId
                                                && $nav['urlData']['route'] === $route) ? ' selected' : '';
                                            $value = json_encode(['id' => $pluginId, 'route' => $route]);
                                            echo '<option value=\'' . h($value) . '\'' . $selected . '>' . h($name) . ' (' . h($route) . ')</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>

                            <div class="radio">
                                <input
                                    type="radio"
                                    id="url-type-page"
                                    class="url-type-page"
                                    name="url_type"
                                    value="page"
                                    <?= ($nav['urlData']['type'] === "page") ? 'checked' : '' ?>
                                >
                                <label for="url-type-page"><?= __('NAVBAR__LINK_TYPE_PAGE') ?></label>
                            </div>
                            <div class="page<?= ($nav['urlData']['type'] === "page") ? '' : ' d-none' ?>">
                                <label for="url-page-select" class="sr-only"><?= __('NAVBAR__LINK_TYPE_PAGE') ?></label>
                                <select class="form-control" name="url_page" id="url-page-select">
                                    <?php foreach ($url_pages as $key => $value) { ?>
                                        <option
                                            value="<?= h($key) ?>"
                                            <?= (isset($nav['urlData']['id']) && $nav['urlData']['id'] == $key) ? 'selected' : '' ?>
                                        >
                                            <?= h($value) ?>
                                        </option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="radio">
                                <input
                                    type="radio"
                                    id="url-type-custom"
                                    class="url-type-custom"
                                    name="url_type"
                                    value="custom"
                                    <?= ($nav['urlData']['type'] === "custom") ? 'checked' : '' ?>
                                >
                                <label for="url-type-custom"><?= __('NAVBAR__LINK_TYPE_CUSTOM') ?></label>
                            </div>

                            <label for="url-custom" class="sr-only"><?= __('NAVBAR__CUSTOM_URL') ?></label>
                            <input
                                id="url-custom"
                                type="text"
                                value="<?= ($nav['urlData']['type'] === "custom") ? h($nav['urlData']['url']) : '' ?>"
                                class="form-control custom<?= ($nav['urlData']['type'] === "custom") ? '' : ' d-none' ?>"
                                placeholder="<?= __('NAVBAR__CUSTOM_URL') ?>"
                                name="url_custom"
                            >
                        </div>
                    </div>

                    <div id="type-dropdown" class="<?= ($nav['type'] === "2") ? '' : 'd-none' ?>">
                        <div class="form-group">
                            <?php
                            $i = 0;
                            $subMenu = !empty($nav['submenu']) ? json_decode($nav['submenu'], true) : [];
                            foreach ($subMenu as $name => $url) {
                                $i++;
                                $nameId = 'name_of_nav_' . $i;
                                $urlId = 'url_of_nav_' . $i;
                                ?>
                                <div class="card card-body nav-item-block" id="nav-<?= $i ?>">
                                    <div class="form-group">
                                        <label for="<?= $nameId ?>"><?= __('NAVBAR__LINK_NAME') ?></label>
                                        <input
                                            type="text"
                                            class="form-control name_of_nav"
                                            id="<?= $nameId ?>"
                                            value="<?= h(urldecode($name)) ?>"
                                            name="name_of_nav"
                                        >
                                    </div>
                                    <div class="form-group">
                                        <label for="<?= $urlId ?>"><?= __('URL') ?></label>
                                        <input
                                            type="text"
                                            class="form-control url_of_nav"
                                            id="<?= $urlId ?>"
                                            value="<?= h(urldecode($url)) ?>"
                                            placeholder="<?= __('NAVBAR__CUSTOM_URL') ?>"
                                            name="url"
                                        >
                                    </div>
                                    <a href="#" class="btn btn-danger delete-nav float-right">
                                        <?= __('GLOBAL__DELETE') ?>
                                    </a>
                                    <br>
                                </div>
                            <?php } ?>
                        </div>
                        <div id="add-js" data-number="<?= (int)$i ?>"></div>
                        <div class="control-group">
                            <a href="#" id="add_nav" class="btn btn-success">
                                <?= __('NAVBAR__ADD_LINK') ?>
                            </a>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox">
                            <input
                                type="checkbox"
                                id="nav-new-tab"
                                name="new_tab"
                                <?= $nav['open_new_tab'] ? 'checked' : '' ?>
                            >
                            <label for="nav-new-tab"><?= __('NAVBAR__OPEN_IN_NEW_TAB') ?></label>
                        </div>
                    </div>

                    <div class="float-right">
                        <a
                            href="<?= $this->Url->build(['_name' => 'admin_navbar_index']) ?>"
                            class="btn btn-default"
                        >
                            <?= __('GLOBAL__CANCEL') ?>
                        </a>
                        <button class="btn btn-primary" type="submit">
                            <?= __('GLOBAL__SUBMIT') ?>
                        </button>
                    </div>
                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</section>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        let radioTypeNormal = document.getElementById('nav-type-normal');
        let radioTypeDropdown = document.getElementById('nav-type-dropdown');
        let typeNormal = document.getElementById('type-normal');
        let typeDropdown = document.getElementById('type-dropdown');

        let radioUrlPlugin = document.getElementById('url-type-plugin');
        let radioUrlPage = document.getElementById('url-type-page');
        let radioUrlCustom = document.getElementById('url-type-custom');
        let pluginBlocks = document.querySelectorAll('.plugin');
        let pageBlocks = document.querySelectorAll('.page');
        let customInputs = document.querySelectorAll('.custom');

        function showNormal() {
            if (typeNormal) {
                typeNormal.classList.remove('d-none');
            }
            if (typeDropdown) {
                typeDropdown.classList.add('d-none');
            }
        }

        function showDropdown() {
            if (typeNormal) {
                typeNormal.classList.add('d-none');
            }
            if (typeDropdown) {
                typeDropdown.classList.remove('d-none');
            }
        }

        if (radioTypeNormal) {
            radioTypeNormal.addEventListener('change', function () {
                if (radioTypeNormal.checked) {
                    showNormal();
                }
            });
        }
        if (radioTypeDropdown) {
            radioTypeDropdown.addEventListener('change', function () {
                if (radioTypeDropdown.checked) {
                    showDropdown();
                }
            });
        }

        function toggleUrlType() {
            let pluginChecked = radioUrlPlugin && radioUrlPlugin.checked;
            let pageChecked = radioUrlPage && radioUrlPage.checked;
            let customChecked = radioUrlCustom && radioUrlCustom.checked;

            pluginBlocks.forEach(function (el) {
                if (pluginChecked) {
                    el.classList.remove('d-none');
                } else {
                    el.classList.add('d-none');
                }
            });
            pageBlocks.forEach(function (el) {
                if (pageChecked) {
                    el.classList.remove('d-none');
                } else {
                    el.classList.add('d-none');
                }
            });
            customInputs.forEach(function (el) {
                if (customChecked) {
                    el.classList.remove('d-none');
                } else {
                    el.classList.add('d-none');
                }
            });
        }

        if (radioUrlPlugin) {
            radioUrlPlugin.addEventListener('change', toggleUrlType);
        }
        if (radioUrlPage) {
            radioUrlPage.addEventListener('change', toggleUrlType);
        }
        if (radioUrlCustom) {
            radioUrlCustom.addEventListener('change', toggleUrlType);
        }
        toggleUrlType();

        let addJs = document.getElementById('add-js');
        let addNavBtn = document.getElementById('add_nav');

        function attachDeleteHandlers() {
            let deleteButtons = document.querySelectorAll('.delete-nav');
            deleteButtons.forEach(function (btn) {
                btn.onclick = function (e) {
                    e.preventDefault();
                    let card = btn.closest('.card');
                    if (card) {
                        card.style.transition = 'opacity 150ms';
                        card.style.opacity = '0';
                        setTimeout(function () {
                            if (card.parentNode) {
                                card.parentNode.removeChild(card);
                            }
                        }, 160);
                    }
                };
            });
        }

        function addNavItem() {
            if (!addJs) {
                return;
            }
            let current = parseInt(addJs.getAttribute('data-number') || '0', 10);
            let next = current + 1;
            addJs.setAttribute('data-number', String(next));

            let nameId = 'name_of_nav_' + next;
            let urlId = 'url_of_nav_' + next;

            let wrapper = document.createElement('div');
            wrapper.className = 'form-group';
            wrapper.innerHTML =
                '<div class="card card-body nav-item-block" id="nav-' + next + '">' +
                '<div class="form-group">' +
                '<label for="' + nameId + '"><?= addslashes(__('NAVBAR__LINK_NAME')) ?></label>' +
                '<input type="text" class="form-control name_of_nav" id="' + nameId + '" name="name_of_nav">' +
                '</div>' +
                '<div class="form-group">' +
                '<label for="' + urlId + '"><?= addslashes(__('URL')) ?></label>' +
                '<input type="text" class="form-control url_of_nav" id="' + urlId + '" name="url" placeholder="<?= addslashes(__('NAVBAR__CUSTOM_URL')) ?>">' +
                '</div>' +
                '<a href="#" class="btn btn-danger delete-nav float-right"><?= addslashes(__('GLOBAL__DELETE')) ?></a>' +
                '<br>' +
                '</div>';

            addJs.appendChild(wrapper);
            attachDeleteHandlers();
        }

        if (addNavBtn) {
            addNavBtn.addEventListener('click', function (e) {
                e.preventDefault();
                addNavItem();
            });
        }

        attachDeleteHandlers();
    });

    function formatteData(form) {
        let nameInput = form.querySelector("input[name='name']");
        let iconInput = form.querySelector("input[name='icon']");
        let typeInput = form.querySelector("input[type='radio'][name='type']:checked");

        let name = nameInput ? nameInput.value : '';
        let icon = iconInput ? iconInput.value : '';
        let type = typeInput ? typeInput.value : '';

        let url;

        if (type === 'normal') {
            let urlTypeInput = form.querySelector("input[name='url_type']:checked");
            let urlType = urlTypeInput ? urlTypeInput.value : '';

            if (urlType === 'custom') {
                let customInput = form.querySelector("input[name='url_custom']");
                let customValue = customInput ? customInput.value : '';
                url = JSON.stringify({
                    type: 'custom',
                    url: customValue
                });
            } else if (urlType === 'plugin') {
                let pluginSelect = form.querySelector("select[name='url_plugin']");
                let pluginValue = pluginSelect ? pluginSelect.value : '';
                if (pluginValue) {
                    try {
                        let parsed = JSON.parse(pluginValue);
                        url = JSON.stringify({
                            type: 'plugin',
                            id: parsed.id,
                            route: parsed.route
                        });
                    } catch (e) {
                        url = JSON.stringify({
                            type: 'plugin'
                        });
                    }
                } else {
                    url = JSON.stringify({
                        type: 'plugin'
                    });
                }
            } else if (urlType === 'page') {
                let pageSelect = form.querySelector("select[name='url_page']");
                let pageValue = pageSelect ? pageSelect.value : '';
                url = JSON.stringify({
                    type: 'page',
                    id: pageValue
                });
            } else {
                url = 'undefined';
            }
        } else {
            let names = document.querySelectorAll('.name_of_nav');
            let urls = document.querySelectorAll('.url_of_nav');
            let urlObject = {};
            let length = Math.min(names.length, urls.length);

            for (let i = 0; i < length; i++) {
                let n = names[i].value;
                let u = urls[i].value;
                if (n !== '') {
                    urlObject[n] = u;
                }
            }

            url = urlObject;
        }

        let newTabInput = form.querySelector("input[name='new_tab']");
        let openNewTab = newTabInput ? newTabInput.checked : false;

        let idInput = form.querySelector("input[name='id']");
        let id = idInput ? idInput.value : null;

        let inputs = {};
        inputs.name = name;
        inputs.icon = icon;
        inputs.type = type;
        inputs.url = url;
        inputs.open_new_tab = openNewTab;
        inputs.id = id;

        return inputs;
    }
</script>
