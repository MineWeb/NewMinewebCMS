<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('PERMISSIONS__LABEL') ?></h3>
                </div>
                <div class="card-body">

                    <button data-toggle="modal" data-target="#addRank"
                            class="btn btn-block btn-success"><?= __('USER__RANK_ADD') ?></button>

                    <hr>

                    <?= $this->Form->create(null, [
                        'method' => 'post',
                        'url' => ['_name' => 'admin_permissions_index']
                    ]) ?>

                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th><?= __('PERMISSIONS__LABEL') ?></th>
                            <?php
                            if (!empty($all_ranks)) {
                                foreach ($all_ranks as $k => $data) {
                                    echo '<th>' . $data['name'] . '</th>';
                                }
                            }
                            ?>
                            <th><?= __('USER__RANK_ADMINISTRATOR') ?></th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($permissions as $permission => $ranks) { ?>
                            <tr>
                                <td><?= __('PERMISSIONS__' . $permission) ?></td>

                                <?php if (!empty($all_ranks)) { ?>
                                    <?php foreach ($all_ranks as $k => $data) { ?>
                                        <td>
                                            <input
                                                type="checkbox"
                                                name="<?= $permission ?>-<?= $data['rank_id'] ?>"
                                                <?= isset($ranks[$data['rank_id']]) && $ranks[$data['rank_id']] ? 'checked="checked"' : '' ?>
                                            >
                                        </td>
                                    <?php } ?>
                                <?php } ?>

                                <td><input type="checkbox" checked="checked" disabled="disabled"></td>
                            </tr>
                        <?php } ?>

                        <tr>
                            <td></td>
                            <td></td>
                            <td></td>

                            <?php
                            if (!empty($all_ranks)) {
                                foreach ($all_ranks as $k => $data) {
                                    if ($data['rank_id'] < 5) {
                                        continue;
                                    }

                                    echo '<td><a class="btn btn-danger" href="'
                                        . $this->Url->build(['_name' => 'admin_permissions_delete_rank', $data['rank_id']])
                                        . '">' . __('GLOBAL__DELETE') . '</a></td>';
                                }
                            }
                            ?>
                        </tr>

                        </tbody>
                    </table>

                    <button class="btn btn-primary" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>

                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>
    </div>
</section>

<div class="modal fade" id="addRank" tabindex="-1" role="dialog" aria-labelledby="addRankLabel" aria-hidden="true"
     style="display:none;">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h4 class="modal-title" id="myModalLabel"><?= __('USER__RANK_ADD') ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-hidden="true">&times;</button>
            </div>

            <div class="modal-body">
                <?= $this->Form->create(null, [
                    'url' => ['_name' => 'admin_permissions_add_rank'],
                    'method' => 'post',
                    'data-ajax' => 'true',
                    'data-redirect-url' => '#'
                ]) ?>

                <div class="ajax-msg"></div>
                <div class="input-group">
                    <input
                        type="text"
                        class="form-control"
                        name="name"
                        placeholder="<?= __('GLOBAL__NAME') ?>"
                    >
                    <span class="input-group-btn">
                            <button class="btn btn-info btn-flat" type="submit"><?= __('GLOBAL__SUBMIT') ?></button>
                        </span>
                </div>

                <?= $this->Form->end() ?>
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-default"
                        data-dismiss="modal">
                    <?= __('GLOBAL__CANCEL') ?>
                </button>
            </div>

        </div>
    </div>
</div>
