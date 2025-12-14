<section class="content">
    <div class="row">
        <div class="col-md-12">

            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title" style="width:100%;">
                        <?= __('GLOBAL__UPDATE') ?>
                    </h3>
                </div>

                <div class="card-body">

                    <div style="text-align:center">

                        <p><?= __('UPDATE__LAST_VERSION') ?> : <?= h($this->Update->cmsLastVersion()) ?></p>
                        <p><?= __('UPDATE__CMS_VERSION') ?> : <?= h($this->Update->cmsVersion()) ?></p>

                        <?php if ((int)explode('.', $this->Update->cmsLastVersion())[0] > (int)explode('.', $this->Update->cmsVersion())[0]): ?>
                            <div class="alert alert-warning">
                                <?= __('UPDATE__MAJOR_WARNING') ?>
                            </div>
                        <?php endif; ?>

                        <div class="btn-group">

                            <button id="btn-update" class="btn btn-large btn-primary">
                                <?= __('GLOBAL__UPDATE') ?>
                            </button>

                            <a class="btn btn-warning"
                               href="<?= $this->Url->build(['_name' => 'admin_update_clear_cache']) ?>">
                                <?= __('UPDATE__CLEAR_CACHE') ?>
                            </a>

                            <a class="btn btn-large btn-info"
                               href="<?= $this->Url->build(['_name' => 'admin_update_check']) ?>">
                                <?= __('UPDATE__CHECK_STATUS') ?>
                            </a>

                            <a href="https://github.com/MineWeb/MineWebCMS/releases"
                               target="_blank"
                               class="btn btn-large btn-default">
                                <?= __('UPDATE__VIEW_CHANGELOG') ?>
                            </a>
                        </div>

                        <div id="update-msg" style="margin-top:15px"></div>

                        <div id="update-progress" class="progress progress-striped active" style="display:none;">
                            <div class="bar" style="width:40%"></div>
                        </div>

                    </div>

                </div>

            </div>

        </div>
    </div>
</section>

<script type="text/javascript">

    async function callUpdate(step = '0') {

        const csrf = "<?= $this->request->getAttribute('csrfToken') ?>";

        const url = "<?= $this->Url->build(['_name' => 'admin_update_update']) ?>/" + step;

        try {
            const response = await fetch(url, {
                method: "POST",
                headers: {
                    "X-CSRF-Token": csrf,
                    "Accept": "application/json"
                },
                body: new FormData()
            });

            const data = await response.json();

            if (data.statut === "success") {

                document.getElementById('update-msg').innerHTML =
                    '<div class="alert alert-success"><b><?= __('GLOBAL__SUCCESS') ?> :</b> ' + data.msg + '</div>';

                window.location = "<?= $this->Url->build(['_name' => 'admin_update_clear_cache']) ?>";

            } else if (data.statut === "continue") {

                callUpdate('1');

            } else if (data.statut === "error") {

                document.getElementById('update-msg').innerHTML =
                    '<div class="alert alert-danger"><b><?= __('GLOBAL__ERROR') ?> :</b> ' + data.msg + '</div>';

            } else {
                alert("Error");
            }

        } catch (e) {
            alert("Error");
        }
    }

    document.getElementById("btn-update").addEventListener("click", () => {
        const msg = document.getElementById("update-msg");
        msg.innerHTML = '<div class="alert alert-info"><?= __('UPDATE__LOADING') ?></div>';
        callUpdate();
    });

</script>
