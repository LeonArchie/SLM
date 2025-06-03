document.addEventListener('DOMContentLoaded', function() {
    // Get the button and modal elements
    const changePasswordButton = document.getElementById('changePasswordButton');
    const modalOverlay = document.getElementById('modalOverlay');
    
    // Show the form when button is clicked
    if (changePasswordButton) {
        changePasswordButton.addEventListener('click', function() {
            modalOverlay.style.display = 'flex';
        });
    }
});