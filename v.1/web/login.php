<?php
    // Путь к файлу с функциями
    $file_path = 'include/function.php';

    // Проверка существования файла
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем на страницу ошибки 503
        header("Location: /err/50x.html");
        exit(); // Завершаем выполнение скрипта
    }
    
    // Подключаем файл с функциями
    require_once $file_path;

    // Запуск сессии, если она еще не запущена
    startSessionIfNotStarted();

    // Генерация CSRF-токена для защиты от атак типа CSRF
    csrf_token();

    // Путь к конфигурационному файлу
    $config_path = CONFIG_PATH;

    // Чтение и декодирование конфигурационного файла в формате JSON
    $config = json_decode(file_get_contents($config_path), true);

    // Проверка состояния LDAP из конфигурации
    $ldap_active = $config['LDAP']['active'] ?? false; // Если параметр не задан, по умолчанию false
    $auth_type_disabled = !$ldap_active; // Определяем, отключена ли LDAP-аутентификация
    $default_auth_type = $ldap_active ? 'ldap' : 'internal'; // Устанавливаем тип аутентификации по умолчанию

    // Инициализация переменной для хранения сообщения об ошибке
    $error_message = "";

    // Проверяем, передана ли ошибка через GET-параметр
    if (isset($_GET['error'])) {
        $raw_error = $_GET['error']; // Сохраняем сырое значение ошибки
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8'); // Экранируем специальные символы для безопасности
    }

    // Логируем успешную инициализацию скрипта
    logger("DEBUG", "login.php успешно инициализирован.");
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <!--Заголовок-->
        <title>ЕОС</title>	
        <!--Кодировка-->
        <meta charset="utf-8">							
        <!--Ключевые слова-->
        <meta
            name="description"
            content="Единое окно сотрудникв"
        />
        <!--Минус роботы-->
        <meta 
            name="robots"
            content="noindex, nofollow" 
        />
        <!-- Фавикон -->
        <link
            rel="icon"
            sizes="16x16 32x32 48x48"
            type="image/png"
            href="/img/eos_icon.png"
        />
        <link rel="stylesheet" href="css/login.css"/>
        <link rel="stylesheet" href="css/error.css"/>
    </head>
    <body>
        <?php include 'include/eos_header.html'; ?>
        <!-- Основной контент -->
        <main class="authorization">
            <h2>Авторизация</h2>
            <form id="authForm" action="back/authorization.php" method="POST">
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
                <!-- Поле для выбора типа авторизации -->
                <div class= "input-group select-group">
                    <label for="auth_type">Сервер:</label>
                    <select id="auth_type" name="auth_type" <?php echo $auth_type_disabled ? 'disabled' : ''; ?> required>
                        <option value="internal" <?php echo $default_auth_type === 'internal' ? 'selected' : ''; ?>>Внутренняя</option>
                        <option value="ldap" <?php echo $default_auth_type === 'ldap' ? 'selected' : ''; ?>>LDAP</option>
                    </select>
                </div>
                <!-- Кнопка отправки -->
                <input type="submit" value="Войти">
            </form>
            <?php include 'include/loading.html'; ?>
        </main>
         <?php include 'include/error.php'; ?>
        <?php include 'include/footer.php'; ?>
        <!-- Подключаем скрипты -->
        <script src="js/error.js"></script>
        <script src="js/login.js"></script>
    </body>
</html>