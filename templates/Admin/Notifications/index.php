<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NOTIFICATION__ADD_NOTIFICATION') ?></h3>
                </div>
                <div class="card-body">

                    <form action="<?= Router::url(['action' => 'setTo', 'admin' => true]) ?>" method="post" data-ajax="true"
                          data-callback-function="afterSendNotification">

                        <div class="form-group">
                            <label><?= __('NOTIFICATION__CONTENT') ?></label>
                            <textarea class="form-control" name="content" maxlength="255"></textarea>
                        </div>

                        <div class="form-group">
                            <div class="checkbox">
                                <input name="from" type="checkbox">
                                <label><?= __('NOTIFICATION__DISPLAY_FROM') ?></label>
                            </div>
                        </div>

                        <div class="form-group">
                            <label><?= __('NOTIFICATION__WHO') ?></label>
                            <select class="form-control" name="user_id">
                                <option value="all"><?= __('NOTIFICATION__ALL') ?></option>
                                <option value="user"><?= __('NOTIFICATION__USER') ?></option>
                            </select>
                        </div>

                        <script type="text/javascript">
                            $('select[name="user_id"]').on('change', function (e) {
                                if ($(this).val() == 'all') {
                                    $('#userInput').slideUp();
                                } else {
                                    $('#userInput').slideDown();
                                }
                            });
                        </script>

                        <div class="form-group" style="display:none;" id="userInput">
                            <label><?= __('NOTIFICATION__WHO_USERNAME') ?></label>
                            <input type="text" name="user_pseudo" class="form-control">
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-info"><?= __('GLOBAL__SUBMIT') ?></button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NOTIFICATION__OTHER_ACTIONS') ?></h3>
                </div>
                <div class="card-body">

                    <a href="<?= Router::url(['action' => 'clearAllFromAllUsers', 'admin' => true]) ?>"
                       class="btn btn-danger btn-block"
                       id="delete-all"><?= __('NOTIFICATION__DELETE_ALL_FROM_ALL_USERS') ?></a>
                    <a href="<?= Router::url(['action' => 'markAllAsSeenFromAllUsers', 'admin' => true]) ?>"
                       class="btn btn-default btn-block"
                       id="mark-all-as-seen"><?= __('NOTIFICATION__MARK_ALL_AS_SEEN_FROM_ALL_USERS') ?></a>

                    <hr>

                    <form method="post" action="<?= Router::url(['action' => 'clearAllFromGroup', 'admin' => true]) ?>"
                          data-ajax="true" data-callback-function="afterSendNotification">
                        <div class="input-group">
                            <div class="input-group mb-3">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">#</span>
                                </div>
                                <input type="text" class="form-control" name="group"
                                       placeholder="<?= __('NOTIFICATION__DELETE_ALL_FROM_GROUP_INPUT') ?>">
                                <div class="input-group-append">
                                    <button class="btn btn-danger"><?= __('NOTIFICATION__DELETE_ALL_FROM_GROUP_BTN') ?></button>
                                </div>
                            </div>
                            </span>
                        </div>
                    </form>

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
    $(document).ready(function () {
        $('table').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": true,
            "ordering": false,
            "info": false,
            "autoWidth": false,
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "<?= Router::url(['action' => 'getAll', 'admin' => true]) ?>",
            "aoColumns": [
                {mData: "User.pseudo"},
                {mData: "Notification.group"},
                {mData: "Notification.from", "bSearchable": false},
                {mData: "Notification.content"},
                {mData: "Notification.type", "bSearchable": false},
                {mData: "Notification.created"},
                {mData: "Notification.actions", "bSearchable": false}
            ],
        });

        var table = $('table').DataTable();

        $('table tbody').on('click', '.delete-notification', function (e) {

            e.preventDefault();

            var notification = $(this);
            var url = notification.attr('href');

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'JSON',
                success: function (data) {
                    if (data.status) {
                        table
                            .row(notification.parents('tr'))
                            .remove()
                            .draw();
                    } else {
                        alert('Error!');
                        console.log(data);
                    }
                },
                error: function () {
                    alert('Error!');
                }
            });

        });

        $('table tbody').on('click', '.mark-as-seen', function (e) {

            e.preventDefault();

            var btn = $(this);
            var url = btn.attr('href');

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'JSON',
                success: function (data) {
                    if (data.status) {
                        btn.addClass('disabled').addClass('active').attr('disabled', true).attr('href', '#').html(btn.attr('data-seen'));
                    } else {
                        alert('Error!');
                        console.log(data);
                    }
                },
                error: function () {
                    alert('Error!');
                }
            });

        });

        $('#delete-all').on('click', function (e) {
            e.preventDefault();

            var btn = $(this);
            var url = btn.attr('href');

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'JSON',
                success: function (data) {
                    if (data.status) {
                        table.ajax.reload();
                    } else {
                        alert('Error!');
                        console.log(data);
                    }
                },
                error: function () {
                    alert('Error!');
                }
            });

        });

        $('#mark-all-as-seen').on('click', function (e) {
            e.preventDefault();

            var btn = $(this);
            var url = btn.attr('href');

            $.ajax({
                url: url,
                method: 'GET',
                dataType: 'JSON',
                success: function (data) {
                    if (data.status) {
                        table.ajax.reload();
                    } else {
                        alert('Error!');
                        console.log(data);
                    }
                },
                error: function () {
                    alert('Error!');
                }
            });

        });

    });

    function afterSendNotification() {
        var table = $('table').DataTable();
        table.ajax.reload();
    }
</script>
