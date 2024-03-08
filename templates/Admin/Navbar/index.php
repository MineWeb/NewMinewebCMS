<?php

use Cake\Routing\Router;

?>
<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= $Lang->get('NAVBAR__TITLE') ?></h3>
                </div>
                <div class="card-body">

                    <a class="btn btn-large btn-block btn-primary"
                       href="<?= Router::url(['controller' => 'navbar', 'action' => 'add', 'admin' => true]) ?>"><?= $Lang->get('NAVBAR__ADD_LINK') ?></a>
                    <hr>

                    <table class="table table-responsive-sm table-bordered"
                           style="table-layout: fixed;word-wrap: break-word;">
                        <thead>
                        <tr>
                            <th><?= $Lang->get('GLOBAL__NAME') ?></th>
                            <th><?= $Lang->get('URL') ?></th>
                            <th><?= $Lang->get('GLOBAL__ACTIONS') ?></th>
                        </tr>
                        </thead>
                        <tbody id="sortable">
                        <?php $i = 0;
                        foreach ($navbars as $key => $value) {
                            $i++; ?>

                            <tr style="cursor:move;" id="<?= $value['id'] ?>-<?= $i ?>">
                                <td>
                                    <?php if (!empty($value['icon'])): ?>
                                        <i class="<?= ((strpos($value['icon'], "fa-")) ? $value['icon'] : "fa fa-" . $value['icon']) ?>"></i>
                                    <?php endif; ?>
                                    <?= $value['name'] ?></td>
                                <?php if ($value['url'] != '#' && $value['url'] !== false) { ?>
                                    <td><a href="<?= $value['url'] ?>"><?= $value['url'] ?></a>
                                    </td>
                                <?php } else if ($value['url'] === false) { ?>
                                    <td>
                                        <span class="label label-danger"><?= $Lang->get('PLUGIN__ERROR_UNINSTALLED') ?></span>
                                    </td>
                                <?php } else { ?>
                                    <td><a href="#"><?= $Lang->get('NAVBAR__LINK_TYPE_DROPDOWN') ?></a></td>
                                <?php } ?>
                                <td>
                                    <a class="btn btn-info"
                                       href="<?= Router::url(['action' => 'edit', 'admin' => true, $value['id']]) ?>"><?= $Lang->get('GLOBAL__EDIT') ?></a>
                                    <a onClick="confirmDel('<?= Router::url(['action' => 'delete', 'admin' => true, $value['id']]) ?>')"
                                       class="btn btn-danger"><?= $Lang->get('GLOBAL__DELETE') ?></a>
                                </td>
                            </tr>

                        <?php } ?>
                        </tbody>
                    </table>
                    <br>
                    <div class="ajax-msg"></div>
                    <button id="save" class="btn btn-success float-right active"
                            disabled="disabled"><?= $Lang->get('NAVBAR__SAVE_SUCCESS') ?></button>

                </div>
            </div>
        </div>
    </div>
</section>
<style>
    li {
        list-style-type: none;
    }
</style>
<script>
    $(function () {
        $("#sortable").sortable({
            axis: 'y',
            stop: function (event, ui) {
                $('#save').empty().html('<?= $Lang->get('NAVBAR__SAVE_IN_PROGRESS') ?>');
                let inputs = {};
                inputs['navbar_order'] = $(this).sortable('serialize');
                inputs['data[_Token][key]'] = '<?= $csrfToken ?>';
                $.post("<?= Router::url(['controller' => 'navbar', 'action' => 'save_ajax', 'admin' => true]) ?>", inputs, function (data) {
                    if (data.statut) {
                        $('#save').empty().html('<?= $Lang->get('NAVBAR__SAVE_SUCCESS') ?>');
                    } else if (!data.statut) {
                        $('.ajax-msg').empty().html('<div class="alert alert-danger" style="margin-top:10px;margin-right:10px;margin-left:10px;"><a class="close" data-dismiss="alert">×</a><i class="icon icon-warning-sign"></i> <b><?= $Lang->get('GLOBAL__ERROR') ?> :</b> ' + data.msg + '</i></div>').fadeIn(500);
                    } else {
                        $('.ajax-msg').empty().html('<div class="alert alert-danger" style="margin-top:10px;margin-right:10px;margin-left:10px;"><a class="close" data-dismiss="alert">×</a><i class="icon icon-warning-sign"></i> <b><?= $Lang->get('GLOBAL__ERROR') ?> :</b> <?= $Lang->get('ERROR__INTERNAL_ERROR') ?></i></div>');
                    }
                });
            }
        });
        //$( "#sortable" ).disableSelection();
    });
</script>
