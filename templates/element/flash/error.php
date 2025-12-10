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
    <strong><?= __('GLOBAL__ERROR') ?> :</strong> <?php echo h($message); ?>
</div>
