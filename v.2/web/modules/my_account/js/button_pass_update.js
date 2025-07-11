// SPDX-License-Identifier: AGPL-3.0-only WITH LICENSE-ADDITIONAL
// Copyright (C) 2025 Петунин Лев Михайлович

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