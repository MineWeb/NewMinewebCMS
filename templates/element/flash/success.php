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
<div class="alert alert-success alert-dismissible" role="alert">
    <strong><?= (__('GLOBAL__SUCCESS') !== null) ? __('GLOBAL__SUCCESS') : 'Success' ?> :</strong> <?php echo h($message); ?>
</div>
