<?php

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('BAN__HOME') ?></h3>
                </div>
                <div class="card-body">
                    <form method="post" data-ajax="true" data-upload-image="true"
                          data-redirect-url="<?= $this->Url->build(['_name' => 'admin_ban_index']) ?>">
                        <table class="table table-responsive-sm table-bordered"
                               style="table-layout: fixed;word-wrap: break-word;" id="users">
                            <thead>
                            <tr>
                                <th><?= __('BAN__QUESTION') ?></th>
                                <th><?= __('USER__TITLE') ?></th>
                                <th><?= __('USER__RANK') ?></th>
                                <th>IP</th>
                                <th><?= __('BAN__IP_QUESTION') ?></th>
                            </tr>
                            </thead>
                        </table>

                        <div class="col-sm-6">
                            <div class="form-group">
                                <label><?= __('BAN__REASON') ?></label>
                                <input type="text" class="form-control" name="reason">
                            </div>
                        </div>

                        <div class="float-right">
                            <a href="<?= $this->Url->build(['_name' => 'admin_ban_index']) ?>"
                               class="btn btn-default"><?= __('GLOBAL__CANCEL') ?></a>
                            <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                        </div>
                    </form>
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
            ajax: "<?= $this->Url->build(['_name' => 'admin_ban_get_users_not_ban']) ?>",
            columns: [
                {data: "User.ban", searchable: false},
                {data: "User.pseudo", searchable: true},
                {data: "User.rank", searchable: false},
                {data: "User.ip", searchable: true},
                {data: "User.banIp", searchable: false}
            ]
        });
    });
    <?php } else { ?>
    document.addEventListener("DOMContentLoaded", function () {
        var forms = document.querySelectorAll('form[method="search"]');

        forms.forEach(function (form) {
            var searchInput = form.querySelector('input[name="search"]');
            var listGroup = form.querySelector('.list-group');
            var baseUrl = form.getAttribute('action');

            if (!searchInput) {
                return;
            }

            form.addEventListener('submit', function (event) {
                event.preventDefault();
                var val = searchInput.value || "";
                if (!val) {
                    return;
                }
                window.location.href = "<?= $this->Url->build(['_name' => 'admin_ban_edit']) ?>/" + encodeURIComponent(val);
            });

            if (!listGroup) {
                return;
            }

            searchInput.addEventListener('keyup', function () {
                var value = searchInput.value || "";

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
                            var users = data.data || [];

                            users.forEach(function (user) {
                                var link = document.createElement('a');
                                link.href = "<?= $this->Url->build(['_name' => 'admin_ban_edit']) ?>/" + encodeURIComponent(user.id);
                                link.className = 'list-group-item';
                                link.textContent = user.pseudo;
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
