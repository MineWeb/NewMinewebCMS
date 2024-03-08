<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= $Lang->get('MAINTENANCE__TITLE') ?></h3>
                </div>
                <div class="card-body">
                    <a class="btn btn-large btn-block btn-primary" href="<?= Router::url(['controller' => 'maintenance', 'action' => 'add', 'admin' => true]) ?>"><?= $Lang->get('MAINTENANCE__ADD_PAGE') ?></a>
                    <hr>
                    <table class="table table-responsive-sm table-bordered">
                        <thead>
                        <tr>
                            <th><?= $Lang->get("MAINTENANCE__PAGE") ?></th>
                            <th><?= $Lang->get("MAINTENANCE__REASON") ?></th>
                            <th><?= $Lang->get("MAINTENANCE__SUB_URL") ?></th>
                            <th><?= $Lang->get("GLOBAL__STATUS") ?></th>
                            <th><?= $Lang->get("GLOBAL__ACTIONS") ?></th>
                        </tr>
                        </thead>

                        <tbody>
                        <?php foreach ($pages as $v) { ?>
                            <tr>
                                <td><?= $v["url"] ?></td>
                                <td><?= $v["reason"] ?></td>
                                <td><?= $v["sub_url"] ? $Lang->get("GLOBAL__YES") : $Lang->get("GLOBAL__NO") ?></td>
                                <td><?= $v["active"] != 1 ? $Lang->get("GLOBAL__DISABLED") : $Lang->get("GLOBAL__ENABLED") ?></td>
                                <td>
                                    <a href='<?= Router::url(['action' => 'edit', $v['id'], 'admin' => true]) ?>'
                                       class="btn btn-success"><?= $Lang->get('GLOBAL__EDIT') ?></a>
                                    <?php if ($v["active"] == 1) { ?>
                                        <a onClick="confirmDel('<?= Router::url(['action' => 'disable', $v['id'], 'admin' => true]) ?>')"
                                           class="btn btn-warning"><?= $Lang->get('GLOBAL__DISABLE') ?></a>
                                    <?php } else { ?>
                                        <a onClick="confirmDel('<?= Router::url(['action' => 'enable', $v['id'], 'admin' => true]) ?>')"
                                           class="btn btn-primary"><?= $Lang->get('GLOBAL__ENABLE') ?></a>
                                    <?php } ?>
                                    <a onClick="confirmDel('<?= Router::url(['action' => 'delete', $v['id'], 'admin' => true]) ?>')"
                                       class="btn btn-danger"><?= $Lang->get('GLOBAL__DELETE') ?></a>
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
