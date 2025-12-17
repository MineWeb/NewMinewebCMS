<?php
/**
 * @var \App\View\AppView $this
 * @var string $message
 * @var array $params
 */

$type = $params['type'] ?? 'info';

$map = [
    'success' => ['class' => 'alert-success', 'icon' => 'fas fa-check', 'title' => __('GLOBAL__SUCCESS')],
    'error' => ['class' => 'alert-danger', 'icon' => 'fas fa-times', 'title' => __('GLOBAL__ERROR')],
    'warning' => ['class' => 'alert-warning', 'icon' => 'fas fa-exclamation', 'title' => __('GLOBAL__WARNING')],
    'info' => ['class' => 'alert-info', 'icon' => 'fas fa-circle-notch', 'title' => __('GLOBAL__INFO')],
];

$cfg = $map[$type] ?? $map['info'];

if (!isset($params['escape']) || $params['escape'] !== false) {
    $message = h($message);
}
?>
<div class="alert <?= h($cfg['class']) ?> alert-dismissible">
    <button type="button" class="close" data-dismiss="alert" aria-label="<?= __('Close') ?>">
        <span aria-hidden="true">&times;</span>
    </button>

    <h5 class="mb-1">
        <i class="<?= h($cfg['icon']) ?> mr-2"></i><?= h($cfg['title']) ?>
    </h5>

    <div class="mb-0">
        <?= $message ?>
    </div>
</div>
