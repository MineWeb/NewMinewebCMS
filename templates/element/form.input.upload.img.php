<?php

use Cake\Routing\Router;

?>
<div class="form-group">
    <label><?= (isset($title)) ? $title : $Lang->get('FORM__UPLOAD_IMAGE') ?></label><br>
    <div id="image_preview">
        <div class="thumbnail">
            <span class="file-input btn btn-primary btn-block btn-file"><span
                        class="browse"><?= $Lang->get('FORM__BROWSE') ?>&hellip;</span> <input name="image" type="file"
                                                                                               multiple></span>
            <a id="choose_from_uploaded_files" class="btn btn-default btn-block" data-toggle="modal"
               href="#galery"><?= $Lang->get('FORM__CHOOSE_FROM_UPLOADED_FILES') ?>&hellip;</a>
            <button id="delete_upload_file"
                    class="btn btn-block btn-danger"><?= $Lang->get('FORM__DELETE_UPLOADED_FILE') ?></button>
            <?= (isset($img)) ? '<img src="' . $img . '" class="float-left" width="150" style="margin-top:10px;">' : $this->Html->image('form_img.png', ['class' => 'float-left', 'width' => '150', 'id' => 'img-form', 'style' => 'margin-top:10px;']) ?>
            <div class="caption float-right">
                <h5 id="img-name"><?= (isset($filename)) ? $filename . '<input name="img_edit" value="1" type="hidden">' : '' ?></h5>
                <p></p>
            </div>
            <div class="clearfix"></div>
        </div>
    </div>
</div>
<input name="data[_Token][key]" value="<?= $csrfToken ?>" type="hidden">
<div class="clearfix"></div>


<div class="modal fade" id="galery" tabindex="-1" role="dialog">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h4 class="modal-title"><?= $Lang->get('GALLERY__MODAL_TITLE') ?></h4>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span>
                </button>.

            </div>
            <div class="modal-body">
                <p>
                    <?= $Lang->get('GALLERY__PARAGRAPH') ?>
                </p>
                <?php
                $files = findRecursive(ROOT . DS . 'webroot' . DS . 'img' . DS . 'uploads', array("png", "jpg", "jpeg", "gif"));

                foreach ($files as $path) {
                    $file = new SplFileObject($path);
                    $basename = substr($path, strlen(ROOT . DS . 'webroot' . DS . 'img' . DS . 'uploads'));

                    echo '<hr><div class="row" style="margin-top:10px;margin-bottom:10px;">';
                    echo '<div class="col-md-4">';
                    echo '<img class="img-thumbnail img-rounded" src="data:image/' . $file->getExtension() . ';base64,' . base64_encode($file->fread($file->getSize())) . '" style="width:100%;">';
                    echo '</div>';
                    echo '<div class="col-md-8">';
                    echo '<p>' . $file->getFilename() . '</p>';
                    echo '<button data-basename="' . $basename . '" data-filename="' . $file->getFilename() . '" data-path="' . Router::url('/') . 'img/uploads/' . $basename . '" class="btn btn-primary choose-from-gallery-img">' . $Lang->get('GALLERY__CHOOSE') . '</button>';
                    echo '</div>';
                    echo '</div>';
                }
                ?>
            </div>
        </div><!-- /.modal-content -->
    </div><!-- /.modal-dialog -->
</div>
