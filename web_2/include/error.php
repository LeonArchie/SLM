<!-- Окно ошибки -->
<div id="error-window" class="error-window">
    <div id="progress-bar-container">
        <div id="progress-bar"></div>
    </div>
    <div id="error-message"></div>
</div>
<script>
    window.errorMessage = "<?php echo $error_message; ?>";
</script>
<script src="js/error.js"></script>