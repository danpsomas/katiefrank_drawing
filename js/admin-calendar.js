const adminModal = document.querySelector('[data-admin-edit-modal]');
const adminModalImage = document.querySelector('[data-admin-modal-image]');
const adminModalMeta = document.querySelector('[data-admin-modal-meta]');
const adminModalStatus = document.querySelector('[data-admin-modal-status]');
const adminModalClose = document.querySelector('[data-admin-modal-close]');
const adminForm = document.querySelector('[data-admin-edit-form]');
const adminUploadStatus = document.querySelector('[data-admin-upload-status]');
const adminDidInput = adminForm ? adminForm.querySelector('input[name="DID"]') : null;
const adminDateInput = adminForm ? adminForm.querySelector('input[name="display_date"]') : null;
const adminHiddenInput = adminForm ? adminForm.querySelector('input[name="is_hidden"]') : null;
let activeThumbnail = null;
let saveInProgress = false;
let savePending = false;

if (window.Dropzone) {
    window.Dropzone.autoDiscover = false;
}

function setModalStatus(message, isError = false) {
    if (!adminModalStatus) {
        return;
    }

    adminModalStatus.textContent = message;
    adminModalStatus.classList.toggle('admin-edit-modal__status--error', isError);
}

function setUploadStatus(message, isError = false) {
    if (!adminUploadStatus) {
        return;
    }

    adminUploadStatus.textContent = message;
    adminUploadStatus.classList.toggle('admin-upload-status--error', isError);
}

function setThumbnailHidden(thumbnail, isHidden, hiddenDate = '') {
    thumbnail.dataset.hidden = isHidden ? '1' : '0';
    thumbnail.dataset.hiddenDate = hiddenDate || '';
    thumbnail.classList.toggle('admin-thumbnail--hidden', isHidden);
}

function updateCellState(cell) {
    if (!cell) {
        return;
    }

    const thumbnails = cell.querySelectorAll('[data-admin-thumbnail]');
    const emptyMessage = cell.querySelector('.date-card__empty');
    const grid = cell.querySelector('.thumbnail-grid');
    const count = thumbnails.length;

    cell.classList.toggle('date-card--has-thumbnails', count > 0);

    for (let i = 0; i <= 20; i += 1) {
        cell.classList.remove(`date-card--thumbnail-count-${i}`);
        if (grid) {
            grid.classList.remove(`thumbnail-grid--count-${i}`);
        }
    }

    if (count > 0) {
        cell.classList.add(`date-card--thumbnail-count-${Math.min(count, 20)}`);
        if (grid) {
            grid.classList.add(`thumbnail-grid--count-${Math.min(count, 20)}`);
        }
    }

    if (emptyMessage) {
        emptyMessage.hidden = count > 0;
    }
}

function moveThumbnailToDate(thumbnail, displayDate) {
    const currentCell = thumbnail.closest('[data-date-cell]');
    const targetCell = document.querySelector(`[data-date-cell="${displayDate}"]`);

    if (!targetCell) {
        thumbnail.remove();
        updateCellState(currentCell);
        return false;
    }

    const targetGrid = targetCell.querySelector('.thumbnail-grid');
    if (!targetGrid) {
        return true;
    }

    targetGrid.appendChild(thumbnail);
    updateCellState(currentCell);
    updateCellState(targetCell);
    return true;
}

