<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= $Lang->get('MOTD__EDIT_TITLE') ?></h3>
                </div>
                <div class="card-body">
                    <form method="post" action="<?= Router::url(['action' => 'edit_ajax', 'admin' => true, $get['id']]) ?>"
                          data-ajax="true" data-redirect-url="<?= Router::url(['action' => 'index', 'admin' => true]) ?>">
                        <div class="form-group">
                            <label><?= $Lang->get('GLOBAL__NAME') ?></label>
                            <input disabled class="form-control" value="<?= $get['name'] ?>">
                        </div>
                        <div class="form-group">
                            <label>Motd</label>
                            <p><?= $Lang->get('MOTD__DESC') ?></p>
                            <div class="input-group">
                                <div class="input-group-addon"><?= $Lang->get('MOTD__LINE') ?> 1</div>
                                <input name="motd_line1" class="form-control" type="text"
                                       value="<?= $get['motd_line1'] ?>">
                            </div>
                            <br>
                            <div class="input-group">
                                <div class="input-group-addon"><?= $Lang->get('MOTD__LINE') ?> 2</div>
                                <input name="motd_line2" class="form-control" type="text"
                                       value="<?= $get['motd_line2'] ?? 'Non définie' ?>">
                            </div>
                            <br>

                            <p><b><?= $Lang->get('MOTD__VARIABLES') ?> : </b></p>
                            <p><em>{PLAYERS}</em> : <?= $Lang->get('MOTD__VARIABLE_PLAYERS') ?></p>
                        </div>
                        <div class="float-right">
                            <a href="<?= Router::url(['controller' => 'motd', 'action' => 'index', 'admin' => true]) ?>"
                               class="btn btn-default"><?= $Lang->get('GLOBAL__CANCEL') ?></a>
                            <button class="btn btn-primary" type="submit"><?= $Lang->get('GLOBAL__EDIT') ?></button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
