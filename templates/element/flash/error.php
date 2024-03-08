<?php
/**
 * @var \App\View\AppView $this
 * @var array $params
 * @var string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <strong><?= (isset($Lang)) ? $Lang->get('GLOBAL__ERROR') : 'Error' ?> :</strong> <?php echo h($message); ?>
</div>