function getAdminRelativePath(path) {
    if (!path) {
        return '';
    }

    const cleanPath = String(path).replace(/^\.\//, '');

    if (/^(https?:|data:|\/|\.\.\/)/.test(cleanPath)) {
        return cleanPath;
    }

    return `../${cleanPath}`;
}

function appendUploadedThumbnail(cell, drawing) {
    const targetGrid = cell.querySelector('.thumbnail-grid');

    if (!targetGrid) {
        return;
    }

    const displayDate = drawing.display_date || cell.dataset.dateCell || '';
    const originalName = drawing.origName || '';
    const imageAlt = originalName ? `${originalName} for ${displayDate}` : `Drawing for ${displayDate}`;
    const thumbnail = document.createElement('a');
    const image = document.createElement('img');

    thumbnail.className = drawing.isHidden ? 'admin-thumbnail admin-thumbnail--hidden' : 'admin-thumbnail';
    thumbnail.href = getAdminRelativePath(drawing.sizedPath);
    thumbnail.setAttribute('data-admin-thumbnail', '');
    thumbnail.dataset.did = drawing.DID || '';
    thumbnail.dataset.displayDate = displayDate;
    thumbnail.dataset.filename = drawing.filename || '';
    thumbnail.dataset.hidden = drawing.isHidden ? '1' : '0';
    thumbnail.dataset.hiddenDate = drawing.hidden || '';
    thumbnail.dataset.origName = originalName;
    thumbnail.dataset.thumbSrc = getAdminRelativePath(drawing.thumbPath);
    thumbnail.dataset.modalAlt = imageAlt;

    image.src = getAdminRelativePath(drawing.thumbPath);
    image.alt = imageAlt;
    image.loading = 'lazy';

    thumbnail.appendChild(image);
    targetGrid.appendChild(thumbnail);
    updateCellState(cell);
}

function getUploadErrorMessage(message) {
    if (typeof message === 'string') {
        return message;
    }

    if (message && typeof message.message === 'string') {
        return message.message;
    }

    return 'Could not upload image.';
}

function parseUploadResponse(response) {
    if (typeof response !== 'string') {
        return response;
    }

    try {
        return JSON.parse(response);
    } catch (error) {
        return null;
    }
}

function eventHasUploadFiles(event) {
    const types = event.dataTransfer ? event.dataTransfer.types : null;

    if (!types) {
        return false;
    }

    return Array.prototype.indexOf.call(types, 'Files') !== -1;
}

function preventBrowserFileDrop(event) {
    if (!eventHasUploadFiles(event)) {
        return;
    }

    event.preventDefault();
}

function uploadFileToCell(cell, file) {
    const formData = new FormData();
    const request = new XMLHttpRequest();

    formData.append('file', file);
    formData.append('display_date', cell.dataset.dateCell || '');

    cell.classList.add('date-card--uploading');
    setUploadStatus(`Uploading ${file.name}...`);

    request.open('POST', 'upload_handler.php');
    request.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

    request.onload = () => {
        const response = parseUploadResponse(request.responseText);

        if (request.status < 200 || request.status >= 300 || !response || !response.success) {
            setUploadStatus(getUploadErrorMessage(response), true);
            return;
        }

        appendUploadedThumbnail(cell, response);
        setUploadStatus(`${file.name} uploaded.`);
    };

    request.onerror = () => {
        setUploadStatus('Could not upload image.', true);
    };

    request.onloadend = () => {
        cell.classList.remove('date-card--uploading');
    };

    request.send(formData);
}

function initializeNativeCellUpload(cell) {
    cell.addEventListener('dragenter', (event) => {
        if (!eventHasUploadFiles(event)) {
            return;
        }

        event.preventDefault();
        cell.classList.add('dz-drag-hover');
    });

    cell.addEventListener('dragover', (event) => {
        if (!eventHasUploadFiles(event)) {
            return;
        }

        event.preventDefault();
        event.dataTransfer.dropEffect = 'copy';
        cell.classList.add('dz-drag-hover');
    });

    cell.addEventListener('dragleave', (event) => {
        if (!cell.contains(event.relatedTarget)) {
            cell.classList.remove('dz-drag-hover');
        }
    });

    cell.addEventListener('drop', (event) => {
        if (!eventHasUploadFiles(event)) {
            return;
        }

        event.preventDefault();
        event.stopPropagation();
        cell.classList.remove('dz-drag-hover');

        Array.prototype.forEach.call(event.dataTransfer.files || [], (file) => {
            uploadFileToCell(cell, file);
        });
    });
}

function initializeCalendarDropzones() {
    const previewContainer = document.createElement('div');
    previewContainer.hidden = true;
    document.body.appendChild(previewContainer);

    document.querySelectorAll('[data-date-cell]').forEach((cell) => {
        let dropzoneAttached = false;

        if (window.Dropzone) {
            try {
                new window.Dropzone(cell, {
                    url: 'upload_handler.php',
                    paramName: 'file',
                    acceptedFiles: 'image/*',
                    clickable: false,
                    createImageThumbnails: false,
                    previewsContainer,
                    previewTemplate: '<div></div>',
                    sending(file, xhr, formData) {
                        formData.append('display_date', cell.dataset.dateCell || '');
                        cell.classList.add('date-card--uploading');
                        setUploadStatus(`Uploading ${file.name}...`);
                    },
                    success(file, response) {
                        const uploadResponse = parseUploadResponse(response);

                        if (!uploadResponse || !uploadResponse.success) {
                            setUploadStatus(getUploadErrorMessage(uploadResponse), true);
                            return;
                        }

                        appendUploadedThumbnail(cell, uploadResponse);
                        setUploadStatus(`${file.name} uploaded.`);
                    },
                    error(file, message) {
                        setUploadStatus(getUploadErrorMessage(message), true);
                    },
                    complete(file) {
                        cell.classList.remove('date-card--uploading');
                        this.removeFile(file);
                    },
                });
                dropzoneAttached = true;
            } catch (error) {
                dropzoneAttached = false;
            }
        }

        if (!dropzoneAttached) {
            initializeNativeCellUpload(cell);
        }

        cell.classList.add('date-card--dropzone-ready');
    });
}

function fillModalFromThumbnail(thumbnail) {
    activeThumbnail = thumbnail;

    if (adminModalImage) {
        adminModalImage.src = thumbnail.href;
        adminModalImage.alt = thumbnail.dataset.modalAlt || '';
    }

    if (adminModalMeta) {
        const pieces = [`DID ${thumbnail.dataset.did}`];
        if (thumbnail.dataset.origName) {
            pieces.push(thumbnail.dataset.origName);
        }
        adminModalMeta.textContent = pieces.join(' · ');
    }

    if (adminDidInput) {
        adminDidInput.value = thumbnail.dataset.did || '';
    }

    if (adminDateInput) {
        adminDateInput.value = thumbnail.dataset.displayDate || '';
    }

    if (adminHiddenInput) {
        adminHiddenInput.checked = thumbnail.dataset.hidden === '1';
    }

    setModalStatus('');
}

function openAdminModal(thumbnail) {
    if (!adminModal || !adminForm) {
        return;
    }

    fillModalFromThumbnail(thumbnail);
    adminModal.hidden = false;

    if (adminModalClose) {
        adminModalClose.focus();
    }
}

function closeAdminModal() {
    if (!adminModal) {
        return;
    }

    adminModal.hidden = true;

    if (adminModalImage) {
        adminModalImage.src = '';
        adminModalImage.alt = '';
    }

    if (activeThumbnail && document.contains(activeThumbnail)) {
        activeThumbnail.focus();
    }

    activeThumbnail = null;
}

function saveActiveDrawing() {
    if (!activeThumbnail || !adminForm || !adminDidInput || !adminDateInput || !adminHiddenInput) {
        return;
    }

    if (saveInProgress) {
        savePending = true;
        setModalStatus('Saving...');
        return;
    }

    saveInProgress = true;
    setModalStatus('Saving...');

    $.ajax({
        url: window.location.href,
        method: 'POST',
        dataType: 'json',
        data: {
            DID: adminDidInput.value,
            display_date: adminDateInput.value,
            is_hidden: adminHiddenInput.checked ? '1' : '0',
        },
    }).done((response) => {
        if (!response || String(response.DID) !== String(adminDidInput.value)) {
            setModalStatus('Save response did not match this drawing.', true);
            return;
        }

        if (!response.success) {
            setModalStatus(response.message || 'Could not save.', true);
            return;
        }

        const isHidden = Boolean(response.is_hidden);
        const displayDate = response.display_date || adminDateInput.value;

        adminDateInput.value = displayDate;
        adminHiddenInput.checked = isHidden;
        activeThumbnail.dataset.displayDate = displayDate;
        activeThumbnail.dataset.filename = response.filename || activeThumbnail.dataset.filename || '';
        setThumbnailHidden(activeThumbnail, isHidden, response.hidden || '');
        const remainsInCurrentMonth = moveThumbnailToDate(activeThumbnail, displayDate);
        setModalStatus(response.message || 'Saved');

        if (!remainsInCurrentMonth) {
            closeAdminModal();
        }
    }).fail(() => {
        setModalStatus('Could not save.', true);
    }).always(() => {
        saveInProgress = false;

        if (savePending) {
            savePending = false;
            saveActiveDrawing();
        }
    });
}

document.addEventListener('click', (event) => {
    const thumbnail = event.target.closest('[data-admin-thumbnail]');

    if (thumbnail) {
        event.preventDefault();
        openAdminModal(thumbnail);
        return;
    }

    if (event.target === adminModal || event.target.closest('[data-admin-modal-close]')) {
        closeAdminModal();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && adminModal && !adminModal.hidden) {
        closeAdminModal();
    }
});

if (adminForm) {
    adminForm.addEventListener('submit', (event) => {
        event.preventDefault();
    });

    adminForm.addEventListener('change', (event) => {
        if (event.target.matches('input[name="display_date"], input[name="is_hidden"]')) {
            saveActiveDrawing();
        }
    });
}

if (adminModalClose) {
    adminModalClose.addEventListener('click', closeAdminModal);
}

document.addEventListener('dragover', preventBrowserFileDrop);
document.addEventListener('drop', preventBrowserFileDrop);
initializeCalendarDropzones();
