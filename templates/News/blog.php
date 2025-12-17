<?php
declare(strict_types=1);

$search_news = is_array($search_news ?? null) ? $search_news : [];
?>
<div class="container">
    <div class="row">
        <div class="col-md-6">
            <h1><?= __('NEWS__TITLE') ?></h1>
        </div>
    </div>
</div>

<div class="container">
    <div class="row">
        <?php foreach ($search_news as $news) {
            $slug = (string)($news['slug'] ?? '');
            $title = (string)($news['title'] ?? '');
            $content = (string)($news['content'] ?? '');

            $comments = $news['comment'] ?? [];
            $likes = $news['likes'] ?? [];

            $commentsCount = is_countable($comments) ? count($comments) : 0;
            $likesCount = is_countable($likes) ? count($likes) : 0;

            $updatedAt = $news['updated_at'] ?? null;
            ?>
            <div class="well">
                <a href="<?= $this->Url->build('/blog/' . $slug) ?>">
                    <h3><b><?= h($title) ?></b></h3>
                </a>

                <p><b><?= __('GLOBAL__UPDATED') ?> : </b><?= $updatedAt ? $this->Lang->date($updatedAt) : '' ?></p>
                <p><b><?= __('NEWS__COMMENTS_NBR') ?> : </b><?= (int)$commentsCount ?></p>
                <p><b><?= __('NEWS__LIKES_NBR') ?> : </b><?= (int)$likesCount ?></p>

                <hr>

                <p><?= h(mb_substr(strip_tags($content), 0, 500)) ?> ...</p>
            </div>
        <?php } ?>
    </div>
</div>
