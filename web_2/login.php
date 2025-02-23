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
    <link rel="stylesheet" href="css/login.css"/>
    <link rel="stylesheet" href="css/error.css"/>
</head>
<body>
    <?php include 'include/eos_header.html'; ?>
    <!-- Основной контент -->
    <main class="authorization">
        <h2>Авторизация</h2>
        <form action="back/authorization.php" method="POST">
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
            <!-- Кнопка отправки -->
            <input type="submit" value="Войти">
        </form>
    </main>
    <?php include 'include/error.html'; ?>
    <?php include 'include/footer.php'; ?>
    <!-- Подключаем внешний скрипт -->
    <script src="js/error.js"></script>
</body>
</html>