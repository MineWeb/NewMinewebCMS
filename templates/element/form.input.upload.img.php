<?php
declare(strict_types=1);

/**
 * Element: form.input.upload.img.php
 *
 * Options:
 * - uid: string (optional) unique prefix to avoid id collisions
 * - field: string (optional) base field name for inputs (default: "img")
 * - img: string (optional) current image url (already saved)
 * - filename: string (optional) current image file name to display (optional, else derived from img url)
 * - title: string (optional) label text to display above the picker
 * - description: string (optional) helper/description text shown under label
 * - showLabel: bool (optional) default true if title/description provided, else false
 */

$uid = $uid ?? ('upload-img-' . bin2hex(random_bytes(4)));

$field = isset($field) && $field !== '' ? (string)$field : 'img';

$nameFile = $field . '[file]';
$nameEdit = $field . '[edit]';
$nameUploaded = $field . '[uploaded]';
$nameUrl = $field . '[url]';
$nameDelete = $field . '[delete]';

$imgSrc = !empty($img) ? (string)$img : '';
$providedCurrentName = (isset($filename) && $filename !== '') ? (string)$filename : '';

$labelText = isset($title) && $title !== '' ? (string)$title : '';
$descText = isset($description) && $description !== '' ? (string)$description : '';

$showLabel = isset($showLabel)
    ? (bool)$showLabel
    : ($labelText !== '' || $descText !== '');

$rootId = $uid . '-root';
$fileId = $uid . '-file';

$currentPreviewId = $uid . '-current-preview';
$currentPlaceholderId = $uid . '-current-placeholder';
$currentNameId = $uid . '-current-name';

$newPreviewId = $uid . '-new-preview';
$newPlaceholderId = $uid . '-new-placeholder';
$newNameId = $uid . '-new-name';

$deleteNewId = $uid . '-delete-new';
$deleteCurrentId = $uid . '-delete-current';
$modalId = $uid . '-gallery';

$labelId = $uid . '-label';
$descId = $uid . '-desc';

$hasCurrent = $imgSrc !== '';

$currentDisplayName = $providedCurrentName;
if ($currentDisplayName === '' && $hasCurrent) {
    $path = (string)parse_url($imgSrc, PHP_URL_PATH);
    $base = $path !== '' ? basename($path) : '';
    $currentDisplayName = $base !== '' ? $base : __('FORM__CURRENT_IMAGE');
}
?>

