<?php

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('USER__LIST') ?></h3>
                </div>
                <div class="card-body">
                    <?php if ($type == '0') { ?>
                        <table class="table table-responsive-sm table-bordered"
                               style="table-layout: fixed;word-wrap: break-word;" id="users">
                            <thead>
                            <tr>
                                <th scope="col"><?= __('USER__TITLE') ?></th>
                                <th scope="col"><?= __('USER__EMAIL') ?></th>
                                <th scope="col"><?= __('GLOBAL__CREATED') ?></th>
                                <th scope="col"><?= __('USER__RANK') ?></th>
                                <th scope="col" class="right"><?= __('GLOBAL__ACTIONS') ?></th>
                            </tr>
                            </thead>
                            <tbody>
                            </tbody>
                        </table>
                    <?php } else { ?>
                        <form action="<?= $this->Url->build(['_name' => 'admin_user_live_search']) ?>" method="search">
                            <div class="form-group">
                                <label for="user-search"><?= __('GLOBAL__SEARCH') ?></label>
                                <input id="user-search" type="text" name="search" placeholder="username..." autocomplete="off"
                                       class="form-control">
                                <div class="list-group" style="display:none;"></div>
                            </div>
                        </form>
                    <?php } ?>
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

        new DataTable("#users", {
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
                {data: "User.username", searchable: true},
                {data: "User.email", searchable: true},
                {data: "User.created", searchable: true},
                {data: "User.rank", searchable: false},
                {data: "actions", searchable: false}
            ]
        });
    });
    <?php } else { ?>
    document.addEventListener("DOMContentLoaded", function () {
        let forms = document.querySelectorAll('form[method="search"]');

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
                    headers: {
                        'Accept': 'application/json'
                    }
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
                                link.className = 'list-group-item';
                                link.textContent = user.username;
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
