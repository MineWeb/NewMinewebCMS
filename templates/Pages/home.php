<?php
declare(strict_types=1);

$theme_config = is_array($theme_config ?? null) ? $theme_config : [];
$sliderEnabled = !array_key_exists('slider', $theme_config) || (string)$theme_config['slider'] === 'true';
?>

<?php if ($sliderEnabled) { ?>
    <header id="myCarousel" class="carousel slide transition-timer-carousel">
        <div class="carousel-inner">
            <?php if (!empty($search_slider)) { ?>
                <?php $i = 0; ?>
                <?php foreach ($search_slider as $k => $v) { ?>
                    <div class="item<?= ($i === 0 ? ' active' : '') ?>">
                        <div class="fill" style="background-image:url('<?= h($v['Slider']['url_img'] ?? '') ?>');"></div>
                        <div class="carousel-caption">
                            <h2><?= before_display((string)($v['Slider']['title'] ?? '')) ?></h2>
                            <p><?= before_display((string)($v['Slider']['subtitle'] ?? '')) ?></p>
                        </div>
                    </div>
                    <?php $i++; ?>
                <?php } ?>
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

        <hr class="transition-timer-carousel-progress-bar animate"/>
    </header>
<?php } ?>

<div class="container">
    <div class="row">
        <div class="col-lg-12">
            <h1 class="page-header animated fadeInRight home">
                <?= __('NEWS__LAST_TITLE') ?>
            </h1>
        </div>

        <?php if (!empty($search_news)) { ?>
            <ul id="items">
                <?php foreach ($search_news as $k => $v) { ?>
                    <li class="col-md-4 animated fadeInUp">
                        <div class="bloc <?= h(rand_color_news()) ?>" style="width:100%;">
                            <h2><?= h(cut((string)($v['title'] ?? ''), 15)) ?></h2>
                            <div><?= $this->Text->truncate((string)($v['content'] ?? ''), 220, ['ellipsis' => '...', 'html' => true]) ?></div>

                            <div class="btn-group">
                                <button id="<?= h((string)($v['id'] ?? '')) ?>" type="button"
                                        class="btn btn-primary like<?= (!empty($v['liked']) ? ' active' : '') ?>"<?= (!empty($can_like) ? '' : ' disabled') ?>>
                                    <?= h((string)($v['count_likes'] ?? 0)) ?> <i class="fa fa-thumbs-up"></i>
                                </button>
                                <button type="button" class="btn btn-primary">
                                    <?= h((string)($v['count_comments'] ?? 0)) ?> <i class="fa fa-comments"></i>
                                </button>
                            </div>

                            <a href="<?= $this->Url->build('/blog/' . (string)($v['slug'] ?? '')) ?>"
                               class="btn btn-success pull-right"><?= __('NEWS__READ_MORE') ?> »</a>
                        </div>
                    </li>
                <?php } ?>
            </ul>
            <ol id="pagination"></ol>
        <?php } else { ?>
            <center><h3><?= __('NEWS__NONE_PUBLISHED') ?></h3></center>
        <?php } ?>
    </div>

    <div class="row btn-socials text-center">
        <?php
        $findSocialButtons = $this->SocialButton->all();
        $howManyBtns = count($findSocialButtons);

        $maxBtnsByLine = 4;
        $col = 12;

        if ($howManyBtns > 0) {
            $howManyBtnsDivided = (int)ceil($howManyBtns / (int)ceil($howManyBtns / $maxBtnsByLine));
            $col = (int)(12 / max(1, $howManyBtnsDivided));
        }

        foreach ($findSocialButtons as $value) {
            $color = (string)($value['color'] ?? '');
            $url = (string)($value['url'] ?? '#');
            $extra = (string)($value['extra'] ?? '');
            $title = (string)($value['title'] ?? '');

            echo '<div class="col-md-' . (int)$col . ' text-center">';
            echo '<a class="btn btn-default btn-block btn-lg" style="background-color:' . h($color) . ';color:white;font-size:18px;" target="_blank" href="' . h($url) . '">';

            if ($extra !== '') {
                if (strpos($extra, 'fa-') !== false) {
                    echo '<i class="' . h($extra) . '"></i>';
                } else {
                    echo '<img src="' . h($extra) . '" alt="' . h((string)__("SOCIAL__BUTTON_IMG_ALT")) . h($title) . '">';
                }
            }

            if ($title !== '') {
                echo ' ' . h($title);
            }

            echo '</a></div>';
        }
        ?>
    </div>

    <?= $this->Module->load('home') ?>
</div>
