<section class="content">
    <div class="container-fluid">

        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">
                            <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-users mr-2"></i><?= __('USER__LIST') ?>
                                </h3>

                                <?php if ($type == '0') { ?>
                                    <span id="users-total" class="badge badge-light border"></span>
                                <?php } else { ?>
                                    <span class="badge badge-info">
                                        <i class="fas fa-search mr-1"></i><?= __('GLOBAL__SEARCH') ?>
                                    </span>
                                <?php } ?>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <?php if ($type == '0') { ?>

                                <div class="d-md-flex justify-content-between align-items-center col-12 dt-layout-full col-md">
                                    <table class="table table-responsive-sm table-bordered dataTable"
                                           style="table-layout: fixed;word-wrap: break-word;"
                                           id="users">
                                        <thead>
                                        <tr>
                                            <th scope="col"><?= __('USER__TITLE') ?></th>
                                            <th scope="col"><?= __('USER__EMAIL') ?></th>
                                            <th scope="col"><?= __('GLOBAL__CREATED') ?></th>
                                            <th scope="col"><?= __('USER__RANK') ?></th>
                                            <th scope="col" class="right"><?= __('GLOBAL__ACTIONS') ?></th>
                                        </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>

                            <?php } else { ?>

                                <?= $this->Form->create(null, [
                                    'url' => ['_name' => 'admin_user_live_search'],
                                    'method' => 'get',
                                ]) ?>

                                <div class="callout callout-info mb-3">
                                    <div class="d-flex align-items-start">
                                        <i class="fas fa-info-circle mt-1 mr-2"></i>
                                        <div class="text-sm mb-0"><?= __('USER__SEARCH_HINT') ?></div>
                                    </div>
                                </div>

                                <div class="form-group mb-0">
                                    <label for="user-search" class="mb-1"><?= __('GLOBAL__SEARCH') ?></label>

                                    <div class="input-group">
                                        <div class="input-group-prepend">
                                            <span class="input-group-text bg-white">
                                                <i class="fas fa-user text-muted"></i>
                                            </span>
                                        </div>

                                        <input
                                            id="user-search"
                                            type="text"
                                            name="search"
                                            placeholder="username..."
                                            autocomplete="off"
                                            class="form-control"
                                        >

                                        <div class="input-group-append">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-arrow-right"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="list-group mt-2" style="display:none;"></div>
                                </div>

                                <?= $this->Form->end() ?>

                            <?php } ?>

                        </div>
                    </div>
                </div>

            </div>
        </div>

    </div>
</section>

<script type="text/javascript">
    <?php if ($type == '0') { ?>
    document.addEventListener("DOMContentLoaded", function () {
        if (typeof DataTable === "undefined") {
            return;
        }

        const table = new DataTable("#users", {
            paging: true,
            lengthChange: false,
            searching: true,
            ordering: false,
            info: false,
            autoWidth: false,
            processing: true,
            serverSide: true,
            ajax: "<?= $this->Url->build(['_name' => 'admin_user_get_users']) ?>",
            columns: [
                {data: "Users.username", searchable: true},
                {data: "Users.email", searchable: true},
                {data: "Users.created_at", searchable: true},
                {data: "Users.rank", searchable: false},
                {data: "actions", searchable: false}
            ]
        });

        table.on('xhr', function () {
            const json = table.ajax.json();
            if (!json) return;

            const total = json.recordsTotal ?? 0;
            const filtered = json.recordsFiltered ?? total;

            const badge = document.getElementById('users-total');
            if (badge) {
                badge.textContent = filtered + " / " + total + " <?= h((string)__('TABLE__ITEMS')) ?>";
            }
        });
    });
    <?php } else { ?>
    document.addEventListener("DOMContentLoaded", function () {
        let forms = document.querySelectorAll('form[method="get"]');

        forms.forEach(function (form) {
            let searchInput = form.querySelector('input[name="search"]');
            let listGroup = form.querySelector('.list-group');
            let baseUrl = form.getAttribute('action');

            if (!searchInput) {
                return;
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                let val = searchInput.value || "";
                if (!val) {
                    return;
                }
                window.location.href = "<?= $this->Url->build(['_name' => 'admin_user_edit']) ?>/" + encodeURIComponent(val);
            });

            if (!listGroup) {
                return;
            }

            searchInput.addEventListener('keyup', function () {
                let value = searchInput.value || "";

                fetch(baseUrl + '/' + encodeURIComponent(value), {
                    method: 'GET',
                    headers: {'Accept': 'application/json'}
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        listGroup.innerHTML = "";

                        if (data.status) {
                            let users = data.data || [];

                            users.forEach(function (user) {
                                let link = document.createElement('a');
                                link.href = "<?= $this->Url->build(['_name' => 'admin_user_edit']) ?>/" + encodeURIComponent(user.id);
                                link.className = 'list-group-item list-group-item-action';
                                link.innerHTML = '<i class="fas fa-user mr-2 text-muted"></i>' + String(user.username || '');
                                listGroup.prepend(link);
                            });

                            listGroup.style.display = 'block';
                        } else {
                            listGroup.style.display = 'none';
                        }
                    })
                    .catch(function () {
                        listGroup.style.display = 'none';
                    });
            });
        });
    });
    <?php } ?>
</script>
