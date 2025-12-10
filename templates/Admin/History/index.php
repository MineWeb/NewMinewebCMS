<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('HISTORY__VIEW_GLOBAL') ?></h3>
                </div>
                <div class="card-body">
                    <table class="table table-bordered" style="table-layout: fixed;word-wrap: break-word;">
                        <thead>
                        <tr>
                            <th><?= __('USER__USERNAME') ?></th>
                            <th><?= __('GLOBAL__ACTIONS') ?></th>
                            <th><?= __('GLOBAL__CATEGORY') ?></th>
                            <th><?= __('GLOBAL__CREATED') ?></th>
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
            "ordering": false,
            "info": false,
            "autoWidth": false,
            'searching': true,
            "bProcessing": true,
            "bServerSide": true,
            "sAjaxSource": "<?= Router::url(['action' => 'getAll', 'admin' => true]) ?>",
            "aoColumns": [
                {mData: "User.pseudo"},
                {mData: "History.action"},
                {mData: "History.category"},
                {mData: "History.created"}
            ],
        });
    })
</script>
