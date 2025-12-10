<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('PAGE__LIST') ?></h3>
                </div>
                <div class="card-body">

                    <a class="btn btn-large btn-block btn-primary"
                       href="<?= Router::url(['controller' => 'pages', 'action' => 'add', 'admin' => true]) ?>"><?= __('PAGE__ADD') ?></a>

                    <hr>

                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th><?= __('GLOBAL__TITLE') ?></th>
                            <th><?= __('GLOBAL__BY') ?></th>
                            <th><?= __('PAGE__POSTED_ON') ?></th>
                            <th><?= __('GLOBAL__UPDATED') ?></th>
                            <th><?= __('GLOBAL__SLUG') ?></th>
                            <th><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($pages as $key => $value) { ?>
                            <tr>
                                <td><?= $value['title'] ?></td>
                                <td><?= $value['author'] ?></td>
                                <td><?= $this->Lang->date($value['created']) ?></td>
                                <td><?= $this->Lang->date($value['updated']) ?></td>
                                <td>
                                    <a href="<?= Router::url(['controller' => 'p', 'action' => $value['slug'], 'admin' => false]) ?>"><?= $value['slug'] ?></a>
                                </td>
                                <td>
                                    <a href="<?= Router::url(['controller' => 'pages', 'action' => 'edit/' . $value['id'], 'admin' => true]) ?>"
                                       class="btn btn-info"><?= __('GLOBAL__EDIT') ?></a>
                                    <a onClick="confirmDel('<?= Router::url(['controller' => 'pages', 'action' => 'delete/' . $value['id'], 'admin' => true]) ?>')"
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
