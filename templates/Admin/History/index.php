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
                        <tbody></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    document.addEventListener('DOMContentLoaded', function () {
        let table = document.querySelector('table');
        if (!table) {
            return;
        }

        new DataTable(table, {
            paging: true,
            lengthChange: false,
            ordering: false,
            info: false,
            autoWidth: false,
            searching: true,
            processing: true,
            serverSide: true,
            ajax: "<?= $this->Url->build(['_name' => 'admin_history_get_all']) ?>",
            columns: [
                { data: "Users.username" },
                { data: "Histories.action" },
                { data: "Histories.category" },
                { data: "Histories.created_at" }
            ]
        });
    });
</script>
