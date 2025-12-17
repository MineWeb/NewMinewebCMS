<section class="content">
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">

                <div class="card card-outline card-primary">
                    <div class="card-header">
                        <div class="d-flex align-items-center justify-content-between flex-wrap" style="gap: 10px;">

                            <div class="d-flex align-items-center flex-wrap" style="gap: 10px;">
                                <h3 class="card-title mb-0">
                                    <i class="fas fa-tools mr-2"></i><?= __('MAINTENANCE__TITLE') ?>
                                </h3>

                                <span class="badge badge-light border">
                                    <i class="fas fa-list mr-1"></i><?= is_countable($pages) ? count($pages) : 0 ?> <?= __('TABLE__ITEMS') ?>
                                </span>
                            </div>

                            <a class="btn btn-primary btn-sm" href="<?= $this->Url->build(['_name' => 'admin_maintenance_add']) ?>">
                                <i class="fas fa-plus mr-2"></i><?= __('MAINTENANCE__ADD_PAGE') ?>
                            </a>
                        </div>
                    </div>

                    <div class="card-body p-0">
                        <div class="p-3 p-md-4">

                            <div class="table-responsive">
                                <table class="table table-hover table-striped mb-0">
                                    <thead class="thead-light">
                                    <tr>
                                        <th><?= __("MAINTENANCE__PAGE") ?></th>
                                        <th><?= __("MAINTENANCE__REASON") ?></th>
                                        <th style="width: 1%; white-space: nowrap;"><?= __("MAINTENANCE__SUB_URL") ?></th>
                                        <th style="width: 1%; white-space: nowrap;"><?= __("GLOBAL__STATUS") ?></th>
                                        <th class="text-right" style="width: 1%; white-space: nowrap;"><?= __("GLOBAL__ACTIONS") ?></th>
                                    </tr>
                                    </thead>

                                    <tbody>
                                    <?php if (empty($pages) || count($pages) === 0) : ?>
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                <i class="far fa-folder-open mr-2"></i><?= __('TABLE__NO_RESULT') ?>
                                            </td>
                                        </tr>
                                    <?php else : ?>
                                        <?php foreach ($pages as $v) : ?>
                                            <?php
                                            $url = (string)($v['url'] ?? '');
                                            $reason = (string)($v['reason'] ?? '');
                                            $subUrl = !empty($v['sub_url']);
                                            $active = ((int)($v['active'] ?? 0)) === 1;

                                            $editUrl = $this->Url->build(['_name' => 'admin_maintenance_edit', $v['id']]);
                                            $deleteUrl = $this->Url->build(['_name' => 'admin_maintenance_delete', $v['id']]);
                                            $toggleUrl = $active
                                                ? $this->Url->build(['_name' => 'admin_maintenance_disable', $v['id']])
                                                : $this->Url->build(['_name' => 'admin_maintenance_enable', $v['id']]);
                                            ?>
                                            <tr>
                                                <td class="align-middle">
                                                    <span class="font-weight-bold"><?= h($url) ?></span>
                                                    <?php if ($url === '') : ?>
                                                        <span class="text-muted ml-2">(<?= __('MAINTENANCE__ADD_EMPTY_URL') ?>)</span>
                                                    <?php endif; ?>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="text-truncate d-inline-block" style="max-width: 520px;" title="<?= h(strip_tags($reason)) ?>">
                                                        <?= h(strip_tags($reason)) ?>
                                                    </span>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="badge badge-<?= $subUrl ? 'info' : 'light' ?>">
                                                        <?= $subUrl ? __("GLOBAL__YES") : __("GLOBAL__NO") ?>
                                                    </span>
                                                </td>

                                                <td class="align-middle">
                                                    <span class="badge badge-<?= $active ? 'success' : 'secondary' ?>">
                                                        <?= $active ? __("GLOBAL__ENABLED") : __("GLOBAL__DISABLED") ?>
                                                    </span>
                                                </td>

                                                <td class="align-middle text-right text-nowrap">
                                                    <div class="btn-group btn-group-sm" role="group" aria-label="<?= h(__('GLOBAL__ACTIONS')) ?>">
                                                        <a href="<?= h($editUrl) ?>" class="btn btn-info">
                                                            <i class="fas fa-edit mr-1"></i><?= __('GLOBAL__EDIT') ?>
                                                        </a>

                                                        <?php if ($active) : ?>
                                                            <a onClick="confirmDel('<?= h($toggleUrl) ?>')" class="btn btn-warning">
                                                                <i class="fas fa-ban mr-1"></i><?= __('GLOBAL__DISABLE') ?>
                                                            </a>
                                                        <?php else : ?>
                                                            <a onClick="confirmDel('<?= h($toggleUrl) ?>')" class="btn btn-success">
                                                                <i class="fas fa-check mr-1"></i><?= __('GLOBAL__ENABLE') ?>
                                                            </a>
                                                        <?php endif; ?>

                                                        <a onClick="confirmDel('<?= h($deleteUrl) ?>')" class="btn btn-danger">
                                                            <i class="fas fa-trash mr-1"></i><?= __('GLOBAL__DELETE') ?>
                                                        </a>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                        </div>
                    </div>

                </div>

            </div>
        </div>
    </div>
</section>
