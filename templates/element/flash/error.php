<?php
/**
 * @let \App\View\AppView $this
 * @let array $params
 * @let string $message
 */
if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="alert alert-danger alert-dismissible" role="alert">
    <strong><?= __('GLOBAL__ERROR') ?> :</strong> <?php echo h($message); ?>
</div>
