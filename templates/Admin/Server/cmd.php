<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= $Lang->get('SERVER__CMD_TITLE') ?> &nbsp;&nbsp;<button
                                data-toggle="modal"
                                data-target="#executeCommand"
                                class="btn btn-success"><?= $Lang->get('GLOBAL__ADD') ?></button>
                    </h3>
                </div>
                <div class="card-body">

                    <table class="table table-bordered dataTable">
                        <thead>
                        <tr>
                            <th><?= $Lang->get('SERVER__CMD_NAME') ?></th>
                            <th><?= $Lang->get('SERVER__COMMAND') ?></th>
                            <th><?= $Lang->get('SERVER__TITLE') ?></th>
                            <th class="right"><?= $Lang->get('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($search_cmd as $c) { ?>
                            <tr>
                                <td><?= $c['name'] ?></td>
                                <td><?= $c['cmd'] ?></td>
                                <td><?php foreach ($search_server as $d) {
                                        if ($c['server_id'] == $d['id']) echo $d['name'];
                                    } ?></td>
                                <td class="right">
                                    <form method="post" action="<?= Router::url(['action' => 'execute_cmd', 'admin' => true]) ?>"
                                          data-ajax="true"
                                          data-redirect-url="<?= Router::url(['action' => 'cmd', 'admin' => true]) ?>">
                                        <input type="hidden" name="cmd" value="<?= $c['cmd'] ?>">
                                        <input type="hidden" name="server_id"
                                               value="<?= $c['server_id'] ?>">
                                        <button class="btn btn-primary"
                                                type="submit"><?= $Lang->get('GLOBAL__SUBMIT') ?></button>

                                        <a onClick="confirmDel('<?= Router::url(['controller' => 'server', 'action' => 'delete_cmd/' . $c['id'], 'admin' => true]) ?>')"
                                           class="btn btn-danger"><?= $Lang->get('GLOBAL__DELETE') ?></a>
                                    </form>
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

<div class="modal fade show" id="executeCommand" aria-modal="true" role="dialog" style="display:none;">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= $Lang->get('SERVER__CMD_TITLE') ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">×</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= Router::url(['action' => 'add_cmd', 'admin' => true]) ?>" method="post" data-ajax="true"
                      data-redirect-url="<?= Router::url(['action' => 'cmd', 'admin' => true]) ?>">
                    <div class="ajax-msg"></div>
                    <div class="form-group">
                        <label><?= $Lang->get('GLOBAL__NAME') ?></label>
                        <input name="name" class="form-control" type="text">
                    </div>

                    <div class="form-group">
                        <label><?= $Lang->get('SERVER__COMMAND') ?></label>
                        <input name="cmd" class="form-control" type="text">
                    </div>

                    <div class="form-group">
                        <label><?= $Lang->get('SERVER__TITLE') ?></label>
                        <select class="form-control" name="server_id">
                            <?php foreach ($search_server as $c) {
                                if ($c['type'] == 0 or $c['type'] == 2) ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                            <?php } ?>
                        </select>
                    </div>

                    <div class="float-right">
                        <a href="<?= Router::url(['action' => 'cmd', 'admin' => true]) ?>"
                           class="btn btn-default"><?= $Lang->get('GLOBAL__CANCEL') ?></a>
                        <button class="btn btn-primary" type="submit"><?= $Lang->get('GLOBAL__SUBMIT') ?></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="executeCommand2" tabindex="-1" role="dialog" aria-labelledby="myModalLabel"
     aria-hidden="true" style="display:none;">
    <div class="modal-dialog">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header with-border">
                        <h3 class="card-title"><?= $Lang->get('SERVER__CMD_TITLE') ?></h3>
                    </div>
                    <div class="card-body">
                        <form action="<?= Router::url(['action' => 'add_cmd', 'admin' => true]) ?>" method="post" data-ajax="true"
                              data-redirect-url="<?= Router::url(['action' => 'cmd', 'admin' => true]) ?>">

                            <div class="ajax-msg"></div>

                            <div class="form-group">
                                <label><?= $Lang->get('GLOBAL__NAME') ?></label>
                                <input name="name" class="form-control" type="text">
                            </div>

                            <div class="form-group">
                                <label><?= $Lang->get('SERVER__COMMAND') ?></label>
                                <input name="cmd" class="form-control" type="text">
                            </div>

                            <div class="form-group">
                                <label><?= $Lang->get('SERVER__TITLE') ?></label>
                                <select class="form-control" name="server_id">
                                    <?php foreach ($search_server as $c) {
                                        if ($c['type'] == 0 or $c['type'] == 2) ?>
                                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                    <?php } ?>
                                </select>
                            </div>

                            <div class="float-right">
                                <a href="<?= Router::url(['action' => 'admin_cmd', 'admin' => true]) ?>"
                                   class="btn btn-default"><?= $Lang->get('GLOBAL__CANCEL') ?></a>
                                <button class="btn btn-primary"
                                        type="submit"><?= $Lang->get('GLOBAL__SUBMIT') ?></button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
