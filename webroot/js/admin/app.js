function string_to_slug(str) {
    let s = str == null ? '' : String(str);
    s = s.trim().toLowerCase();

    let from = 'àáäâèéëêìíïîòóöôùúüûñç·/_,:;';
    let to = 'aaaaeeeeiiiioooouuuunc------';
    for (let i = 0, l = from.length; i < l; i++) {
        s = s.replace(new RegExp(from.charAt(i), 'g'), to.charAt(i));
    }

    s = s
        .replace(/[^a-z0-9 -]/g, '')
        .replace(/\s+/g, '-')
        .replace(/-+/g, '-');

    return s;
}

document.addEventListener('DOMContentLoaded', function () {
    let generateSlugBtn = document.getElementById('generate_slug');
    if (generateSlugBtn) {
        generateSlugBtn.addEventListener('click', function (event) {
            event.preventDefault();
            let titleInput = document.getElementById('title');
            let slugInput = document.getElementById('slug');
            if (titleInput && slugInput) {
                slugInput.value = string_to_slug(titleInput.value || '');
            }
        });
    }

    if (window.jQuery && window.jQuery.fn && window.jQuery.fn.dataTable) {
        let tables = document.querySelectorAll('table.dataTable');
        tables.forEach(function (table) {
            if (!window.jQuery.fn.dataTable.isDataTable(table)) {
                window.jQuery(table).DataTable({
                    paging: true,
                    lengthChange: false,
                    searching: true,
                    ordering: false,
                    info: false,
                    autoWidth: false
                });
            }
        });
    }

    let fileInputs = document.querySelectorAll('.btn-file input[type="file"]');
    fileInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            let files = input.files;
            let numFiles = files ? files.length : 1;
            let label = input.value.split('\\').pop().split('/').pop();
            let log = numFiles > 1 ? numFiles + ' files selected' : label;
            let group = input.closest('.input-group');
            if (group) {
                let textInput = group.querySelector('input[type="text"]');
                if (textInput) {
                    textInput.value = log;
                } else {
                    if (log) {
                        let browseSpan = document.querySelector('span.browse');
                        if (browseSpan) {
                            browseSpan.textContent = log;
                        }
                    }
                }
            }
        });
    });

    let galleryButtons = document.querySelectorAll('.choose-from-gallery-img');
    galleryButtons.forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();

            let path = btn.getAttribute('data-path');
            let filename = btn.getAttribute('data-filename');
            let basename = btn.getAttribute('data-basename');

            let imagePreview = document.getElementById('image_preview');
            if (imagePreview) {
                let thumbImg = imagePreview.querySelector('.thumbnail img');
                if (thumbImg && path) {
                    thumbImg.setAttribute('src', path);
                }
                let imgName = document.getElementById('img-name');
                if (imgName && filename) {
                    imgName.textContent = filename;
                }

                let hidden = imagePreview.querySelector('input[name="img-uploaded"]');
                if (!hidden) {
                    hidden = document.createElement('input');
                    hidden.type = 'hidden';
                    hidden.name = 'img-uploaded';
                    imagePreview.appendChild(hidden);
                }
                hidden.value = basename || '';
            }

            let galleryModal = document.getElementById('galery');
            if (galleryModal) {
                galleryModal.style.display = 'none';
                galleryModal.classList.remove('show');
            }
        });
    });

    let deleteUploadBtn = document.querySelector('form #delete_upload_file');
    if (deleteUploadBtn) {
        deleteUploadBtn.addEventListener('click', function (e) {
            e.preventDefault();
            let form = deleteUploadBtn.closest('form') || document.querySelector('form');
            if (!form) {
                return;
            }
            let imageInput = form.querySelector('input[name="image"]');
            if (imageInput) {
                imageInput.value = '';
            }
            let imagePreview = document.getElementById('image_preview');
            if (imagePreview) {
                let thumbImg = imagePreview.querySelector('.thumbnail img');
                if (thumbImg) {
                    thumbImg.setAttribute('src', '#');
                }
                let captionTitle = imagePreview.querySelector('.thumbnail .caption h5');
                if (captionTitle) {
                    captionTitle.textContent = '';
                }
            }
        });
    }

    let formImageInputs = document.querySelectorAll('form input[name="image"]');
    formImageInputs.forEach(function (input) {
        input.addEventListener('change', function () {
            let files = input.files;
            if (!files || files.length === 0) {
                return;
            }

            let file = files[0];
            let imagePreview = document.getElementById('image_preview');
            if (!imagePreview) {
                return;
            }

            let thumbnail = imagePreview.querySelector('.thumbnail');
            if (thumbnail) {
                thumbnail.classList.remove('hidden');
            }

            let img = imagePreview.querySelector('img');
            if (img) {
                img.src = window.URL.createObjectURL(file);
            }

            let title = imagePreview.querySelector('h5');
            if (title) {
                title.textContent = file.name;
            }

            let sizeParagraph = imagePreview.querySelector('.caption p:first-child') || imagePreview.querySelector('.caption p');
            if (sizeParagraph) {
                sizeParagraph.textContent = file.size + ' bytes';
            }
        });
    });

    let initBtnChooseUploadedFiles = false;
    let chooseUploadedBtn = document.getElementById('choose_form_uploaded_files');
    if (chooseUploadedBtn) {
        chooseUploadedBtn.addEventListener('click', function (e) {
            e.preventDefault();

            if (!initBtnChooseUploadedFiles) {
                let modal = '';
                let container = document.createElement('div');
                container.innerHTML = modal;
                while (container.firstChild) {
                    document.body.appendChild(container.firstChild);
                }
                initBtnChooseUploadedFiles = true;
            }
        });
    }
});
