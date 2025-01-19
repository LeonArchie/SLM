<?php
session_start();

// Проверка, авторизован ли пользователь
//if (!isset($_SESSION['user']) || !isset($_COOKIE['session_id']) || $_COOKIE['session_id'] !== session_id()) {
//    header("Location: logout.php");
//    exit();
//}

// Генерация CSRF-токена, если он еще не создан
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Проверка, есть ли сообщение об ошибке
$error_message = "";
if (isset($_GET['error'])) {
    $error_message = htmlspecialchars($_GET['error']);
}

require_once 'db_connect.php'; // Файл с подключением к PostgreSQL

// Запрос для получения списка ролей
$sql = "SELECT roleid, names_rol FROM public.name_rol";
$stmt = $pdo->query($sql);

// Проверка наличия данных
if ($stmt->rowCount() > 0) {
    $roles = $stmt->fetchAll(PDO::FETCH_ASSOC);
} else {
    $roles = []; // Если ролей нет
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <!--Заголовок-->
    <title>SLM</title>
    <!--Кодировка-->
    <meta charset="utf-8">
    <!-- Адаптивность -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!--Ключевые слова-->
    <meta name="description" content="Управление жизненным циклом серверов и приложений">
    <!--Минус роботы-->
    <meta name="robots" content="noindex, nofollow">
    <!-- Подключение стилей -->
    <link rel="stylesheet" href="/css/all.css">
    <link rel="stylesheet" href="/css/navbar.css">
    <link rel="stylesheet" href="/css/register.css"> <!-- Подключение стилей для регистрации -->
    <!-- Фавикон -->
    <link rel="icon" sizes="16x16 32x32 48x48" type="image/png" href="/img/icon.png">
</head>
<body>
    <?php include 'header.html'; ?>
    <?php include 'navbar.html'; ?>
    <!-- Основной контент -->
    <main class="register">
        <h2>Ручная регистрация пользователя</h2>
        <form id="registrationForm" action="/BC_register.php" method="POST">
            <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
            <div class="input-group">
                <label for="email">E-mail:</label>
                <input type="email" id="email" name="email" required>
                <span class="validation-indicator" id="email-indicator"></span>
            </div>
            <div class="input-group">
                <label for="username">Логин:</label>
                <input type="text" id="username" name="username" required>
                <span class="validation-indicator" id="username-indicator"></span>
            </div>
            <div class="input-group">
                <label for="password">Транспортный пароль:</label>
                <div class="password-container">
                    <input type="text" id="password" name="password" required>
                    <button type="button" id="generate-password">Сгенерировать</button>
                </div>
                <span class="validation-indicator" id="password-indicator"></span>
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
            </div>
            <!-- Блок для отображения всех ошибок -->
            <div id="error-summary" class="error-message"></div>
            <button type="submit">Зарегистрировать</button>
        </form>
        <!-- Уведомление о результате регистрации -->
        <div id="notification"></div>
    </main>
    <?php include 'footer.html'; ?>
    <!-- Подключаем внешние скрипты -->
    <script src="/js/generator_pass.js"></script>
    <script src="/js/register.js"></script>
</body>
</html>