<?php
declare(strict_types=1);

$likes = $news['likes'] ?? [];
$comments = $news['comment'] ?? [];

$likesCount = is_countable($likes) ? count($likes) : 0;
$commentsCount = is_countable($comments) ? count($comments) : 0;

$canLike = $this->Auth->can('LIKE_NEWS');
$canComment = $this->Auth->can('COMMENT_NEWS');

$author = (string)($news['author'] ?? '');
$title = (string)($news['title'] ?? '');
$content = (string)($news['content'] ?? '');
$createdAt = $news['created_at'] ?? null;
$updatedAt = $news['updated_at'] ?? null;

$newsId = (string)($news['id'] ?? '');
$liked = !empty($news['liked']);

$search_news = is_array($search_news ?? null) ? $search_news : [];
?>
<br><br><br>
<div class="container">
    <div class="row">
        <div class="col-lg-8">
            <h1><?= before_display($title) ?></h1>

            <p class="lead">
                <?= __('GLOBAL__BY') ?> <a href="#"><?= h($author) ?></a>
            </p>

            <hr>

            <p>
                <span class="glyphicon glyphicon-time"></span>
                <?= __('NEWS__POSTED_ON') . ' ' . ($createdAt ? $this->Lang->date($createdAt) : '') ?>
            </p>

            <hr>

            <p class="lead"><?= $content ?></p>

            <button id="<?= h($newsId) ?>" type="button"
                    class="btn btn-primary pull-right like<?= ($liked ? ' active' : '') ?>"<?= (!$canLike ? ' disabled' : '') ?>>
                <?= (int)$likesCount ?>
                <i class="fa fa-thumbs-up"></i>
            </button>

            <br>

            <?php if ($canComment) { ?>
                <div id="form-comment-fade-out">
                    <hr>
                    <div class="well">
                        <h4><?= __('NEWS__COMMENT_TITLE') ?> :</h4>

                        <?= $this->Form->create(null, [
                            'type' => 'post',
                            'url' => ['_name' => 'news_add_comment'],
                            'data-ajax' => 'true',
                            'data-callback-function' => 'addcomment',
                            'data-success-msg' => 'false',
                        ]) ?>
                        <?= $this->Form->hidden('news_id', ['value' => $newsId]) ?>
                        <div class="form-group">
                            <textarea name="content" class="form-control" rows="3"></textarea>
                        </div>
                        <button type="submit" class="btn btn-primary"><?= __('GLOBAL__SUBMIT') ?></button>
                        <?= $this->Form->end() ?>
                    </div>
                </div>
            <?php } ?>

            <hr>
            <div class="add-comment"></div>

            <?php if (!empty($comments) && is_iterable($comments)) { ?>
                <?php foreach ($comments as $k => $v) {
                    $commentId = (string)($v['id'] ?? '');
                    $commentAuthor = (string)($v['author'] ?? '');
                    $commentContent = (string)($v['content'] ?? '');
                    $commentCreated = $v['created_at'] ?? null;

                    $canDeleteAny = $this->Auth->can('DELETE_COMMENT');
                    $canDeleteOwn = $this->Auth->can('DELETE_HIS_COMMENT') && h($this->Auth->username()) === $commentAuthor;
                    ?>
                    <div class="media comment" id="comment-<?= h($commentId) ?>">
                        <a class="pull-left" href="#">
                            <img class="media-object"
                                 src="<?= $this->Url->build(['_name' => 'api_get_head_skin', $commentAuthor, 64]) ?>"
                                 alt="">
                        </a>

                        <div class="media-body">
                            <h4 class="media-heading">
                                <?= h($commentAuthor) ?>
                                <small><?= $commentCreated ? $this->Lang->date($commentCreated) : '' ?></small>
                            </h4>
                            <?= before_display($commentContent) ?>
                        </div>

                        <div class="pull-right">
                            <?php if ($canDeleteAny || $canDeleteOwn) { ?>
                                <p>
                                    <a id="<?= h($commentId) ?>" title="<?= __('GLOBAL__DELETE') ?>"
                                       class="comment-delete btn btn-danger btn-sm">
                                        <i class="fa fa-times"></i>
                                    </a>
                                </p>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
        </div>

        <div class="col-md-4">
            <div class="well">
                <h4><?= __('NEWS__LAST_TITLE') ?></h4>
                <div class="row">
                    <div class="col-lg-6">
                        <ul class="list-unstyled">
                            <?php foreach ($search_news as $k => $v) {
                                $slug = (string)($v['slug'] ?? '');
                                $t = (string)($v['title'] ?? '');
                                ?>
                                <li>
                                    <a href="<?= $this->Url->build('/blog/' . $slug) ?>">
                                        <?= h($t) ?>
                                    </a>
                                </li>
                            <?php } ?>
                        </ul>
                    </div>
                </div>
            </div>

            <div class="well">
                <h4><?= __('GLOBAL__INFORMATIONS') ?></h4>
                <p><b><?= __('GLOBAL__UPDATED') ?> : </b><?= $updatedAt ? $this->Lang->date($updatedAt) : '' ?></p>
                <p><b><?= __('NEWS__COMMENTS_NBR') ?> : </b><?= (int)$commentsCount ?></p>
                <p><b><?= __('NEWS__LIKES_NBR') ?> : </b><?= (int)$likesCount ?></p>
            </div>
        </div>
    </div>
</div>

<?= $this->Module->load('news') ?>

<script>
    <?php if (!empty($user)) { ?>
    function addcomment(data) {
        let d = new Date();
        let hours = String(d.getHours()).padStart(2, "0");
        let minutes = String(d.getMinutes()).padStart(2, "0");

        let comment =
            '<div class="media">' +
            '<a class="pull-left" href="#">' +
            '<img class="media-object" src="<?= $this->Url->build(['_name' => 'api_get_head_skin', h($this->Auth->username()), 64]) ?>" alt="">' +
            '</a>' +
            '<div class="media-body">' +
            '<h4 class="media-heading"><?= h($this->Auth->username()) ?> ' +
            '<small>' + hours + 'h' + minutes + '</small>' +
            '</h4>' +
            (data && data["content"] ? data["content"] : '') +
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

    function getCsrfToken() {
        let meta = document.querySelector('meta[name="csrf-token"]');
        return meta ? meta.getAttribute("content") : "";
    }

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

        fetch("<?= $this->Url->build(['_name' => 'news_ajax_comment_delete']) ?>", {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded;charset=UTF-8",
                "X-CSRF-Token": getCsrfToken()
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
