<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NAVBAR__ADD_LINK') ?></h3>
                </div>
                <div class="card-body">
                    <?= $this->Form->create(null, [
                        'method' => 'post',
                        'url' => ['_name' => 'admin_navbar_add_ajax'],
                        'data-ajax' => 'true',
                        'data-redirect-url' => $this->Url->build(['_name' => 'admin_navbar_index']),
                        'data-custom-function' => 'formatteData'
                    ]) ?>

                    <div class="form-group">
                        <label for="navbar-name"><?= __('GLOBAL__NAME') ?></label>
                        <input id="navbar-name" name="name" class="form-control" type="text">
                    </div>

                    <div class="form-group">
                        <label for="navbar-icon"><?= __('NAVBAR__ICON') ?></label>
                        <p>
                            <?= __('NAVBAR__ICON__DESC') ?>
                            <a target="_blank" href="https://fontawesome.com/">https://fontawesome.com/</a>
                        </p>
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">FA</span>
                            </div>
                            <input id="navbar-icon" name="icon" class="form-control" type="text">
                        </div>
                    </div>

                    <div class="form-group">
                        <label><?= __('GLOBAL__TYPE') ?></label>
                        <div class="radio">
                            <input type="radio" id="normal" name="type" value="normal">
                            <label for="normal"><?= __('NAVBAR__LINK_TYPE_DEFAULT') ?></label>
                        </div>
                        <div class="radio">
                            <input type="radio" id="dropdown" name="type" value="dropdown">
                            <label for="dropdown"><?= __('NAVBAR__LINK_TYPE_DROPDOWN') ?></label>
                        </div>
                    </div>

                    <div id="type-normal" class="d-none">
                        <div class="form-group">
                            <label><?= __('URL') ?></label>
                            <div class="radio">
                                <input type="radio" id="url-type-plugin" class="type_plugin" name="url_type" value="plugin">
                                <label for="url-type-plugin"><?= __('NAVBAR__LINK_TYPE_PLUGIN') ?></label>
                            </div>
                            <div class="d-none plugin">
                                <select class="form-control" name="url_plugin">
                                    <?php
                                    foreach ($url_plugins as $pluginId => $data) {
                                        echo '<option disabled>' . $data->name . '</option>';
                                        foreach ($data->routes as $name => $route) {
                                            echo '<option value=\'' . json_encode(['id' => $pluginId, 'route' => $route]) . '\'>' . $name . ' (' . $route . ')</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="radio">
                                <input type="radio" id="url-type-page" class="type_page" name="url_type" value="page">
                                <label for="url-type-page"><?= __('NAVBAR__LINK_TYPE_PAGE') ?></label>
                            </div>
                            <div class="d-none page">
                                <select class="form-control" name="url_page">
                                    <?php foreach ($url_pages as $key => $value) { ?>
                                        <option value="<?= $key ?>"><?= $value ?></option>
                                    <?php } ?>
                                </select>
                            </div>
                            <div class="radio">
                                <input type="radio" id="url-type-custom" class="type_custom" name="url_type" value="custom">
                                <label for="url-type-custom"><?= __('NAVBAR__LINK_TYPE_CUSTOM') ?></label>
                            </div>
                            <input
                                type="text"
                                class="form-control d-none custom"
                                id="url-custom"
                                placeholder="<?= __('NAVBAR__CUSTOM_URL') ?>"
                                name="url_custom"
                            >
                        </div>
                    </div>

                    <div id="type-dropdown" class="d-none">
                        <div class="form-group">
                            <div class="card card-body" id="nav-1">
                                <div class="form-group">
                                    <label for="name-of-nav-1"><?= __('NAVBAR__LINK_NAME') ?></label>
                                    <input
                                        type="text"
                                        class="form-control name_of_nav"
                                        id="name-of-nav-1"
                                        name="name_of_nav"
                                    >
                                </div>
                                <div class="form-group">
                                    <label for="url-of-nav-1"><?= __('URL') ?></label>
                                    <input
                                        type="text"
                                        class="form-control url_of_nav"
                                        id="url-of-nav-1"
                                        placeholder="<?= __('NAVBAR__CUSTOM_URL') ?>"
                                        name="url"
                                    >
                                </div>
                                <a href="#"
                                   class="btn btn-danger delete-nav float-right"><?= __('GLOBAL__DELETE') ?></a>
                                <br>
                            </div>
                        </div>
                        <div id="add-js" data-number="1"></div>
                        <div class="control-group">
                            <a href="#" id="add_nav"
                               class="btn btn-success"><?= __('NAVBAR__ADD_LINK') ?></a>
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="checkbox">
                            <input type="checkbox" id="navbar-new-tab" name="new_tab">
                            <label for="navbar-new-tab"><?= __('NAVBAR__OPEN_IN_NEW_TAB') ?></label>
                        </div>
                    </div>

                    <div class="float-right">
                        <a href="<?= $this->Url->build(['_name' => 'admin_navbar_index']) ?>"
                           class="btn btn-default"><?= __('GLOBAL__CANCEL') ?></a>
                        <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                    </div>

                    <?= $this->Form->end() ?>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        let normalRadio = document.getElementById('normal');
        let dropdownRadio = document.getElementById('dropdown');
        let typeNormalDiv = document.getElementById('type-normal');
        let typeDropdownDiv = document.getElementById('type-dropdown');

        function updateTypeVisibility() {
            if (normalRadio && normalRadio.checked) {
                if (typeNormalDiv) {
                    typeNormalDiv.classList.remove('d-none');
                }
                if (typeDropdownDiv) {
                    typeDropdownDiv.classList.add('d-none');
                }
            } else if (dropdownRadio && dropdownRadio.checked) {
                if (typeDropdownDiv) {
                    typeDropdownDiv.classList.remove('d-none');
                }
                if (typeNormalDiv) {
                    typeNormalDiv.classList.add('d-none');
                }
            }
        }

        if (normalRadio) {
            normalRadio.addEventListener('change', updateTypeVisibility);
        }
        if (dropdownRadio) {
            dropdownRadio.addEventListener('change', updateTypeVisibility);
        }
        updateTypeVisibility();

        let typePlugin = document.querySelector('.type_plugin');
        let typePage = document.querySelector('.type_page');
        let typeCustom = document.querySelector('.type_custom');
        let pluginBlock = document.querySelector('.plugin');
        let pageBlock = document.querySelector('.page');
        let customInput = document.querySelector('.custom');

        function updateUrlType() {
            if (typePlugin && typePlugin.checked) {
                if (pluginBlock) {
                    pluginBlock.classList.remove('d-none');
                }
                if (pageBlock) {
                    pageBlock.classList.add('d-none');
                }
                if (customInput) {
                    customInput.classList.add('d-none');
                }
            } else if (typePage && typePage.checked) {
                if (pluginBlock) {
                    pluginBlock.classList.add('d-none');
                }
                if (pageBlock) {
                    pageBlock.classList.remove('d-none');
                }
                if (customInput) {
                    customInput.classList.add('d-none');
                }
            } else if (typeCustom && typeCustom.checked) {
                if (pluginBlock) {
                    pluginBlock.classList.add('d-none');
                }
                if (pageBlock) {
                    pageBlock.classList.add('d-none');
                }
                if (customInput) {
                    customInput.classList.remove('d-none');
                }
            } else {
                if (pluginBlock) {
                    pluginBlock.classList.add('d-none');
                }
                if (pageBlock) {
                    pageBlock.classList.add('d-none');
                }
                if (customInput) {
                    customInput.classList.add('d-none');
                }
            }
        }

        if (typePlugin) {
            typePlugin.addEventListener('change', updateUrlType);
        }
        if (typePage) {
            typePage.addEventListener('change', updateUrlType);
        }
        if (typeCustom) {
            typeCustom.addEventListener('change', updateUrlType);
        }
        updateUrlType();

        let addNavBtn = document.getElementById('add_nav');
        let addJsContainer = document.getElementById('add-js');

        if (addNavBtn && addJsContainer) {
            let how = parseInt(addJsContainer.getAttribute('data-number') || '1', 10);

            addNavBtn.addEventListener('click', function (e) {
                e.preventDefault();
                how += 1;
                addJsContainer.setAttribute('data-number', String(how));

                let wrapper = document.createElement('div');
                let nameId = 'name-of-nav-' + how;
                let urlId = 'url-of-nav-' + how;

                let html = ''
                    + '<div class="form-group">'
                    + '<div class="card card-body" id="nav-' + how + '">'
                    + '<div class="form-group">'
                    + '<label for="' + nameId + '"><?= addslashes(__('NAVBAR__LINK_NAME')) ?></label>'
                    + '<input type="text" class="form-control name_of_nav" id="' + nameId + '" name="name_of_nav">'
                    + '</div>'
                    + '<div class="form-group">'
                    + '<label for="' + urlId + '"><?= __('URL') ?></label>'
                    + '<input type="text" class="form-control url_of_nav" id="' + urlId + '" placeholder="<?= __('NAVBAR__CUSTOM_URL') ?>" name="url">'
                    + '</div>'
                    + '<a href="#" class="btn btn-danger delete-nav float-right"><?= __('GLOBAL__DELETE') ?></a>'
                    + '<br>'
                    + '</div>'
                    + '</div>';

                wrapper.innerHTML = html;
                while (wrapper.firstChild) {
                    addJsContainer.appendChild(wrapper.firstChild);
                }
            });
        }

        document.addEventListener('click', function (e) {
            let target = e.target;
            if (target && target.classList.contains('delete-nav')) {
                e.preventDefault();
                let card = target.closest('.card');
                if (card) {
                    card.remove();
                }
            }
        });
    });

    function formatteData(form) {
        if (form && form.jquery && form[0]) {
            form = form[0];
        }

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
                url = '{"type":"custom","url":"' + customValue + '"}';
            } else if (urlType === 'plugin') {
                let pluginSelect = form.querySelector("select[name='url_plugin']");
                let value = pluginSelect ? pluginSelect.value : '';
                try {
                    let parsed = JSON.parse(value);
                    url = {
                        type: 'plugin',
                        id: parsed.id,
                        route: parsed.route
                    };
                    url = JSON.stringify(url);
                } catch (e) {
                    url = 'undefined';
                }
            } else if (urlType === 'page') {
                let pageSelect = form.querySelector("select[name='url_page']");
                let pageValue = pageSelect ? pageSelect.value : '';
                url = '{"type":"page","id":"' + pageValue + '"}';
            } else {
                url = 'undefined';
            }
        } else {
            let nameInputs = form.querySelectorAll('.name_of_nav');
            let urlInputs = form.querySelectorAll('.url_of_nav');
            url = {};
            for (let i = 0; i < nameInputs.length; i++) {
                let l = nameInputs[i].value;
                let p = urlInputs[i] ? urlInputs[i].value : '';
                if (l !== '') {
                    url[l] = p;
                }
            }
        }

        let newTabInput = form.querySelector('input[name="new_tab"]');
        let openNewTab = newTabInput ? newTabInput.checked : false;

        let inputs = {};
        inputs.name = name;
        inputs.icon = icon;
        inputs.type = type;
        inputs.url = url;
        inputs.open_new_tab = openNewTab;

        return inputs;
    }
</script>
