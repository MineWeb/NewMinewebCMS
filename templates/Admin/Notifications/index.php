<section class="content">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NOTIFICATION__ADD_NOTIFICATION') ?></h3>
                </div>
                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_notifications_set_to'],
                        'method' => 'post',
                        'data-ajax' => 'true',
                        'data-callback-function' => 'afterSendNotification'
                    ]) ?>

                    <div class="form-group">
                        <label for="notification-content"><?= __('NOTIFICATION__CONTENT') ?></label>
                        <textarea
                            class="form-control"
                            id="notification-content"
                            name="content"
                            maxlength="255"
                        ></textarea>
                    </div>

                    <div class="form-group">
                        <div class="checkbox">
                            <input id="notification-from" name="from" type="checkbox">
                            <label for="notification-from"><?= __('NOTIFICATION__DISPLAY_FROM') ?></label>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="notification-user-id"><?= __('NOTIFICATION__WHO') ?></label>
                        <select
                            class="form-control"
                            id="notification-user-id"
                            name="user_id"
                        >
                            <option value="all"><?= __('NOTIFICATION__ALL') ?></option>
                            <option value="user"><?= __('NOTIFICATION__USER') ?></option>
                        </select>
                    </div>

                    <div class="form-group" style="display:none;" id="userInput">
                        <label for="notification-user-username"><?= __('NOTIFICATION__WHO_USERNAME') ?></label>
                        <input
                            type="text"
                            id="notification-user-username"
                            name="user_username"
                            class="form-control"
                        >
                    </div>

                    <div class="form-group">
                        <button type="submit" class="btn btn-info"><?= __('GLOBAL__SUBMIT') ?></button>
                    </div>

                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NOTIFICATION__OTHER_ACTIONS') ?></h3>
                </div>
                <div class="card-body">

                    <a href="<?= $this->Url->build(['_name' => 'admin_notifications_clear_all_from_all_users']) ?>"
                       class="btn btn-danger btn-block"
                       id="delete-all"><?= __('NOTIFICATION__DELETE_ALL_FROM_ALL_USERS') ?></a>
                    <a href="<?= $this->Url->build(['_name' => 'admin_notifications_mark_all_as_seen_from_all_users']) ?>"
                       class="btn btn-default btn-block"
                       id="mark-all-as-seen"><?= __('NOTIFICATION__MARK_ALL_AS_SEEN_FROM_ALL_USERS') ?></a>

                    <hr>

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_notifications_clear_all_from_group'],
                        'method' => 'post',
                        'data-ajax' => 'true',
                        'data-callback-function' => 'afterSendNotification'
                    ]) ?>
                    <div class="input-group">
                        <div class="input-group mb-3">
                            <div class="input-group-prepend">
                                <span class="input-group-text">#</span>
                            </div>
                            <input
                                type="text"
                                class="form-control"
                                name="group"
                                placeholder="<?= __('NOTIFICATION__DELETE_ALL_FROM_GROUP_INPUT') ?>"
                            >
                            <div class="input-group-append">
                                <button class="btn btn-danger">
                                    <?= __('NOTIFICATION__DELETE_ALL_FROM_GROUP_BTN') ?>
                                </button>
                            </div>
                        </div>
                    </div>
                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NOTIFICATION__NOTIFICATIONS_LIST') ?></h3>
                </div>
                <div class="card-body">
                    <table class="table table-responsive-sm table-bordered"
                           style="table-layout: fixed;word-wrap: break-word;">
                        <thead>
                        <tr>
                            <th><?= __('USER__USERNAME') ?></th>
                            <th><?= __('NOTIFICATION__GROUP') ?></th>
                            <th><?= __('NOTIFICATION__FROM') ?></th>
                            <th><?= __('NOTIFICATION__CONTENT') ?></th>
                            <th><?= __('NOTIFICATION__TYPE') ?></th>
                            <th><?= __('GLOBAL__CREATED') ?></th>
                            <th><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        let userSelect = document.getElementById('notification-user-id');
        let userInputDiv = document.getElementById('userInput');

        if (userSelect && userInputDiv) {
            function updateUserInputVisibility() {
                if (userSelect.value === 'user') {
                    userInputDiv.style.display = '';
                } else {
                    userInputDiv.style.display = 'none';
                }
            }

            userSelect.addEventListener('change', updateUserInputVisibility);
            updateUserInputVisibility();
        }

        let tableElement = document.querySelector('.card-body table.table-responsive-sm.table-bordered');
        if (!tableElement) {
            return;
        }

        let notificationsTable = new DataTable(tableElement, {
            paging: true,
            lengthChange: false,
            searching: true,
            ordering: false,
            info: false,
            autoWidth: false,
            processing: true,
            serverSide: true,
            ajax: "<?= $this->Url->build(['_name' => 'admin_notifications_get_all']) ?>",
            columns: [
                { data: "User.username" },
                { data: "Notification.group" },
                { data: "Notification.from", searchable: false },
                { data: "Notification.content" },
                { data: "Notification.type", searchable: false },
                { data: "Notification.created" },
                { data: "Notification.actions", searchable: false }
            ]
        });

        window.notificationsTable = notificationsTable;

        tableElement.addEventListener('click', function (e) {
            let deleteLink = e.target.closest('.delete-notification');
            let markSeenLink = e.target.closest('.mark-as-seen');

            if (deleteLink) {
                e.preventDefault();

                let url = deleteLink.getAttribute('href');
                if (!url) {
                    return;
                }

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (data && data.status) {
                            let rowElement = deleteLink.closest('tr');
                            if (rowElement) {
                                notificationsTable.row(rowElement).remove().draw();
                            }
                        } else {
                            alert('Error!');
                            if (window.console) {
                                console.log(data);
                            }
                        }
                    })
                    .catch(function () {
                        alert('Error!');
                    });

                return;
            }

            if (markSeenLink) {
                e.preventDefault();

                let urlSeen = markSeenLink.getAttribute('href');
                if (!urlSeen) {
                    return;
                }

                fetch(urlSeen, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (data && data.status) {
                            let seenText = markSeenLink.getAttribute('data-seen') || markSeenLink.textContent;
                            markSeenLink.classList.add('disabled');
                            markSeenLink.classList.add('active');
                            markSeenLink.setAttribute('disabled', 'disabled');
                            markSeenLink.setAttribute('href', '#');
                            markSeenLink.textContent = seenText;
                        } else {
                            alert('Error!');
                            if (window.console) {
                                console.log(data);
                            }
                        }
                    })
                    .catch(function () {
                        alert('Error!');
                    });

                return;
            }
        });

        let deleteAllBtn = document.getElementById('delete-all');
        if (deleteAllBtn) {
            deleteAllBtn.addEventListener('click', function (e) {
                e.preventDefault();

                let url = deleteAllBtn.getAttribute('href');
                if (!url) {
                    return;
                }

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (data && data.status) {
                            notificationsTable.ajax.reload();
                        } else {
                            alert('Error!');
                            if (window.console) {
                                console.log(data);
                            }
                        }
                    })
                    .catch(function () {
                        alert('Error!');
                    });
            });
        }

        let markAllSeenBtn = document.getElementById('mark-all-as-seen');
        if (markAllSeenBtn) {
            markAllSeenBtn.addEventListener('click', function (e) {
                e.preventDefault();

                let url = markAllSeenBtn.getAttribute('href');
                if (!url) {
                    return;
                }

                fetch(url, {
                    method: 'GET',
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest'
                    }
                })
                    .then(function (response) {
                        return response.json();
                    })
                    .then(function (data) {
                        if (data && data.status) {
                            notificationsTable.ajax.reload();
                        } else {
                            alert('Error!');
                            if (window.console) {
                                console.log(data);
                            }
                        }
                    })
                    .catch(function () {
                        alert('Error!');
                    });
            });
        }
    });

    function afterSendNotification() {
        if (window.notificationsTable) {
            window.notificationsTable.ajax.reload();
        }
    }
</script>
