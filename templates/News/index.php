<?php

use Cake\Routing\Router;

?>
<br><br><br>
<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <h1><?= before_display($news['title']) ?></h1>
            <p class="lead">
                <?= $Lang->get('GLOBAL__BY') ?> <a href="#"><?= $news['author'] ?></a>
            </p>

            <hr>
            <p>
                <span class="glyphicon glyphicon-time"></span> <?= $Lang->get('NEWS__POSTED_ON') . ' ' . $Lang->date($news['created']); ?>
            </p>

            <hr>
            <p class="lead"><?= $news['content'] ?></p>
            <button id="<?= $news['id'] ?>" type="button"
                    class="btn btn-primary pull-right like<?= ($news['liked']) ? ' active' : '' ?>"<?= (!$Permissions->can('LIKE_NEWS')) ? ' disabled' : '' ?>><?= count($news['likes']) ?>
                <i class="fa fa-thumbs-up"></i></button>
            <br>
            <?php if ($Permissions->can('COMMENT_NEWS')) { ?>
                <div id="form-comment-fade-out">
                    <hr>
                    <div class="well">
                        <h4><?= $Lang->get('NEWS__COMMENT_TITLE') ?> :</h4>
                        <form method="POST" data-ajax="true"
                              action="<?= Router::url(['controller' => 'news', 'action' => 'add_comment']) ?>"
                              data-callback-function="addcomment" data-success-msg="false">
                            <input name="news_id" value="<?= $news['id'] ?>" type="hidden">
                            <div class="form-group">
                                <textarea name="content" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= $Lang->get('GLOBAL__SUBMIT') ?></button>
                        </form>
                    </div>
                </div>
            <?php } ?>
            <hr>
            <div class="add-comment"></div>
            <?php foreach ($news['comment'] as $k => $v) { ?>
                <div class="media comment" id="comment-<?= $v['id'] ?>">
                    <a class="pull-left" href="#">
                        <img class="media-object"
                             src="<?= Router::url(['controller' => 'API', 'action' => 'get_head_skin/']) ?>/<?= $v['author'] ?>/64"
                             alt="">
                    </a>
                    <div class="media-body">
                        <h4 class="media-heading"><?= $v['author'] ?>
                            <small><?= $Lang->date($v['created']); ?></small>
                        </h4>
                        <?= before_display($v['content']) ?>
                    </div>
                    <div class="pull-right">
                        <?php if ($Permissions->can('DELETE_COMMENT') or $Permissions->can('DELETE_HIS_COMMENT') and $user['pseudo'] == $v['author']) { ?>
                            <p><a id="<?= $v['id'] ?>" title="<?= $Lang->get('GLOBAL__DELETE') ?>"
                                  class="comment-delete btn btn-danger btn-sm">
                                    <icon class="fa fa-times"></icon>
                                </a></p>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="col-md-4">
            <div class="well">
                <h4><?= $Lang->get('NEWS__LAST_TITLE') ?></h4>
                <div class="row">
                    <div class="col-lg-6">
                        <ul class="list-unstyled">
                            <?php foreach ($search_news as $k => $v) { ?>
                                <li>
                                    <a href="<?= Router::url(['controller' => 'blog', 'action' => $v['slug']]) ?>"><?= $v['title'] ?></a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="well">
                <h4><?= $Lang->get('GLOBAL__INFORMATIONS') ?></h4>
                <p><b><?= $Lang->get('GLOBAL__UPDATED') ?> : </b><?= $Lang->date($news['updated']) ?></p>
                <p><b><?= $Lang->get('NEWS__COMMENTS_NBR') ?> : </b><?= count($news['comment']) ?></p>
                <p><b><?= $Lang->get('NEWS__LIKES_NBR') ?> : </b><?= count($news['likes']) ?></p>
            </div>
        </div>
    </div>
</div>
<?= $Module->loadModules('news') ?>
<script>
    <?php if (!empty($user)) { ?>
        function addcomment(data) {
            var d = new Date();
            var comment = '<div class="media"><a class="pull-left" href="#"><img class="media-object" src="<?= Router::url(['controller' => 'API', 'action' => 'get_head_skin', $user['pseudo'], '64']) ?>" alt=""></a><div class="media-body"><h4 class="media-heading"><?= $user['pseudo'] ?> <small>' + d.getHours() + 'h' + d.getMinutes() + '</small></h4>' + data['content'] + '</div></div>';
            $('.add-comment').hide().html(comment).fadeIn(1500);
            $('#form-comment-fade-out').slideUp(1500);
        }
    <?php } ?>

    $(".comment-delete").click(function () {
        comment_delete(this);
    });

    function comment_delete(e) {
        var inputs = {};
        var id = $(e).attr("id");
        inputs["id"] = id;
        inputs["data[_Token][key]"] = '<?= $csrfToken ?>';
        $.post("<?= Router::url(['controller' => 'news', 'action' => 'ajax_comment_delete']) ?>", inputs, function (data) {
            if (data === 'true') {
                $('#comment-' + id).slideUp(500);
            } else {
                console.log(data);
            }
        });
    }
</script>
