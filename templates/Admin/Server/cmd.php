<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title">
                        <?= __('SERVER__CMD_TITLE') ?>
                        <button type="button" data-toggle="modal" data-target="#executeCommand" class="btn btn-success">
                            <?= __('GLOBAL__ADD') ?>
                        </button>
                    </h3>
                </div>
                <div class="card-body">

                    <table class="table table-bordered dataTable">
                        <thead>
                        <tr>
                            <th scope="col"><?= __('SERVER__CMD_NAME') ?></th>
                            <th scope="col"><?= __('SERVER__COMMAND') ?></th>
                            <th scope="col"><?= __('SERVER__TITLE') ?></th>
                            <th scope="col" class="right"><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($search_cmd as $c) { ?>
                            <tr>
                                <td><?= $c['name'] ?></td>
                                <td><?= $c['cmd'] ?></td>
                                <td>
                                    <?php
                                    foreach ($search_server as $d) {
                                        if ($c['server_id'] == $d['id']) {
                                            echo $d['name'];
                                        }
                                    }
                                    ?>
                                </td>

                                <td class="right">
                                    <form method="post"
                                          action="<?= $this->Url->build(['_name' => 'admin_server_execute_cmd']) ?>"
                                          data-ajax="true"
                                          data-redirect-url="<?= $this->Url->build(['_name' => 'admin_server_cmd']) ?>">

                                        <input type="hidden" name="cmd" value="<?= $c['cmd'] ?>">
                                        <input type="hidden" name="server_id" value="<?= $c['server_id'] ?>">

                                        <button class="btn btn-primary" type="submit">
                                            <?= __('GLOBAL__SUBMIT') ?>
                                        </button>

                                        <a onclick="confirmDel('<?= $this->Url->build(['_name' => 'admin_server_delete_cmd', $c['id']]) ?>')"
                                           class="btn btn-danger">
                                            <?= __('GLOBAL__DELETE') ?>
                                        </a>
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
                <h4 class="modal-title"><?= __('SERVER__CMD_TITLE') ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="<?= __('GLOBAL__CANCEL') ?>">
                    <span aria-hidden="true">×</span>
                </button>
            </div>

            <div class="modal-body">
                <form action="<?= $this->Url->build(['_name' => 'admin_server_add_cmd']) ?>"
                      method="post"
                      data-ajax="true"
                      data-redirect-url="<?= $this->Url->build(['_name' => 'admin_server_cmd']) ?>">

                    <div class="ajax-msg" aria-live="polite"></div>

                    <div class="form-group">
                        <label for="cmd-name-1"><?= __('GLOBAL__NAME') ?></label>
                        <input id="cmd-name-1" name="name" class="form-control" type="text">
                    </div>

                    <div class="form-group">
                        <label for="cmd-command-1"><?= __('SERVER__COMMAND') ?></label>
                        <input id="cmd-command-1" name="cmd" class="form-control" type="text">
                    </div>

                    <div class="form-group">
                        <label for="cmd-server-1"><?= __('SERVER__TITLE') ?></label>
                        <select id="cmd-server-1" class="form-control" name="server_id">
                            <?php foreach ($search_server as $c) {
                                if ($c['type'] == 0 or $c['type'] == 2) { ?>
                                    <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                <?php }
                            } ?>
                        </select>
                    </div>

                    <div class="float-right">
                        <a href="<?= $this->Url->build(['_name' => 'admin_server_cmd']) ?>"
                           class="btn btn-default">
                            <?= __('GLOBAL__CANCEL') ?>
                        </a>

                        <button class="btn btn-primary" type="submit">
                            <?= __('GLOBAL__SUBMIT') ?>
                        </button>
                    </div>

                </form>
            </div>

        </div>
    </div>
</div>

<div class="modal fade" id="executeCommand2" tabindex="-1" role="dialog" aria-hidden="true" style="display:none;">
    <div class="modal-dialog">
        <div class="row">
            <div class="col-md-12">

                <div class="card">
                    <div class="card-header with-border">
                        <h3 class="card-title"><?= __('SERVER__CMD_TITLE') ?></h3>
                    </div>

                    <div class="card-body">

                        <form action="<?= $this->Url->build(['_name' => 'admin_server_add_cmd']) ?>"
                              method="post"
                              data-ajax="true"
                              data-redirect-url="<?= $this->Url->build(['_name' => 'admin_server_cmd']) ?>">

                            <div class="ajax-msg" aria-live="polite"></div>

                            <div class="form-group">
                                <label for="cmd-name-2"><?= __('GLOBAL__NAME') ?></label>
                                <input id="cmd-name-2" name="name" class="form-control" type="text">
                            </div>

                            <div class="form-group">
                                <label for="cmd-command-2"><?= __('SERVER__COMMAND') ?></label>
                                <input id="cmd-command-2" name="cmd" class="form-control" type="text">
                            </div>

                            <div class="form-group">
                                <label for="cmd-server-2"><?= __('SERVER__TITLE') ?></label>
                                <select id="cmd-server-2" class="form-control" name="server_id">
                                    <?php foreach ($search_server as $c) {
                                        if ($c['type'] == 0 or $c['type'] == 2) { ?>
                                            <option value="<?= $c['id'] ?>"><?= $c['name'] ?></option>
                                        <?php }
                                    } ?>
                                </select>
                            </div>

                            <div class="float-right">
                                <a href="<?= $this->Url->build(['_name' => 'admin_server_cmd']) ?>"
                                   class="btn btn-default">
                                    <?= __('GLOBAL__CANCEL') ?>
                                </a>

                                <button class="btn btn-primary" type="submit">
                                    <?= __('GLOBAL__SUBMIT') ?>
                                </button>
                            </div>

                        </form>

                    </div>
                </div>

            </div>
        </div>
    </div>
</div>
