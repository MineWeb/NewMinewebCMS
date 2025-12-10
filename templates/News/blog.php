<div class="container">
    <div class="row">
        <div class="col-md-6">
            <h1>News</h1>
        </div>
    </div>
</div>
<div class="container">
    <div class="row">
        <?php use Cake\Routing\Router;

        foreach ($search_news as $news) { ?>
            <div class="well">
                <a href="<?= Router::url(['controller' => 'blog', 'action' => $news['slug']]) ?>"><h3>
                        <b><?= $news['title'] ?></b></h3></a>
                <p><b><?= __('GLOBAL__UPDATED') ?> : </b><?= $this->Lang->date($news['updated']) ?></p>
                <p><b><?= __('NEWS__COMMENTS_NBR') ?> : </b><?= count($news['comment']) ?></p>
                <p><b><?= __('NEWS__LIKES_NBR') ?> : </b><?= count($news['likes']) ?></p>
                <hr>
                <p><?php $nmsg = substr($news['content'], 0, 500);
                    echo $nmsg; ?> ...</p>
            </div>
        <?php } ?>
    </div>
</div>
