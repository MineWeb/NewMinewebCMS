<br><br><br>
<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <h1><?= before_display($news['title']) ?></h1>
            <p class="lead">
                <?= __('GLOBAL__BY') ?> <a href="#"><?= $news['author'] ?></a>
            </p>

            <hr>
            <p>
                <span class="glyphicon glyphicon-time"></span> <?= __('NEWS__POSTED_ON') . ' ' . $this->Lang->date($news['created']); ?>
            </p>

            <hr>
            <p class="lead"><?= $news['content'] ?></p>
            <button id="<?= $news['id'] ?>" type="button"
                    class="btn btn-primary pull-right like<?= ($news['liked']) ? ' active' : '' ?>"<?= (!$this->Auth->can('LIKE_NEWS')) ? ' disabled' : '' ?>>
                <?= count($news['likes']) ?>
                <i class="fa fa-thumbs-up"></i>
            </button>
            <br>
            <?php if ($this->Auth->can('COMMENT_NEWS')) { ?>
                <div id="form-comment-fade-out">
                    <hr>
                    <div class="well">
                        <h4><?= __('NEWS__COMMENT_TITLE') ?> :</h4>
                        <form method="POST" data-ajax="true"
                              action="<?= $this->Url->build(['_name' => 'news_add_comment']) ?>"
                              data-callback-function="addcomment" data-success-msg="false">
                            <input name="news_id" value="<?= $news['id'] ?>" type="hidden">
                            <div class="form-group">
                                <textarea name="content" class="form-control" rows="3"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary"><?= __('GLOBAL__SUBMIT') ?></button>
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
                             src="<?= $this->Url->build(['_name' => 'api_get_head_skin', $v['author'], 64]) ?>"
                             alt="">
                    </a>
                    <div class="media-body">
                        <h4 class="media-heading">
                            <?= $v['author'] ?>
                            <small><?= $this->Lang->date($v['created']); ?></small>
                        </h4>
                        <?= before_display($v['content']) ?>
                    </div>
                    <div class="pull-right">
                        <?php if ($this->Auth->can('DELETE_COMMENT') or $this->Auth->can('DELETE_HIS_COMMENT') and $user['pseudo'] == $v['author']) { ?>
                            <p>
                                <a id="<?= $v['id'] ?>" title="<?= __('GLOBAL__DELETE') ?>"
                                   class="comment-delete btn btn-danger btn-sm">
                                    <icon class="fa fa-times"></icon>
                                </a>
                            </p>
                        <?php } ?>
                    </div>
                </div>
            <?php } ?>
        </div>
        <div class="col-md-4">
            <div class="well">
                <h4><?= __('NEWS__LAST_TITLE') ?></h4>
                <div class="row">
                    <div class="col-lg-6">
                        <ul class="list-unstyled">
                            <?php foreach ($search_news as $k => $v) { ?>
                                <li>
                                    <a href="<?= $this->Url->build(['_name' => 'blog_view', $v['slug']]) ?>">
                                        <?= $v['title'] ?>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>
            <div class="well">
                <h4><?= __('GLOBAL__INFORMATIONS') ?></h4>
                <p><b><?= __('GLOBAL__UPDATED') ?> : </b><?= $this->Lang->date($news['updated']) ?></p>
                <p><b><?= __('NEWS__COMMENTS_NBR') ?> : </b><?= count($news['comment']) ?></p>
                <p><b><?= __('NEWS__LIKES_NBR') ?> : </b><?= count($news['likes']) ?></p>
            </div>
        </div>
    </div>
</div>
<?= $Module->loadModules('news') ?>
<script>
    <?php if (!empty($user)) { ?>
    function addcomment(data) {
        let d = new Date();
        let hours = String(d.getHours()).padStart(2, "0");
        let minutes = String(d.getMinutes()).padStart(2, "0");
        let comment =
            '<div class="media">' +
            '<a class="pull-left" href="#">' +
            '<img class="media-object" src="<?= $this->Url->build(['_name' => 'api_get_head_skin', $user['pseudo'], 64]) ?>" alt="">' +
            '</a>' +
            '<div class="media-body">' +
            '<h4 class="media-heading"><?= $user['pseudo'] ?> ' +
            '<small>' + hours + 'h' + minutes + '</small>' +
            '</h4>' +
            data["content"] +
            '</div>' +
            '</div>';

        let addCommentContainer = document.querySelector(".add-comment");
        if (addCommentContainer) {
            addCommentContainer.style.display = "none";
            addCommentContainer.innerHTML = comment;
            addCommentContainer.style.display = "block";
        }

        let formFadeOut = document.getElementById("form-comment-fade-out");
        if (formFadeOut) {
            formFadeOut.style.display = "none";
        }
    }
    <?php } ?>

    function attachCommentDeleteHandlers() {
        let buttons = document.querySelectorAll(".comment-delete");
        buttons.forEach(function (button) {
            button.addEventListener("click", function () {
                comment_delete(this);
            });
        });
    }

    function comment_delete(e) {
        let id = e.getAttribute("id");
        let params = new URLSearchParams();
        params.append("id", id);
        params.append("data[_Token][key]", "<?= $csrfToken ?>");

        fetch("<?= $this->Url->build(['_name' => 'news_ajax_comment_delete']) ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8"
            },
            body: params.toString()
        })
            .then(function (response) {
                return response.text();
            })
            .then(function (data) {
                if (data === "true") {
                    let comment = document.getElementById("comment-" + id);
                    if (comment) {
                        comment.style.display = "none";
                    }
                } else {
                    console.log(data);
                }
            })
            .catch(function (error) {
                console.log(error);
            });
    }

    document.addEventListener("DOMContentLoaded", function () {
        attachCommentDeleteHandlers();
    });
</script>
