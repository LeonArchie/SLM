<?php
require_once 'include/function.php';

// Логирование начала выполнения скрипта
logger("INFO", "Начало выполнения скрипта login.php.");

// Запуск сессии
startSessionIfNotStarted();
logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());

// Генерация CSRF-токена
csrf_token();
logger("INFO", "CSRF-токен сгенерирован.");

// Проверка, есть ли сообщение об ошибке
$error_message = "";
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
    logger("ERROR", "Получено сообщение об ошибке: " . $error_message);
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <?php include 'include/all_head.html'; ?>
    <!-- Подключение стилей -->
    <link rel="stylesheet" href="/css/login.css"/>
</head>
<body>
    <?php include 'include/header.html'; ?>
    <!-- Основной контент -->
    <main class="authorization">
        <h2>Авторизация</h2>
        <form action="authorization.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <!-- Поле для логина -->
            <div class="input-group">
                <label for="login">Логин:</label>
                <input type="text" id="login" name="login" placeholder="Введите логин" required>
            </div>
            <!-- Поле для пароля -->
            <div class="input-group">
                <label for="password">Пароль:</label>
                <input type="password" id="password" name="password" placeholder="Введите пароль" required>
            </div>
            <?php if (!empty($error_message)): ?>
                <div class="error-message">
                    <?php echo $error_message; ?>
                </div>
            <?php endif; ?>
            <!-- Кнопка отправки -->
            <input type="submit" value="Войти">
        </form>
    </main>
    <?php include 'include/footer.html'; ?>
    <!-- Подключаем внешний скрипт -->
    <script src="js/input_err.js"></script>
</body>
</html>