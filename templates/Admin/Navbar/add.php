<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <h3 class="card-title mb-0">
                            <i class="fas fa-plus mr-2"></i><?= __('NAVBAR__ADD_LINK') ?>
                        </h3>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <?= $this->Form->create(null, [
                                'method' => 'post',
                                'url' => ['_name' => 'admin_navbar_add_ajax'],
                                'data-ajax' => 'true',
                                'data-redirect-url' => $this->Url->build(['_name' => 'admin_navbar_index']),
                                'data-custom-function' => 'formatteData'
                            ]) ?>

                            <div class="ajax-msg"></div>

                            <div class="row">

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <label for="navbar-name" class="mb-1"><?= __('GLOBAL__NAME') ?></label>
                                                <input id="navbar-name" name="name" class="form-control" type="text" autocomplete="off">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="form-group mb-0">
                                                <div class="d-flex align-items-center justify-content-between">
                                                    <label for="navbar-icon" class="mb-1"><?= __('NAVBAR__ICON') ?></label>
                                                    <a class="btn btn-outline-secondary btn-xs" target="_blank" rel="noopener" href="https://fontawesome.com/">
                                                        <i class="fas fa-external-link-alt mr-1"></i>Font Awesome
                                                    </a>
                                                </div>

                                                <div class="text-muted text-sm mb-2">
                                                    <?= __('NAVBAR__ICON__DESC') ?>
                                                </div>

                                                <div class="input-group">
                                                    <div class="input-group-prepend">
                                                        <span class="input-group-text">FA</span>
                                                    </div>
                                                    <input id="navbar-icon" name="icon" class="form-control" type="text" autocomplete="off">
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <label class="mb-2"><?= __('GLOBAL__TYPE') ?></label>

                                            <div class="custom-control custom-radio">
                                                <input type="radio" id="normal" name="type" value="normal" class="custom-control-input">
                                                <label class="custom-control-label" for="normal"><?= __('NAVBAR__LINK_TYPE_DEFAULT') ?></label>
                                            </div>

                                            <div class="custom-control custom-radio mt-2">
                                                <input type="radio" id="dropdown" name="type" value="dropdown" class="custom-control-input">
                                                <label class="custom-control-label" for="dropdown"><?= __('NAVBAR__LINK_TYPE_DROPDOWN') ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div id="type-normal" class="d-none">
                                        <div class="card mb-3">
                                            <div class="card-body">

                                                <label class="mb-2"><?= __('URL') ?></label>

                                                <div class="custom-control custom-radio">
                                                    <input type="radio" id="url-type-plugin" class="custom-control-input type_plugin" name="url_type" value="plugin">
                                                    <label class="custom-control-label" for="url-type-plugin"><?= __('NAVBAR__LINK_TYPE_PLUGIN') ?></label>
                                                </div>

                                                <div class="mt-2 d-none plugin">
                                                    <select class="form-control" name="url_plugin">
                                                        <?php
                                                        foreach ($url_plugins as $pluginId => $data) {
                                                            echo '<option disabled>' . h($data->name) . '</option>';
                                                            foreach ($data->routes as $name => $route) {
                                                                $v = json_encode(['id' => $pluginId, 'route' => $route]);
                                                                echo '<option value=\'' . h($v) . '\'>' . h($name) . ' (' . h($route) . ')</option>';
                                                            }
                                                        }
                                                        ?>
                                                    </select>
                                                </div>

                                                <div class="custom-control custom-radio mt-3">
                                                    <input type="radio" id="url-type-page" class="custom-control-input type_page" name="url_type" value="page">
                                                    <label class="custom-control-label" for="url-type-page"><?= __('NAVBAR__LINK_TYPE_PAGE') ?></label>
                                                </div>

                                                <div class="mt-2 d-none page">
                                                    <select class="form-control" name="url_page">
                                                        <?php foreach ($url_pages as $key => $value) { ?>
                                                            <option value="<?= h((string)$key) ?>"><?= h((string)$value) ?></option>
                                                        <?php } ?>
                                                    </select>
                                                </div>

                                                <div class="custom-control custom-radio mt-3">
                                                    <input type="radio" id="url-type-custom" class="custom-control-input type_custom" name="url_type" value="custom">
                                                    <label class="custom-control-label" for="url-type-custom"><?= __('NAVBAR__LINK_TYPE_CUSTOM') ?></label>
                                                </div>

                                                <input
                                                    type="text"
                                                    class="form-control mt-2 d-none custom"
                                                    id="url-custom"
                                                    placeholder="<?= h(__('NAVBAR__CUSTOM_URL')) ?>"
                                                    name="url_custom"
                                                    autocomplete="off"
                                                >

                                            </div>
                                        </div>
                                    </div>

                                    <div id="type-dropdown" class="d-none">
                                        <div class="card mb-3">
                                            <div class="card-body">

                                                <div class="alert alert-light border d-flex align-items-start mb-3">
                                                    <i class="fas fa-layer-group mt-1 mr-2"></i>
                                                    <div class="text-sm mb-0"><?= __('NAVBAR__LINK_TYPE_DROPDOWN') ?></div>
                                                </div>

                                                <div class="form-group mb-0">
                                                    <div class="card card-body nav-item-block" id="nav-1">
                                                        <div class="form-group">
                                                            <label for="name-of-nav-1"><?= __('NAVBAR__LINK_NAME') ?></label>
                                                            <input type="text" class="form-control name_of_nav" id="name-of-nav-1" name="name_of_nav" autocomplete="off">
                                                        </div>
                                                        <div class="form-group mb-0">
                                                            <label for="url-of-nav-1"><?= __('URL') ?></label>
                                                            <input type="text" class="form-control url_of_nav" id="url-of-nav-1" placeholder="<?= h(__('NAVBAR__CUSTOM_URL')) ?>" name="url" autocomplete="off">
                                                        </div>

                                                        <div class="mt-3 text-right">
                                                            <a href="#" class="btn btn-danger btn-sm delete-nav">
                                                                <i class="fas fa-trash mr-1"></i><?= __('GLOBAL__DELETE') ?>
                                                            </a>
                                                        </div>
                                                    </div>
                                                </div>

                                                <div id="add-js" data-number="1"></div>

                                                <div class="mt-3">
                                                    <a href="#" id="add_nav" class="btn btn-success btn-sm">
                                                        <i class="fas fa-plus mr-1"></i><?= __('NAVBAR__ADD_LINK') ?>
                                                    </a>
                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="card mb-3">
                                        <div class="card-body">
                                            <div class="custom-control custom-switch">
                                                <input type="checkbox" class="custom-control-input" id="navbar-new-tab" name="new_tab">
                                                <label class="custom-control-label" for="navbar-new-tab"><?= __('NAVBAR__OPEN_IN_NEW_TAB') ?></label>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-12">
                                    <div class="d-flex justify-content-end">
                                        <a href="<?= $this->Url->build(['_name' => 'admin_navbar_index']) ?>" class="btn btn-default mr-2">
                                            <?= __('GLOBAL__CANCEL') ?>
                                        </a>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-save mr-2"></i><?= __('GLOBAL__SUBMIT') ?>
                                        </button>
                                    </div>
                                </div>

                            </div>

                            <?= $this->Form->end() ?>

                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</section>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        let normalRadio = document.getElementById('normal');
        let dropdownRadio = document.getElementById('dropdown');
        let typeNormalDiv = document.getElementById('type-normal');
        let typeDropdownDiv = document.getElementById('type-dropdown');

        function updateTypeVisibility() {
            if (normalRadio && normalRadio.checked) {
                if (typeNormalDiv) typeNormalDiv.classList.remove('d-none');
                if (typeDropdownDiv) typeDropdownDiv.classList.add('d-none');
            } else if (dropdownRadio && dropdownRadio.checked) {
                if (typeDropdownDiv) typeDropdownDiv.classList.remove('d-none');
                if (typeNormalDiv) typeNormalDiv.classList.add('d-none');
            } else {
                if (typeNormalDiv) typeNormalDiv.classList.add('d-none');
                if (typeDropdownDiv) typeDropdownDiv.classList.add('d-none');
            }
        }

        if (normalRadio) normalRadio.addEventListener('change', updateTypeVisibility);
        if (dropdownRadio) dropdownRadio.addEventListener('change', updateTypeVisibility);
        updateTypeVisibility();

        let typePlugin = document.querySelector('.type_plugin');
        let typePage = document.querySelector('.type_page');
        let typeCustom = document.querySelector('.type_custom');
        let pluginBlock = document.querySelector('.plugin');
        let pageBlock = document.querySelector('.page');
        let customInput = document.querySelector('.custom');

        function updateUrlType() {
            if (typePlugin && typePlugin.checked) {
                if (pluginBlock) pluginBlock.classList.remove('d-none');
                if (pageBlock) pageBlock.classList.add('d-none');
                if (customInput) customInput.classList.add('d-none');
            } else if (typePage && typePage.checked) {
                if (pluginBlock) pluginBlock.classList.add('d-none');
                if (pageBlock) pageBlock.classList.remove('d-none');
                if (customInput) customInput.classList.add('d-none');
            } else if (typeCustom && typeCustom.checked) {
                if (pluginBlock) pluginBlock.classList.add('d-none');
                if (pageBlock) pageBlock.classList.add('d-none');
                if (customInput) customInput.classList.remove('d-none');
            } else {
                if (pluginBlock) pluginBlock.classList.add('d-none');
                if (pageBlock) pageBlock.classList.add('d-none');
                if (customInput) customInput.classList.add('d-none');
            }
        }

        if (typePlugin) typePlugin.addEventListener('change', updateUrlType);
        if (typePage) typePage.addEventListener('change', updateUrlType);
        if (typeCustom) typeCustom.addEventListener('change', updateUrlType);
        updateUrlType();

        let addNavBtn = document.getElementById('add_nav');
        let addJsContainer = document.getElementById('add-js');

        if (addNavBtn && addJsContainer) {
            let how = parseInt(addJsContainer.getAttribute('data-number') || '1', 10);

            addNavBtn.addEventListener('click', function (e) {
                e.preventDefault();
                how += 1;
                addJsContainer.setAttribute('data-number', String(how));

                let nameId = 'name-of-nav-' + how;
                let urlId = 'url-of-nav-' + how;

                let wrapper = document.createElement('div');
                wrapper.className = 'form-group';
                wrapper.innerHTML =
                    '<div class="card card-body nav-item-block" id="nav-' + how + '">' +
                    '<div class="form-group">' +
                    '<label for="' + nameId + '"><?= addslashes(__('NAVBAR__LINK_NAME')) ?></label>' +
                    '<input type="text" class="form-control name_of_nav" id="' + nameId + '" name="name_of_nav" autocomplete="off">' +
                    '</div>' +
                    '<div class="form-group mb-0">' +
                    '<label for="' + urlId + '"><?= addslashes(__('URL')) ?></label>' +
                    '<input type="text" class="form-control url_of_nav" id="' + urlId + '" placeholder="<?= addslashes(__('NAVBAR__CUSTOM_URL')) ?>" name="url" autocomplete="off">' +
                    '</div>' +
                    '<div class="mt-3 text-right">' +
                    '<a href="#" class="btn btn-danger btn-sm delete-nav"><i class="fas fa-trash mr-1"></i><?= addslashes(__('GLOBAL__DELETE')) ?></a>' +
                    '</div>' +
                    '</div>';

                addJsContainer.appendChild(wrapper);
            });
        }

        document.addEventListener('click', function (e) {
            let target = e.target;
            let btn = target && target.closest ? target.closest('.delete-nav') : null;
            if (btn) {
                e.preventDefault();
                let block = btn.closest('.nav-item-block');
                if (block) block.remove();
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
                    url = JSON.stringify({ type: 'plugin', id: parsed.id, route: parsed.route });
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
