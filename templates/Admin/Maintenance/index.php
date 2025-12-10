<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('MAINTENANCE__TITLE') ?></h3>
                </div>
                <div class="card-body">
                    <a class="btn btn-large btn-block btn-primary" href="<?= Router::url(['controller' => 'maintenance', 'action' => 'add', 'admin' => true]) ?>"><?= __('MAINTENANCE__ADD_PAGE') ?></a>
                    <hr>
                    <table class="table table-responsive-sm table-bordered">
                        <thead>
                        <tr>
                            <th><?= __("MAINTENANCE__PAGE") ?></th>
                            <th><?= __("MAINTENANCE__REASON") ?></th>
                            <th><?= __("MAINTENANCE__SUB_URL") ?></th>
                            <th><?= __("GLOBAL__STATUS") ?></th>
                            <th><?= __("GLOBAL__ACTIONS") ?></th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($pages as $v) { ?>
                            <tr>
                                <td><?= $v["url"] ?></td>
                                <td><?= $v["reason"] ?></td>
                                <td><?= $v["sub_url"] ? __("GLOBAL__YES") : __("GLOBAL__NO") ?></td>
                                <td><?= $v["active"] != 1 ? __("GLOBAL__DISABLED") : __("GLOBAL__ENABLED") ?></td>
                                <td>
                                    <a href='<?= Router::url(['action' => 'edit', $v['id'], 'admin' => true]) ?>'
                                       class="btn btn-success"><?= __('GLOBAL__EDIT') ?></a>
                                    <?php if ($v["active"] == 1) { ?>
                                        <a onClick="confirmDel('<?= Router::url(['action' => 'disable', $v['id'], 'admin' => true]) ?>')"
                                           class="btn btn-warning"><?= __('GLOBAL__DISABLE') ?></a>
                                    <?php } else { ?>
                                        <a onClick="confirmDel('<?= Router::url(['action' => 'enable', $v['id'], 'admin' => true]) ?>')"
                                           class="btn btn-primary"><?= __('GLOBAL__ENABLE') ?></a>
                                    <?php } ?>
                                    <a onClick="confirmDel('<?= Router::url(['action' => 'delete', $v['id'], 'admin' => true]) ?>')"
                                       class="btn btn-danger"><?= __('GLOBAL__DELETE') ?></a>
                                </td>
                            </tr>
                        <?php } ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</section>
