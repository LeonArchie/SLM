<!-- Форма смены пароля -->
<div class="modal-overlay" id="modalOverlay">
    <div class="passwd-form">
        <form id="passwdForm">
            <h3><i class="fas fa-key"></i> Смена пароля</h3>s            
            <div class="form-field">
                <label for="current_password"><i class="fas fa-lock"></i> Текущий пароль:</label>
                <input type="password" id="current_password" name="current_password" required>
            </div>
            
            <div class="form-field">
                <label for="new_password"><i class="fas fa-key"></i> Новый пароль:</label>
                <input type="password" id="new_password" name="new_password" required>
            </div>
            
            <div class="form-field">
                <label for="confirm_password"><i class="fas fa-redo"></i> Повторите пароль:</label>
                <input type="password" id="confirm_password" name="confirm_password" required>
            </div>
            
            <div class="button-group">
                <button type="button" class="cancel" onclick="closeForm()">
                    <i class="fas fa-times"></i> Отменить
                </button>
                <button type="submit" class="save">
                    <i class="fas fa-check"></i> Сменить
                </button>
            </div>
        </form>
    </div>
</div>