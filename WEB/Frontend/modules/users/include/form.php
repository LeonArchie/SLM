<?php
// Проверка привилегий для формы создания пользователя
$privileges_form = '076a0c70-8cca-4124-b009-97fe44f6c68e';
if (!checkPrivilege($privileges_form)) {
    exit();
}
?>

<div id="createUserModal" class="modal">
    <div class="modal-content">
        <div class="modal-header">
            <h2>Создание нового пользователя</h2>
            <span class="close-modal">&times;</span>
        </div>
        <div class="modal-body">
            <form id="createUserForm">
                <div class="form-group">
                    <label for="userlogin">Логин пользователя</label>
                    <input type="text" id="userlogin" name="userlogin" required placeholder="Введите логин">
                </div>
                
                <div class="form-group">
                    <label for="full_name">Полное имя</label>
                    <input type="text" id="full_name" name="full_name" required placeholder="Введите полное имя">
                </div>
                
                <div class="form-group">
                    <label for="password">Пароль</label>
                    <input type="password" id="password" name="password" required placeholder="Введите пароль">
                </div>
                
                <div class="form-group">
                    <label for="email">Email</label>
                    <input type="email" id="email" name="email" required placeholder="Введите email">
                </div>
                
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                
                <div class="form-actions">
                    <button type="button" class="cancel-button">Отмена</button>
                    <button type="submit" class="submit-button">Создать</button>
                </div>
            </form>
        </div>
    </div>
</div>