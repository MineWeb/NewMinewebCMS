<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('NEWS__LIST_PUBLISHED') ?></h3>
                </div>
                <div class="card-body">
                    <a class="btn btn-large btn-block btn-primary"
                       href="<?= Router::url(['controller' => 'news', 'action' => 'add', 'admin' => true]) ?>"><?= __('NEWS__ADD_NEWS') ?></a>

                    <hr>

                    <table class="table table-bordered">
                        <thead>
                        <tr>
                            <th><?= __('GLOBAL__TITLE') ?></th>
                            <th><?= __('GLOBAL__BY') ?></th>
                            <th><?= __('NEWS__PUBLISHED') ?></th>
                            <th><?= __('NEWS__POSTED_ON') ?></th>
                            <th><?= __('NEWS__COMMENTS_NBR') ?></th>
                            <th><?= __('NEWS__LIKES_NBR') ?></th>
                            <th><?= __('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($view_news as $news => $v) { ?>
                            <tr>
                                <td><?= $v['title'] ?></td>
                                <td><?= $v['author'] ?></td>
                                <td><?= ($v['published']) ? '<span class="label label-success">' . __('GLOBAL__YES') . '</span>' : '<span class="label label-danger">' . __('GLOBAL__NO') . '</span>'; ?></td>
                                <td><?= $this->Lang->date($v['created']) ?></td>
                                <td><?= count($v['comment']) ?> <?= __('NEWS__COMMENTS_TITLE') ?></td>
                                <td><?= count($v['likes']) ?> <?= __('NEWS__LIKES') ?></td>
                                <td>
                                    <a href="<?= Router::url(['controller' => 'news', 'action' => 'edit', 'admin' => true, $v['id']]) ?>"
                                       class="btn btn-info"><?= __('GLOBAL__EDIT') ?></a>
                                    <a onClick="confirmDel('<?= Router::url(['controller' => 'news', 'action' => 'delete', 'admin' => true, $v['id']]) ?>')"
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
