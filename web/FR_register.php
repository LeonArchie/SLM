<?php
require_once 'include/function.php';

// Логирование начала выполнения скрипта
logger("INFO", "Начало выполнения скрипта FR_register.php.");

// Запуск сессии
startSessionIfNotStarted();
logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());

// Проверка авторизации
checkAuth();
logger("INFO", "Пользователь авторизован. Username: " . $_SESSION['username']);

// Подключение к базе данных
require_once 'db_connect.php';
logger("INFO", "Подключение к базе данных успешно.");

// Запрос для получения списка ролей
$sql = "SELECT roleid, names_rol FROM public.name_rol";
$stmt = $pdo->query($sql);

// Проверка наличия данных
if ($stmt->rowCount() > 0) {
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
    logger("INFO", "Список ролей успешно получен из базы данных.");
} else {
    $roles = []; // Если ролей нет
    logger("INFO", "Список ролей пуст.");
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include 'include/all_head.html'; ?>    
    <link rel="stylesheet" href="/css/register.css">
</head>
<body>
    <?php include 'include/header.html'; ?>
    <?php include 'include/navbar.html'; ?>
    <!-- Основной контент -->
    <main class="register">
        <h2>Ручная регистрация пользователя</h2>
        <form id="registrationForm" action="/BC_register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="input-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
                <span class="validation-indicator" id="email-indicator"></span>
                <div class="error-message" id="email-error"></div>
            </div>
            <div class="input-group">
                <label for="usernames">Имя пользователя:</label>
                <input type="text" id="usernames" name="usernames" required>
                <span class="validation-indicator" id="usernames-indicator"></span>
                <div class="error-message" id="usernames-error"></div>
            </div>            
            <div class="input-group">
                <label for="username">Логин:</label>
                <input type="text" id="username" name="username" required>
                <span class="validation-indicator" id="username-indicator"></span>
                <div class="error-message" id="username-error"></div>
            </div>
            <div class="input-group">
                <label for="password">Транспортный пароль:</label>
                <div class="password-container">
                    <input type="text" id="password" name="password" required>
                    <button type="button" id="generate-password">Сгенерировать</button>
                </div>
                <span class="validation-indicator" id="password-indicator"></span>
                <div class="error-message" id="password-error"></div>
            </div>
            <div class="input-group">
                <label for="role">Выберите роль:</label>
                <select id="role" name="role" required>
                    <option value="">-- Выберите роль --</option>
                    <?php foreach ($roles as $role): ?>
                        <option value="<?php echo $role['roleid']; ?>">
                            <?php echo htmlspecialchars($role['names_rol']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="validation-indicator" id="role-indicator"></span>
                <div class="error-message" id="role-error"></div>
            </div>
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <button type="submit">Зарегистрировать</button>
            <!-- Блок для отображения всех ошибок -->
            <div id="error-summary"></div>
        </form>
        <!-- Уведомление о результате регистрации -->
        <div id="notification" style="display: none;"></div>
    </main>
    <?php include 'include/footer.html'; ?>    
    <!-- Подключаем внешние скрипты -->
    <script src="/js/generator_pass.js"></script>
    <script src="/js/register.js"></script>
</body>
</html>