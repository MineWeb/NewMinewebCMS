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
<div class="alert alert-info alert-dismissible" role="alert">
    <strong><?= __('GLOBAL__INFO') ?> :</strong> <?php echo h($message); ?>
</div>
