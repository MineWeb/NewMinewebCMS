<?php use Cake\Routing\Router;

if (!isset($theme_config['slider']) || $theme_config['slider'] == "true") { ?>
    <header id="myCarousel" class="carousel slide transition-timer-carousel">
        <div class="carousel-inner">
            <?php if (!empty($search_slider)) { ?>
                <?php $i = 0;
                foreach ($search_slider as $k => $v) { ?>
                    <div class="item<?php if ($i == 0) {
                        echo ' active';
                    } ?>">
                        <div class="fill" style="background-image:url('<?= $v['Slider']['url_img'] ?>');"></div>
                        <div class="carousel-caption">
                            <h2><?= before_display($v['Slider']['title']) ?></h2>
                            <p><?= before_display($v['Slider']['subtitle']) ?></p>
                        </div>
                    </div>
                    <?php $i++;
                } ?>
            <?php } else { ?>
                <div class="item active">
                    <div class="fill" style="background-image:url('https://via.placeholder.com/1905x420&text=1905x420');"></div>
                    <div class="carousel-caption">
                        <h2>Caption 1</h2>
                    </div>
                </div>
                <div class="item">
                    <div class="fill" style="background-image:url('https://via.placeholder.com/1905x420&text=1905x420');"></div>
                    <div class="carousel-caption">
                        <h2>Caption 2</h2>
                    </div>
                </div>
                <div class="item">
                    <div class="fill" style="background-image:url('https://via.placeholder.com/1905x420&text=1905x420');"></div>
                    <div class="carousel-caption">
                        <h2>Caption 3</h2>
                    </div>
                </div>
            <?php } ?>
        </div>

        <a class="left carousel-control" href="#myCarousel" data-slide="prev">
            <span class="icon-prev"></span>
        </a>
        <a class="right carousel-control" href="#myCarousel" data-slide="next">
            <span class="icon-next"></span>
        </a>
        <!-- Timer "progress bar" -->
        <hr class="transition-timer-carousel-progress-bar animate"/>
    </header>
<?php } ?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header animated fadeInRight home">
                <?= $Lang->get('NEWS__LAST_TITLE') ?>
            </h1>
        </div>
        <?php if (!empty($search_news)) { ?>
            <ul id="items">
                <?php foreach ($search_news as $k => $v) { ?>
                    <li class="col-md-4 animated fadeInUp">
                        <div class="bloc <?= rand_color_news() ?>" style="width:100%;">
                            <h2><?= cut($v['title'], 15) ?></h2>
                            <div><?= $this->Text->truncate($v['content'], 220, ['ellipsis' => '...', 'html' => true]) ?></div>
                            <div class="btn-group">
                                <button id="<?= $v['id'] ?>" type="button"
                                        class="btn btn-primary like<?= ($v['liked']) ? ' active' : ''; ?>"<?= ($can_like) ? '' : ' disabled' ?>><?= $v['count_likes'] ?>
                                    <i class="fa fa-thumbs-up"></i></button>
                                <button type="button" class="btn btn-primary"><?= $v['count_comments'] ?> <i
                                            class="fa fa-comments"></i></button>
                            </div>
                            <a href="<?= Router::url(['controller' => 'blog', 'action' => $v['slug']]) ?>"
                               class="btn btn-success pull-right"><?= $Lang->get('NEWS__READ_MORE') ?> »</a>
                        </div>
                    </li>
                <?php } ?>
            </ul>
            <ol id="pagination"></ol>
        <?php } else {
            echo '<center><h3>' . $Lang->get('NEWS__NONE_PUBLISHED') . '</h3></center>';
        } ?>
    </div>
    <div class="row btn-socials text-center">
        <?php
        $howManyBtns = count($findSocialButtons);

        $maxBtnsByLine = 4;
        if ($howManyBtns > 0) {
            $howManyBtnsDivided = ceil($howManyBtns / ceil($howManyBtns / $maxBtnsByLine));
            $col = 12 / $howManyBtnsDivided;
        }

        foreach ($findSocialButtons as $key => $value) {
            echo '<div class="col-md-' . $col . ' text-center"><a class="btn btn-default btn-block btn-lg" style="background-color:' . $value['color'] . ';color:white;font-size:18px;" target="_blank" href="' . $value['url'] . '">';

            if(!empty($value['extra'])) {
                if (strpos($value['extra'], 'fa-')) {
                    echo '<i class="' . $value['extra'] . '"></i>';
                } else {
                    echo '<img src="' . $value['extra'] . '" alt="' . $Lang->get("SOCIAL__BUTTON_IMG_ALT") . $value['title'] . '">';
                }
            }

            if (!empty($value['title'])) {
                echo ' ' . $value['title'];
            }
            echo '</a></div>';
        }
        ?>
    </div>

    <?= $Module->loadModules('home') ?>
</div>
