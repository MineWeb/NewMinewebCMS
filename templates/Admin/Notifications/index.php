<section class="content">
    <div class="row">

        <div class="col-md-6">
            <div class="card card-outline card-info">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-bell mr-2"></i><?= __('NOTIFICATION__ADD_NOTIFICATION') ?>
                    </h3>
                </div>
                <div class="card-body">

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_notifications_set_to'],
                        'method' => 'post',
                        'data-ajax' => 'true',
                        'data-callback-function' => 'afterSendNotification',
                    ]) ?>

                    <div class="form-group">
                        <label for="notification-content"><?= __('NOTIFICATION__CONTENT') ?></label>
                        <textarea
                            class="form-control"
                            id="notification-content"
                            name="content"
                            maxlength="255"
                            rows="4"
                        ></textarea>
                        <small class="form-text text-muted">255</small>
                    </div>

                    <div class="form-group mb-4">
                        <div class="custom-control custom-switch">
                            <input id="notification-from" name="from" type="checkbox" class="custom-control-input">
                            <label class="custom-control-label" for="notification-from">
                                <?= __('NOTIFICATION__DISPLAY_FROM') ?>
                            </label>
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

                    <div class="form-group mb-0">
                        <button type="submit" class="btn btn-info">
                            <i class="fas fa-paper-plane mr-1"></i><?= __('GLOBAL__SUBMIT') ?>
                        </button>
                    </div>

                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card card-outline card-secondary">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-tools mr-2"></i><?= __('NOTIFICATION__OTHER_ACTIONS') ?>
                    </h3>
                </div>

                <div class="card-body">

                    <?php
                    $deleteAllTitle = __('NOTIFICATION__DELETE_ALL_FROM_ALL_USERS');
                    $markAllTitle = __('NOTIFICATION__MARK_ALL_AS_SEEN_FROM_ALL_USERS');

                    $deleteAllShort = __('NOTIFICATION__DELETE_ALL_SHORT');
                    $markAllShort = __('NOTIFICATION__MARK_ALL_SEEN_SHORT');
                    ?>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <div class="card bg-danger mb-0" style="min-height: 130px;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-start">
                                        <div class="mr-3" style="font-size:30px;line-height:1;">
                                            <i class="fas fa-trash"></i>
                                        </div>
                                        <div class="flex-grow-1" style="min-width:0;">
                                            <div class="text-truncate">
                                                <strong><?= h($deleteAllShort) ?></strong>
                                            </div>
                                            <div class="text-truncate" style="opacity:.9;">
                                                <small><?= h($deleteAllTitle) ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <a
                                            href="<?= $this->Url->build(['_name' => 'admin_notifications_clear_all_from_all_users']) ?>"
                                            class="btn btn-block btn-light btn-sm"
                                            id="delete-all"
                                        >
                                            <i class="fas fa-trash mr-1"></i><?= __('GLOBAL__DELETE') ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-3">
                            <div class="card bg-secondary mb-0" style="min-height: 130px;">
                                <div class="card-body p-3">
                                    <div class="d-flex align-items-start">
                                        <div class="mr-3" style="font-size:30px;line-height:1;">
                                            <i class="fas fa-check-double"></i>
                                        </div>
                                        <div class="flex-grow-1" style="min-width:0;">
                                            <div class="text-truncate">
                                                <strong><?= h($markAllShort) ?></strong>
                                            </div>
                                            <div class="text-truncate" style="opacity:.9;">
                                                <small><?= h($markAllTitle) ?></small>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="mt-3">
                                        <a
                                            href="<?= $this->Url->build(['_name' => 'admin_notifications_mark_all_as_seen_from_all_users']) ?>"
                                            class="btn btn-block btn-light btn-sm"
                                            id="mark-all-as-seen"
                                        >
                                            <i class="fas fa-check mr-1"></i><?= __('NOTIFICATION__MARK_AS_SEEN') ?>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <hr class="mt-0">

                    <div class="mb-2">
                        <strong><?= __('NOTIFICATION__GROUP_ID_LABEL') ?></strong>
                    </div>

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_notifications_clear_all_from_group'],
                        'method' => 'post',
                        'data-ajax' => 'true',
                        'data-callback-function' => 'afterSendNotification',
                    ]) ?>

                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-hashtag"></i></span>
                        </div>
                        <input
                            type="text"
                            class="form-control"
                            id="notification-group"
                            name="group"
                            placeholder="<?= __('NOTIFICATION__GROUP_ID_PLACEHOLDER') ?>"
                        >
                        <div class="input-group-append">
                            <button class="btn btn-danger" type="submit">
                                <i class="fas fa-trash-alt mr-1"></i><?= __('GLOBAL__DELETE') ?>
                            </button>
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
                            <th><?= __('NOTIFICATION__GROUP_ID') ?></th>
                            <th><?= __('NOTIFICATION__FROM') ?></th>
                            <th><?= __('NOTIFICATION__CONTENT') ?></th>
                            <th><?= __('NOTIFICATION__TYPE') ?></th>
                            <th><?= __('GLOBAL__CREATED') ?></th>
                            <th style="width: 240px;"><?= __('GLOBAL__ACTIONS') ?></th>
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

        let textarea = document.getElementById('notification-content');
        if (textarea) {
            let helper = textarea.parentElement ? textarea.parentElement.querySelector('small.form-text') : null;
            let max = parseInt(textarea.getAttribute('maxlength') || '255', 10);

            function updateCount() {
                let left = max - (textarea.value ? textarea.value.length : 0);
                if (helper) {
                    helper.textContent = String(left);
                }
            }

            textarea.addEventListener('input', updateCount);
            updateCount();
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
                { data: "Users.username" },
                { data: "Notifications.group" },
                { data: "Notifications.from", searchable: false },
                { data: "Notifications.content" },
                { data: "Notifications.type", searchable: false },
                { data: "Notifications.created_at" },
                { data: "Notifications.actions", searchable: false }
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
