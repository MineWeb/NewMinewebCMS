<?php
declare(strict_types=1);

$canUpdate = $this->Auth->can('MANAGE_UPDATE');
$updateUrl = $canUpdate ? $this->Url->build(['_name' => 'admin_update_index']) : '#';

$renderItems = function (array $items) use (&$renderItems, $updateUrl): void {
    foreach ($items as $item) {
        $perm = $item['permission'] ?? null;
        if ($perm && !$this->Auth->can($perm)) {
            continue;
        }

        if (!empty($item['url']) && (string)$item['url'] === (string)$updateUrl) {
            continue;
        }

        $hasChildren = !empty($item['children']);
        $liClass = $hasChildren ? 'nav-item has-treeview' : 'nav-item';
        if (!empty($item['open'])) {
            $liClass .= ' menu-open';
        }

        $aClass = 'nav-link';
        if (!empty($item['active']) || !empty($item['open'])) {
            $aClass .= ' active';
        }

        $icon = (string)($item['icon'] ?? '');
        $iconClass = strpos($icon, 'fa-') !== false ? $icon : 'fa fa-' . $icon;

        echo '<li class="' . h($liClass) . '">';
        echo '<a class="' . h($aClass) . '" href="' . h((string)$item['url']) . '">';
        echo '<i class="' . h($iconClass) . ' nav-icon"></i>';
        echo '<p>' . h(__($item['label']));
        if ($hasChildren) {
            echo '<i class="fas fa-angle-left right"></i>';
        }
        echo '</p>';
        echo '</a>';

        if ($hasChildren) {
            echo '<ul class="nav nav-treeview">';
            $renderItems($item['children']);
            echo '</ul>';
        }

        echo '</li>';
    }
};
?>

<aside class="main-sidebar sidebar-dark-lightblue elevation-4">
    <a href="<?= $this->Url->build(['_name' => 'home']) ?>" class="brand-link navbar-lightblue text-center text-white">
        <span class="brand-text font-weight-bold"><?= __('GLOBAL__ADMINISTRATION') ?></span>
    </a>

    <div class="sidebar">
        <nav class="mt-2 mb-2">
            <ul class="nav nav-pills nav-sidebar flex-column nav-child-indent" data-widget="treeview"
                role="menu" data-accordion="false">
                <?php $renderItems($items ?? []); ?>
            </ul>
            <?php if ($canUpdate) : ?>
                <ul class="nav nav-pills nav-sidebar flex-column mt-3">
                    <li class="nav-item">
                        <a href="<?= h($updateUrl) ?>" class="nav-link bg-white text-dark">
                            <i class="fas fa-sync-alt nav-icon"></i>
                            <p class="font-weight-bold">
                                <?= __('GLOBAL__UPDATE') ?>
                                <i class="fas fa-arrow-right right"></i>
                            </p>
                        </a>
                    </li>
                </ul>

            <?php endif; ?>
        </nav>


    </div>
</aside>
