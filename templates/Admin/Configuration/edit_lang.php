<section class="content">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('CONFIG__LANG_LABEL') ?></h3>
                </div>
                <div class="card-body">

                    <?= $this->Form->create(null, ['type' => 'post']) ?>

                    <div class="ajax-msg"></div>

                    <?php foreach ($messages as $key => $value) { ?>
                        <?php if ($key !== 'FOOTER_ADMIN') { ?>
                            <?php $inputId = 'lang_' . strtolower($key); ?>

                            <div class="form-group">
                                <label for="<?= $inputId ?>">
                                    <?= explode('-', $key)[0] ?>
                                </label>

                                <?php if ($key !== 'RESET_PASSWORD_MAIL') { ?>
                                    <input
                                        type="text"
                                        id="<?= $inputId ?>"
                                        name="<?= $key ?>"
                                        class="form-control"
                                        value="<?= htmlentities($value) ?>"
                                    >
                                <?php } else { ?>
                                    <textarea
                                        id="<?= $inputId ?>"
                                        name="<?= $key ?>"
                                        class="form-control"
                                        cols="30"
                                        rows="10"
                                    ><?= $value ?></textarea>
                                <?php } ?>

                                <?php if ($key === 'GLOBAL__FORMAT_DATE') { ?>
                                    <small>
                                        <?= __('CONFIG__LANG_AVAILABLE_letIABLES') ?> :
                                        {%day}, {%month}, {%year}, {%hour|24}, {%hour|12}, {%minutes}
                                    </small>
                                <?php } ?>

                                <?php if ($key === 'SERVER__STATUS_MESSAGE') { ?>
                                    <small>
                                        <?= __('CONFIG__LANG_AVAILABLE_letIABLES') ?> :
                                        {MOTD}, {VERSION}, {ONLINE}, {ONLINE_LIMIT}
                                    </small>
                                <?php } ?>

                                <?php if ($key === 'VOTE_SUCCESS_SERVER') { ?>
                                    <small>
                                        <?= __('CONFIG__LANG_AVAILABLE_letIABLES') ?> :
                                        {PLAYER}
                                    </small>
                                <?php } ?>

                                <?php if ($key === 'RESET_PASSWORD_MAIL') { ?>
                                    <small>
                                        <?= __('CONFIG__LANG_AVAILABLE_letIABLES') ?> :
                                        {EMAIL}, {USERNAME}, {LINK}
                                    </small>
                                <?php } ?>

                                <?php if ($key === 'COPYRIGHT') { ?>
                                    <small><?= __('CONFIG__INFO_LANG') ?></small>
                                <?php } ?>
                            </div>
                        <?php } ?>
                    <?php } ?>

                    <div class="float-right">
                        <button class="btn btn-primary" type="submit">
                            <?= __('GLOBAL__SUBMIT') ?>
                        </button>
                    </div>

                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>
    </div>
</section>