<div class="form-group mb-0">
    <?php if ($showLabel) { ?>
        <?php if ($labelText !== '') { ?>
            <label id="<?= h($labelId) ?>" for="<?= h($fileId) ?>" class="mb-1">
                <?= h($labelText) ?>
            </label>
        <?php } ?>
        <?php if ($descText !== '') { ?>
            <div id="<?= h($descId) ?>" class="text-muted text-sm mb-2">
                <?= $descText ?>
            </div>
        <?php } ?>
    <?php } ?>

    <div id="<?= h($rootId) ?>" class="border rounded p-2 p-sm-3 bg-light">

        <input type="hidden" name="<?= h($nameUploaded) ?>" value="">
        <input type="hidden" name="<?= h($nameUrl) ?>" value="">
        <input type="hidden" name="<?= h($nameDelete) ?>" value="0">

        <div class="row">

            <?php if ($hasCurrent) { ?>
            <div class="col-12 col-lg-6 mb-3 mb-lg-0">
                <div class="border rounded bg-white p-2 h-100">

                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge badge-info"><?= __('FORM__CURRENT_IMAGE') ?></span>

                        <div class="btn-group">
                            <a class="btn btn-outline-info btn-xs" href="<?= h($imgSrc) ?>" target="_blank" rel="noopener">
                                <i class="fas fa-external-link-alt mr-1"></i><?= __('FORM__OPEN') ?>
                            </a>
                            <button
                                id="<?= h($deleteCurrentId) ?>"
                                type="button"
                                class="btn btn-outline-danger btn-xs"
                                title="<?= h(__('FORM__REMOVE_CURRENT_IMAGE')) ?>"
                            >
                                <i class="far fa-trash-alt mr-1"></i><?= __('FORM__DELETE') ?>
                            </button>
                        </div>
                    </div>

                    <div class="border rounded d-flex align-items-center justify-content-center" style="height: 160px; overflow:hidden; background:#f8f9fa;">
                        <div
                            id="<?= h($currentPlaceholderId) ?>"
                            class="d-none"
                            style="width: 100%; height: 100%;"
                            aria-hidden="true"
                        ></div>

                        <img
                            id="<?= h($currentPreviewId) ?>"
                            src="<?= h($imgSrc) ?>"
                            class="img-fluid"
                            alt=""
                            style="max-height: 100%;"
                        >
                    </div>

                    <div class="mt-2">
                        <div class="text-muted text-sm">
                            <i class="far fa-file mr-1"></i>
                            <a
                                id="<?= h($currentNameId) ?>"
                                href="<?= h($imgSrc) ?>"
                                target="_blank"
                                rel="noopener"
                                title="<?= h($imgSrc) ?>"
                            ><?= h($currentDisplayName) ?></a>
                        </div>

                        <div class="text-muted text-sm mt-1">
                            <i class="fas fa-info-circle mr-1"></i><?= __('FORM__CURRENT_IMAGE_HELP') ?>
                        </div>
                    </div>

                </div>
            </div>

            <div class="col-12 col-lg-6">
                <?php } else { ?>
                <div class="col-12">
                    <?php } ?>

                    <div class="border rounded bg-white p-2 h-100">

                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <span class="badge badge-warning"><?= __('FORM__NEW_IMAGE') ?></span>

                            <button
                                id="<?= h($deleteNewId) ?>"
                                type="button"
                                class="btn btn-outline-danger btn-xs d-none"
                                title="<?= h(__('FORM__CLEAR_NEW_SELECTION')) ?>"
                            >
                                <i class="far fa-trash-alt mr-1"></i><?= __('FORM__CLEAR') ?>
                            </button>
                        </div>

                        <div class="border rounded d-flex align-items-center justify-content-center mb-2" style="height: 160px; overflow:hidden; background:#f8f9fa;">
                            <div
                                id="<?= h($newPlaceholderId) ?>"
                                class=""
                                style="width: 100%; height: 100%;"
                                aria-hidden="true"
                            ></div>

                            <img
                                id="<?= h($newPreviewId) ?>"
                                src=""
                                class="img-fluid d-none"
                                alt=""
                                style="max-height: 100%;"
                            >
                        </div>

                        <div class="text-muted text-sm mb-2">
                            <i class="far fa-file mr-1"></i>
                            <span id="<?= h($newNameId) ?>"><?= __('FORM__NO_FILE_SELECTED') ?></span>
                        </div>

                        <div class="d-flex flex-column">
                            <div class="btn btn-primary btn-sm text-left mb-2" style="position:relative; overflow:hidden;">
                                <i class="fas fa-upload mr-1"></i>
                                <?= __('FORM__BROWSE') ?>&hellip;
                                <input
                                    id="<?= h($fileId) ?>"
                                    name="<?= h($nameFile) ?>"
                                    type="file"
                                    style="position:absolute; inset:0; opacity:0; cursor:pointer;"
                                    aria-label="<?= h(__('FORM__BROWSE')) ?>"
                                    <?php if ($showLabel && $labelText !== '') { ?>
                                        aria-labelledby="<?= h($labelId) ?>"
                                    <?php } ?>
                                    <?php if ($showLabel && $descText !== '') { ?>
                                        aria-describedby="<?= h($descId) ?>"
                                    <?php } ?>
                                >
                            </div>

                            <a class="btn btn-default btn-sm text-left" data-toggle="modal" href="#<?= h($modalId) ?>" role="button">
                                <i class="far fa-images mr-1"></i>
                                <?= __('FORM__CHOOSE_FROM_UPLOADED_FILES') ?>&hellip;
                            </a>

                            <small class="text-muted mt-2">
                                <i class="fas fa-eye mr-1"></i><?= __('FORM__NEW_IMAGE_HINT') ?>
                            </small>
                        </div>

                    </div>
                </div>

            </div>
        </div>
    </div>

    <div class="modal fade" id="<?= h($modalId) ?>" tabindex="-1" role="dialog" aria-labelledby="<?= h($modalId) ?>-title" aria-hidden="true">
        <div class="modal-dialog modal-lg" role="document">
            <div class="modal-content">

                <div class="modal-header">
                    <h5 class="modal-title" id="<?= h($modalId) ?>-title"><?= __('GALLERY__MODAL_TITLE') ?></h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>

                <div class="modal-body">
                    <p class="text-muted mb-3"><?= __('GALLERY__PARAGRAPH') ?></p>

                    <div class="row">
                        <?php
                        $files = findRecursive(ROOT . DS . 'webroot' . DS . 'img' . DS . 'uploads', ['png', 'jpg', 'jpeg', 'gif']);

                        foreach ($files as $path) {
                            $file = new SplFileObject($path);
                            $basename = substr($path, strlen(ROOT . DS . 'webroot' . DS . 'img' . DS . 'uploads'));
                            $ext = $file->getExtension();
                            $data = base64_encode($file->fread($file->getSize()));
                            $src = 'data:image/' . $ext . ';base64,' . $data;

                            $public = $this->Url->build('/') . 'img/uploads/' . $basename;
                            ?>
                            <div class="col-12 col-md-6">
                                <div class="card mb-2">
                                    <div class="card-body py-2">
                                        <div class="d-flex align-items-start">
                                            <img class="img-thumbnail mr-3" src="<?= h($src) ?>" alt="" style="width:96px; height:auto;">
                                            <div class="flex-grow-1">
                                                <div class="font-weight-bold mb-2"><?= h($file->getFilename()) ?></div>
                                                <button
                                                    type="button"
                                                    class="btn btn-primary btn-sm choose-from-gallery-img"
                                                    data-filename="<?= h($file->getFilename()) ?>"
                                                    data-path="<?= h($public) ?>"
                                                    data-dismiss="modal"
                                                >
                                                    <i class="fas fa-check mr-1"></i>
                                                    <?= __('GALLERY__CHOOSE') ?>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <?php
                        }
                        ?>
                    </div>

                </div>

            </div>
        </div>
    </div>

    <script>
        (function () {
            const root = document.getElementById(<?= json_encode($rootId) ?>);
            if (!root) return;

            const fileInput = document.getElementById(<?= json_encode($fileId) ?>);

            const newNameEl = document.getElementById(<?= json_encode($newNameId) ?>);
            const newPreviewImg = document.getElementById(<?= json_encode($newPreviewId) ?>);
            const newPlaceholder = document.getElementById(<?= json_encode($newPlaceholderId) ?>);

            const deleteNewBtn = document.getElementById(<?= json_encode($deleteNewId) ?>);
            const deleteCurrentBtn = document.getElementById(<?= json_encode($deleteCurrentId) ?>);

            const currentPreviewImg = document.getElementById(<?= json_encode($currentPreviewId) ?>);
            const currentPlaceholder = document.getElementById(<?= json_encode($currentPlaceholderId) ?>);
            const currentNameLink = document.getElementById(<?= json_encode($currentNameId) ?>);

            const modalId = <?= json_encode($modalId) ?>;

            const nameEdit = <?= json_encode($nameEdit) ?>;
            const nameUploaded = <?= json_encode($nameUploaded) ?>;
            const nameUrl = <?= json_encode($nameUrl) ?>;
            const nameDelete = <?= json_encode($nameDelete) ?>;

            const emitState = () => {
                document.dispatchEvent(new CustomEvent('uploadimg:state', { detail: { rootId: <?= json_encode($rootId) ?> } }));
            };

            const showDeleteNew = (show) => {
                if (!deleteNewBtn) return;
                if (show) deleteNewBtn.classList.remove('d-none');
                else deleteNewBtn.classList.add('d-none');
            };

            const ensureEdit = () => {
                let hidden = root.querySelector('input[name="' + nameEdit + '"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = nameEdit;
                    hidden.value = '1';
                    root.appendChild(hidden);
                } else {
                    hidden.value = '1';
                }
            };

            const clearEdit = () => {
                const hidden = root.querySelector('input[name="' + nameEdit + '"]');
                if (hidden) hidden.remove();
            };

            const setHidden = (n, v) => {
                const el = root.querySelector('input[name="' + n + '"]');
                if (el) el.value = v || '';
            };

            const setDeleteCurrent = (v) => {
                setHidden(nameDelete, v ? '1' : '0');
            };

            const clearGallerySelection = () => {
                setHidden(nameUploaded, '');
                setHidden(nameUrl, '');
            };

            const setNewPreview = (src) => {
                if (newPlaceholder) newPlaceholder.classList.add('d-none');
                if (newPreviewImg) {
                    newPreviewImg.classList.remove('d-none');
                    newPreviewImg.src = src || '';
                }
            };

            const clearNewPreview = () => {
                if (newPreviewImg) {
                    newPreviewImg.classList.add('d-none');
                    newPreviewImg.removeAttribute('src');
                }
                if (newPlaceholder) newPlaceholder.classList.remove('d-none');
            };

            const setNewName = (name) => {
                if (newNameEl) newNameEl.textContent = name || <?= json_encode(__('FORM__NO_FILE_SELECTED')) ?>;
            };

            const hideCurrentUi = () => {
                if (currentPreviewImg) {
                    currentPreviewImg.classList.add('d-none');
                    currentPreviewImg.removeAttribute('src');
                }
                if (currentPlaceholder) currentPlaceholder.classList.remove('d-none');
                if (currentNameLink) {
                    currentNameLink.textContent = <?= json_encode(__('FORM__NO_IMAGE_DEFINED')) ?>;
                    currentNameLink.removeAttribute('href');
                    currentNameLink.removeAttribute('title');
                }
            };

            if (fileInput) {
                fileInput.addEventListener('change', function () {
                    const f = fileInput.files && fileInput.files[0] ? fileInput.files[0] : null;
                    if (!f) return;

                    setNewName(f.name);
                    setNewPreview(URL.createObjectURL(f));
                    showDeleteNew(true);

                    setDeleteCurrent(false);
                    clearGallerySelection();
                    ensureEdit();

                    emitState();
                });
            }

            if (deleteNewBtn) {
                deleteNewBtn.addEventListener('click', function () {
                    if (fileInput) fileInput.value = '';
                    setNewName('');
                    clearNewPreview();
                    showDeleteNew(false);

                    clearGallerySelection();
                    clearEdit();

                    emitState();
                });
            }

            if (deleteCurrentBtn) {
                deleteCurrentBtn.addEventListener('click', function () {
                    setDeleteCurrent(true);
                    ensureEdit();
                    hideCurrentUi();

                    emitState();
                });
            }

            const chooseButtons = document.querySelectorAll('#' + modalId + ' .choose-from-gallery-img');
            for (const btn of chooseButtons) {
                btn.addEventListener('click', function () {
                    const path = btn.getAttribute('data-path') || '';
                    const filename = btn.getAttribute('data-filename') || '';

                    if (path) setNewPreview(path);
                    setNewName(filename);
                    showDeleteNew(true);

                    setDeleteCurrent(false);
                    setHidden(nameUploaded, filename);
                    setHidden(nameUrl, path);

                    if (fileInput) fileInput.value = '';

                    ensureEdit();

                    emitState();
                });
            }

            showDeleteNew(false);
            emitState();
        })();
    </script>
