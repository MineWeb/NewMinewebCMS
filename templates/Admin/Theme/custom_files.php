<section class="content">
    <div class="row">

        <div class="col-md-3">
            <div class="card">
                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('THEME__CUSTOM_FILES_FILES') ?></h3>
                </div>

                <div class="card-body">
                    <ul>
                        <?php foreach ($css_files as $file): ?>
                            <li class="file text-muted">
                                <a
                                    href="#"
                                    class="viewFile"
                                    data-file="<?= h($file['basename']) ?>"
                                    data-filename="<?= h($file['name']) ?>"
                                >
                                    <?= h($file['basename']) ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>


        <div class="col-md-9">
            <div class="card">

                <div class="card-header with-border">
                    <h3 class="card-title"><?= __('THEME__CUSTOM_FILES_FILE_CONTENT') ?></h3>
                </div>

                <div class="card-body" style="position:relative;height:1000px;">

                    <p id="content">
                        <i class="text-muted"><?= __('THEME__CUSTOM_FILES_FILE_CONTENT_CHOOSE') ?></i>
                    </p>

                    <div class="clearfix"></div>

                    <?= $this->Form->create(null, [
                        'url' => ['_name' => 'admin_theme_save_custom_file', $slug],
                        'data-ajax' => 'true',
                        'data-custom-function' => 'getFileContent'
                    ]) ?>

                    <div class="ajax-msg"></div>

                    <button
                        id="saveButton"
                        type="submit"
                        class="btn btn-primary"
                        style="display:none;"
                    >
                        <?= __('GLOBAL__SAVE') ?>
                    </button>

                    <?= $this->Form->end() ?>

                </div>
            </div>
        </div>

    </div>
</section>

<div style="height:30px"></div>

<style>
    #saveButton {
        bottom: -40px;
        position: absolute;
        right: 0;
    }

    .ajax-msg div {
        padding: 10px;
    }

    .ajax-msg {
        bottom: -70px;
        position: absolute;
        left: 0;
    }

    ul {
        padding-left: 0;
    }

    ul li {
        list-style-type: none;
    }

    ul li a {
        color: inherit;
        text-decoration: none;
    }

    ul li a:hover {
        color: black;
    }

    ul li.file:before {
        content: "\f15b\00a0\00a0\00a0";
        font-family: 'FontAwesome';
    }

    #editor {
        position: absolute;
        top: 0;
        right: 0;
        bottom: 0;
        left: 0;
    }
</style>

<?= $this->Html->script('ace') ?>

<script>
    document.querySelectorAll('.viewFile').forEach(el => {
        el.addEventListener('click', function (e) {
            e.preventDefault();

            const file = this.dataset.file;
            const filename = this.dataset.filename;

            fetch('<?= $this->Url->build(['_name' => 'admin_theme_get_custom_file', $slug]) ?>' + file)
                .then(r => r.text())
                .then(data => {
                    document.querySelector('#content').innerHTML = '<div id="editor">' + data + '</div>';

                    const editor = ace.edit("editor");
                    editor.setTheme("ace/theme/monokai");
                    editor.getSession().setMode("ace/mode/css");

                    const btn = document.querySelector('#saveButton');
                    btn.dataset.file = file;
                    btn.style.display = 'inline-block';
                })
                .catch(() => {
                    alert('<?= __('ERROR__INTERNAL_ERROR') ?>');
                });
        });
    });

    function getFileContent() {
        return {
            file: document.querySelector('#saveButton').dataset.file,
            content: ace.edit("editor").getValue()
        };
    }
</script>
