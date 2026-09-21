const modal = document.querySelector('[data-image-modal]');
const modalImage = document.querySelector('[data-image-modal-image]');
const closeButton = document.querySelector('[data-image-modal-close]');
const previousButton = document.querySelector('[data-image-modal-previous]');
const nextButton = document.querySelector('[data-image-modal-next]');
let activeTrigger = null;
let currentImageIndex = -1;

function getImageTriggers() {
    return Array.from(document.querySelectorAll('[data-modal-image]'));
}

function updateNavigation() {
    if (!previousButton || !nextButton) {
        return;
    }

    const imageTriggers = getImageTriggers();

    previousButton.disabled = currentImageIndex <= 0;
    nextButton.disabled = currentImageIndex >= imageTriggers.length - 1;
}

function closeModal() {
    if (!modal || !modalImage) {
        return;
    }

    modal.hidden = true;
    modalImage.src = '';
    modalImage.alt = '';
    currentImageIndex = -1;
    updateNavigation();

    if (activeTrigger) {
        activeTrigger.focus();
        activeTrigger = null;
    }
}

function showImage(index) {
    if (!modal || !modalImage) {
        return;
    }

    const imageTriggers = getImageTriggers();
    const trigger = imageTriggers[index];

    if (!trigger) {
        return;
    }

    currentImageIndex = index;
    modalImage.src = trigger.href;
    modalImage.alt = trigger.dataset.modalAlt || '';
    updateNavigation();
}

function openModal(trigger) {
    const imageTriggers = getImageTriggers();
    const index = imageTriggers.indexOf(trigger);

    if (index === -1) {
        return;
    }

    activeTrigger = trigger;
    showImage(index);
    modal.hidden = false;

    if (closeButton) {
        closeButton.focus();
    }
}

function showPreviousImage() {
    showImage(currentImageIndex - 1);
}

function showNextImage() {
    showImage(currentImageIndex + 1);
}

window.refreshCalendarModalTriggers = function refreshCalendarModalTriggers() {
    if (!modal || modal.hidden || !activeTrigger) {
        updateNavigation();
        return;
    }

    const imageTriggers = getImageTriggers();
    const nextIndex = imageTriggers.indexOf(activeTrigger);

    if (nextIndex === -1) {
        closeModal();
        return;
    }

    currentImageIndex = nextIndex;
    updateNavigation();
};

document.addEventListener('click', (event) => {
    const trigger = event.target.closest('[data-modal-image]');

    if (trigger) {
        event.preventDefault();
        openModal(trigger);
        return;
    }

    if (event.target.closest('[data-image-modal-previous]')) {
        showPreviousImage();
        return;
    }

    if (event.target.closest('[data-image-modal-next]')) {
        showNextImage();
        return;
    }

    if (event.target === modal || event.target.closest('[data-image-modal-close]')) {
        closeModal();
    }
});

document.addEventListener('keydown', (event) => {
    if (event.key === 'Escape' && modal && !modal.hidden) {
        closeModal();
        return;
    }

    if (!modal || modal.hidden) {
        return;
    }

    if (event.key === 'ArrowLeft') {
        showPreviousImage();
    }

    if (event.key === 'ArrowRight') {
        showNextImage();
    }
});
