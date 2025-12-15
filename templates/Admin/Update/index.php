<section class="content">
    <div class="container-fluid">
        <?php
        $latest = (string)$this->Update->cmsLastVersion();
        $current = (string)$this->Update->cmsVersion();

        $latestMajor = (int)explode('.', $latest)[0];
        $currentMajor = (int)explode('.', $current)[0];
        $isMajor = $latestMajor > $currentMajor;

        $clearCacheUrl = $this->Url->build(['_name' => 'admin_update_clear_cache']);
        $checkUrl = $this->Url->build(['_name' => 'admin_update_check']);
        $updateUrl = $this->Url->build(['_name' => 'admin_update_update']);
        $changelogUrl = 'https://github.com/MineWeb/MineWebCMS/releases';
        ?>

        <div class="row">
            <div class="col-12">
                <div class="card card-outline card-primary shadow-sm">
                    <div class="card-header">
                        <h3 class="card-title d-flex align-items-center">
                            <i class="fas fa-sync-alt mr-2"></i>
                            <?= __('GLOBAL__UPDATE') ?>
                        </h3>

                        <div class="card-tools">
                            <button id="btn-update" type="button" class="btn btn-primary btn-sm">
                                <i class="fas fa-download mr-1"></i><?= __('GLOBAL__UPDATE') ?>
                            </button>
                        </div>
                    </div>

                    <div class="card-body">
                        <div class="overlay-wrapper">
                            <div id="update-overlay" class="overlay dark d-none">
                                <i class="fas fa-2x fa-sync-alt fa-spin"></i>
                            </div>

                            <div class="row align-items-stretch">
                                <div class="col-lg-6 d-flex">
                                    <div class="info-box bg-light w-100 h-100 mb-3">
                                        <span class="info-box-icon bg-info elevation-1">
                                            <i class="fas fa-code-branch"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted text-uppercase"><?= __('UPDATE__CMS_VERSION') ?></span>
                                            <span class="info-box-number"><?= h($current) ?></span>
                                        </div>
                                    </div>
                                </div>

                                <div class="col-lg-6 d-flex">
                                    <div class="info-box bg-light w-100 h-100 mb-3">
                                        <span class="info-box-icon bg-success elevation-1">
                                            <i class="fas fa-cloud-download-alt"></i>
                                        </span>
                                        <div class="info-box-content">
                                            <span class="info-box-text text-muted text-uppercase"><?= __('UPDATE__LAST_VERSION') ?></span>
                                            <span class="info-box-number"><?= h($latest) ?></span>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <?php if ($isMajor) { ?>
                                <div class="callout callout-warning mb-3">
                                    <h5 class="mb-2 d-flex align-items-center">
                                        <i class="fas fa-exclamation-triangle mr-2"></i>
                                        <?= __('UPDATE__MAJOR_WARNING') ?>
                                    </h5>
                                    <p class="mb-0"><?= __('UPDATE__MAJOR_WARNING_EXTENSION') ?></p>
                                </div>
                            <?php } ?>

                            <div id="update-msg"></div>

                            <div class="mt-3">
                                <div id="update-progress" class="progress progress-sm d-none">
                                    <div id="update-progress-bar" class="progress-bar progress-bar-striped progress-bar-animated bg-primary" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card-footer d-flex flex-wrap justify-content-end align-items-center">
                        <div class="btn-group">
                            <a class="btn btn-outline-secondary btn-sm" href="<?= h($checkUrl) ?>">
                                <i class="fas fa-search mr-1"></i><?= __('UPDATE__CHECK_STATUS') ?>
                            </a>
                            <a class="btn btn-outline-secondary btn-sm" href="<?= h($clearCacheUrl) ?>">
                                <i class="fas fa-broom mr-1"></i><?= __('UPDATE__CLEAR_CACHE') ?>
                            </a>
                            <a class="btn btn-outline-secondary btn-sm" target="_blank" rel="noopener noreferrer" href="<?= h($changelogUrl) ?>">
                                <i class="fas fa-external-link-alt mr-1"></i><?= __('UPDATE__VIEW_CHANGELOG') ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<script type="text/javascript">
    (function () {
        const btn = document.getElementById('btn-update');
        const msg = document.getElementById('update-msg');
        const progress = document.getElementById('update-progress');
        const bar = document.getElementById('update-progress-bar');
        const overlay = document.getElementById('update-overlay');

        const csrf = "<?= $this->request->getAttribute('csrfToken') ?>";
        const baseUrl = "<?= $updateUrl ?>";
        const clearCacheUrl = "<?= $clearCacheUrl ?>";

        function setMsg(type, html) {
            msg.innerHTML = '<div class="alert alert-' + type + ' mb-0">' + html + '</div>';
        }

        function setBusy(busy) {
            if (busy) {
                btn.setAttribute('disabled', 'disabled');
                btn.classList.add('disabled');
                overlay.classList.remove('d-none');
                progress.classList.remove('d-none');
                bar.style.width = '12%';
                return;
            }

            btn.removeAttribute('disabled');
            btn.classList.remove('disabled');
            overlay.classList.add('d-none');
            bar.style.width = '0%';
            progress.classList.add('d-none');
        }

        async function callUpdate(step) {
            const url = baseUrl + '/' + String(step || '0');

            const response = await fetch(url, {
                method: 'POST',
                headers: {
                    'X-CSRF-Token': csrf,
                    'Accept': 'application/json'
                },
                body: new FormData()
            });

            return response.json();
        }

        async function runUpdate() {
            try {
                setBusy(true);
                setMsg('info', "<?= addslashes(__('UPDATE__LOADING')) ?>");

                let data = await callUpdate('0');

                if (data && data.statut === 'continue') {
                    bar.style.width = '55%';
                    data = await callUpdate('1');
                }

                if (data && data.statut === 'success') {
                    bar.style.width = '100%';
                    setMsg('success', '<b><?= addslashes(__('GLOBAL__SUCCESS')) ?> :</b> ' + (data.msg || ''));
                    window.location = clearCacheUrl;
                    return;
                }

                if (data && data.statut === 'error') {
                    setMsg('danger', '<b><?= addslashes(__('GLOBAL__ERROR')) ?> :</b> ' + (data.msg || ''));
                    setBusy(false);
                    return;
                }

                setMsg('danger', '<b><?= addslashes(__('GLOBAL__ERROR')) ?> :</b> <?= addslashes(__('ERROR__INTERNAL_ERROR')) ?>');
                setBusy(false);
            } catch (e) {
                setMsg('danger', '<b><?= addslashes(__('GLOBAL__ERROR')) ?> :</b> <?= addslashes(__('ERROR__INTERNAL_ERROR')) ?>');
                setBusy(false);
            }
        }

        btn.addEventListener('click', runUpdate);
    })();
</script>
