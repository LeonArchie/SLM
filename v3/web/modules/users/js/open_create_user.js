document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('createUserModal');
    const openButton = document.getElementById('addButton');
    const closeButton = document.querySelector('.close-modal');
    const cancelButton = document.querySelector('.cancel-button');
    
    // Переменные для обработки двойного клика
    let overlayFirstClick = false;
    let overlayClickTimer = null;
    let cancelFirstClick = false;
    let cancelClickTimer = null;
    
    // Открытие модального окна
    if (openButton) {
        openButton.addEventListener('click', function() {
            modal.style.display = 'block';
            document.body.style.overflow = 'hidden';
        });
    }
    
    // Закрытие модального окна
    function closeModal() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        document.getElementById('createUserForm').reset();
        resetOverlayClickState();
        resetCancelClickState();
    }
    
    // Сброс состояния кликов для оверлея
    function resetOverlayClickState() {
        overlayFirstClick = false;
        if (overlayClickTimer) {
            clearTimeout(overlayClickTimer);
            overlayClickTimer = null;
        }
    }
    
    // Сброс состояния кликов для кнопки Отмена
    function resetCancelClickState() {
        cancelFirstClick = false;
        if (cancelClickTimer) {
            clearTimeout(cancelClickTimer);
            cancelClickTimer = null;
        }
    }
    
    // Обработчик для кнопки закрытия (одинарный клик)
    if (closeButton) {
        closeButton.addEventListener('click', closeModal);
    }
    
    // Обработчик для кнопки Отмена (двойной клик)
    if (cancelButton) {
        cancelButton.addEventListener('click', function(event) {
            if (!cancelFirstClick) {
                cancelFirstClick = true;
                event.target.textContent = 'Кликните еще раз для отмены';
                event.target.style.backgroundColor = '#f1c40f';
                cancelClickTimer = setTimeout(function() {
                    event.target.textContent = 'Отмена';
                    event.target.style.backgroundColor = '';
                    resetCancelClickState();
                }, 1500);
            } else {
                closeModal();
            }
        });
    }
    
    // Обработчик для клика по оверлею (двойной клик)
    modal.addEventListener('click', function(event) {
        if (event.target === modal) {
            if (!overlayFirstClick) {
                overlayFirstClick = true;
                overlayClickTimer = setTimeout(resetOverlayClickState, 300);
            } else {
                closeModal();
            }
        }
    });
    
    // Закрытие по ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && modal.style.display === 'block') {
            closeModal();
        }
    });
});