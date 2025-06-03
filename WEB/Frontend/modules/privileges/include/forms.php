    <!-- Форма для просмотра полномочий -->
    <div id="viewPrivilegesForm" class="form-modal" style="display: none;">
        <div class="form-modal-content">
            <div class="table-header">
                <h2>Просмотр полномочий пользователя</h2>
                <div class="search-container">
                    <input type="text" id="privilegesUserSearch" placeholder="Поиск по названию или ID..." class="search-input">
                </div>
            </div>
            <div class="user-id-container">
                <label for="userIDView">UserID:</label>
                <input type="text" id="userIDView" name="userIDView" readonly>
            </div>
            <div class="table-container">
                <table class="privileges-table">
                    <thead>
                        <tr>
                            <th>ID полномочий</th>
                            <th>Название полномочий</th>
                        </tr>
                    </thead>
                    <tbody id="userPrivilegesTableBody">
                        <!-- Данные будут загружены через JS -->
                    </tbody>
                </table>
            </div>
            <div class="get-all-button-group">
                <button type="button" class="close-button" id="closeViewPrivilegesForm">Закрыть</button>
            </div>
        </div>
    </div>
    <!-- Форма для просмотра всех полномочий -->
    <div id="viewAllPrivilegesForm" class="form-modal" style="display: none;">
        <div class="form-modal-content">
            <div class="table-header">
                <h2>Все полномочия</h2>
                <div class="search-container">
                    <input type="text" id="privilegesSearch" placeholder="Поиск по названию или ID..." class="search-input">
                </div>
            </div>
            <div class="table-container">
                <table class="privileges-table">
                    <thead>
                        <tr>
                            <th>ID полномочий</th>
                            <th>Название полномочий</th>
                        </tr>
                    </thead>
                    <tbody id="privilegesTableBody">
                        <!-- Данные будут загружены через JS -->
                    </tbody>
                </table>
            </div>
            <div class="get-all-button-group">
                <button type="button" class="close-button" id="closeViewAllPrivilegesForm">Закрыть</button>
            </div>
        </div>
    </div>
    <?php 
        $privileges_button = 'e8e24302-c6e2-4b0d-9b23-4c7119a94756';
        if (checkPrivilege( $privileges_button)): ?>
            <!-- Форма для назначения полномочий -->
            <div id="assignPrivilegesForm" class="form-modal" style="display: none;">
                <h2>Назначить полномочия</h2>
                <form id="assignPrivilegesFormContent">
                    <div class="input-group">
                        <label for="userIDAssign">UserID:</label>
                        <input type="text" id="userIDAssign" name="userIDAssign" readonly>
                    </div>
                    <div class="input-group select-group">
                        <label for="privilegesToAssign">Полномочия:</label>
                        <select id="privilegesToAssign" name="privilegesToAssign[]" multiple>
                            <?php foreach ($name_privileges as $privilege): ?>
                                <option value="<?= $privilege['id_privileges'] ?>"><?= $privilege['name_privileges'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="button-group">
                        <button type="button" id="submitAssignPrivilegesForm">Назначить полномочия</button>
                        <button type="button" id="cancelAssignPrivilegesForm">Отменить</button>
                    </div>
                </form>
            </div>
            <!-- Форма для снятия полномочий -->
            <div id="revokePrivilegesForm" class="form-modal" style="display: none;">
                <h2>Снять полномочия</h2>
                <form id="revokePrivilegesFormContent">
                    <div class="input-group">
                        <label for="userIDRevoke">UserID:</label>
                        <input type="text" id="userIDRevoke" name="userIDRevoke" readonly>
                    </div>
                    <div class="input-group select-group">
                        <label for="privilegesToRevoke">Привилегии:</label>
                        <select id="privilegesToRevoke" name="privilegesToRevoke[]" multiple>
                            <?php foreach ($name_privileges as $privilege): ?>
                                <option value="<?= $privilege['id_privileges'] ?>"><?= $privilege['name_privileges'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="button-group">
                        <button type="button" id="submitRevokePrivilegesForm">Снять</button>
                        <button type="button" id="cancelRevokePrivilegesForm">Отменить</button>
                    </div>
                </form>
            </div>
    <?php endif; ?>

    <?php 
        $privileges_button = 'eff8f3c0-97c8-43ef-944e-8eb2dcd1d344';
        if (checkPrivilege( $privileges_button)): ?>
            <!-- Форма создания полномочий -->
            <div id="createPrivilegesForm" class="form-modal" style="display: none;">
                <h2>Создать полномочия</h2>
                <form id="createPrivilegesFormContent">
                    <div class="input-group">
                        <label for="privilegeName">Имя:</label>
                        <input type="text" id="privilegeName" name="privilegeName" required>
                    </div>
                    <div class="input-group">
                        <label for="privilegeID">ID:</label>
                        <input type="text" id="privilegeID" name="privilegeID" required>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="button-group">
                        <button type="button" id="submitCreatePrivilegesForm">Создать</button>
                        <button type="button" id="cancelCreatePrivilegesForm">Отменить</button>
                    </div>
                </form>
            </div>
            <!-- Форма для удаления полномочий -->
            <div id="deletePrivilegesForm" class="form-modal" style="display: none;">
                <h2>Удалить полномочия</h2>
                <form id="deletePrivilegesFormContent">
                    <div class="input-group select-group">
                        <label for="privilegesToDelete">Привилегии:</label>
                        <select id="privilegesToDelete" name="privilegesToDelete[]" multiple>
                            <?php foreach ($name_privileges as $privilege): ?>
                                <option value="<?= $privilege['id_privileges'] ?>"><?= $privilege['name_privileges'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <div class="button-group">
                        <button type="button" id="submitDeletePrivilegesForm">Удалить</button>
                        <button type="button" id="cancelDeletePrivilegesForm">Отменить</button>
                    </div>
                </form>
            </div>
    <?php endif; ?>