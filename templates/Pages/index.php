<div class="container">
    <div class="row">

        <div class="col-md-6">
            <h1 style="display: inline-block;"><?= before_display($page['title']) ?></h1>
        </div>
    </div>
    <br>
    <div class="row">
        <div class="col-md-12">
            <div class="panel panel-default">
                <div class="panel-body">
                    <?= $page['content'] ?>
                    <small class="pull-right"><?= __('GLOBAL__UPDATED') ?>
                        : <?= $this->Lang->date($page['updated_at']) ?>
                    </small><br>
                    <small class="pull-right"><?= __('GLOBAL__AUTHOR') ?> : <?= $page['author'] ?></small>
                </div>
            </div>
        </div>
    </div>
</div>
