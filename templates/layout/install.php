<!DOCTYPE html>
<html lang="fr">
<head>

    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="description" content="">
    <meta name="author" content="Eywek">

    <title><?= isset($title_for_layout) ? h($title_for_layout) . ' - MineWeb' : 'MineWeb'; ?></title>

    <?= $this->Html->css('bootstrap') ?>
    <?= $this->Html->css('install') ?>
    <?= $this->Html->css('custom') ?>
    <?= $this->Html->css('prettify') ?>
    <?= $this->Html->css('font-awesome.min') ?>

    <link href="https://fonts.googleapis.com/css?family=Roboto:400,400italic,300,300italic,700,700italic"
          rel="stylesheet" type="text/css">

    <?= $this->Html->css('install/flat') ?>
    <?= $this->Html->css('install/animate.min') ?>
    <?= $this->Html->css('install/install') ?>

</head>
<body>
<div class="page-container">
    <div class="container">
        <div class="row row-offcanvas row-offcanvas-left">
            <?= $this->fetch('content'); ?>
        </div>
    </div>
</div>

<script>

    let TEXT__LOADING = "Chargement..."
    let TEXT__ERROR = "Erreur"
    let TEXT__INTERNAL_ERROR = "Une erreur interne est survenue"
</script>


<?= $this->Html->script('jquery-1.11.0') ?>
<?= $this->Html->script('bootstrap') ?>
<?= $this->Html->script('jquery.bootstrap.wizard.min') ?>
<?= $this->Html->script('prettify') ?>
<?= $this->Html->script('install/install') ?>
<?= $this->Html->script('install/requirements') ?>


</body>
</html>
