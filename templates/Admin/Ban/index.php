<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= $Lang->get("BAN__HOME") ?></h3>
                </div>
                <div class="card-body">
                    <a class="btn btn-large btn-block btn-primary" href="<?= Router::url(['controller' => 'ban', 'action' => 'add', 'admin' => true]) ?>"><?= $Lang->get('BAN__ADD') ?></a>
                    <hr>
                    <table class="table table-responsive-sm table-bordered">
                        <thead>
                            <tr>
                                <th><?= $Lang->get("USER__USERNAME") ?></th>
                                <th><?= $Lang->get("BAN__REASON") ?></th>
                                <th><?= $Lang->get("BAN__IS_BAN_IP") ?></th>
                                <th><?= $Lang->get("GLOBAL__ACTIONS")?></th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php foreach ($banned_users as $v) { ?>
                                <tr>
                                    <td><?= $v["pseudo"] ?></td>
                                    <td><?= $v["reason"] ?></td>
                                    <td><?= $v["ip"] != null ? $v["ip"] : $Lang->get("BAN__NOT_BAN_IP") ?></td>
                                    <td>
                                        <a onClick="confirmDel('<?= Router::url(['action' => 'unban', 'admin' => true, $v['id']]) ?>')"
                                           class="btn btn-danger"><?= $Lang->get('BAN__UNBAN') ?></a>
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
