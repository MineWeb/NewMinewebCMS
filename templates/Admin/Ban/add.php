<?php

use Cake\Routing\Router;

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
                          data-redirect-url="<?= Router::url(['controller' => 'ban', 'action' => 'index', 'admin' => 'true']) ?>">
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
                                <input type="text" class="form-control"
                                       name="reason">
                            </div>
                        </div>

                        <div class="float-right">
                            <a href="<?= Router::url(['controller' => 'ban', 'action' => 'index', 'admin' => true]) ?>"
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
    <?php if($type == '0') { ?>
    $(document).ready(function () {
        $('#users').DataTable({
            "paging": true,
            "lengthChange": false,
            "searching": false,
            "ordering": false,
            "info": false,
            "autoWidth": false,
            'searching': true,
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "<?= Router::url(['action' => 'get_users_not_ban', 'admin' => true]) ?>",
            "aoColumns": [
                {mData: "User.ban", "bSearchable": false},
                {mData: "User.pseudo", "bSearchable": true},
                {mData: "User.rank", "bSearchable": false},
                {mData: "User.ip", "bSearchable": true},
                {mData: "User.banIp", "bSearchable": false}
            ]
        });
    });
    <?php } else { ?>
    $('form[method="search"]').each(function (e) {

        $(this).on('submit', function (e) {
            e.preventDefault();
            var val = $(this).find('input[name="search"]').val();
            window.location = '<?= Router::url(['action' => 'edit']) ?>/' + val;
        });

        var url = $(this).attr('action');
        var form = $(this);

        $(this).find('input[name="search"]').keyup(function (e) {

            var value = $(this).val();

            $.ajax({
                url: url + '/' + encodeURI(value),
                method: 'GET',
                dataType: 'JSON',
                success: function (data) {

                    form.find('.list-group').empty();

                    if (data.status) {

                        var users = data.data;

                        for (var i = 0; i < users.length; i++) {

                            console.log(users[i]);

                            form.find('.list-group').prepend('<a href="<?= Router::url(['action' => 'edit']) ?>/' + users[i]['id'] + '" class="list-group-item">' + users[i]['pseudo'] + '</a>')

                        }

                        form.find('.list-group').slideDown(250);

                    } else {
                        form.find('.list-group').slideUp(250);
                    }

                },
                error: function (data) {
                    form.find('.list-group').slideUp(250);
                }
            })

        });
    });
    <?php } ?>
</script>
