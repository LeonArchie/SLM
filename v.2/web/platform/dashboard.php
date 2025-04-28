<?php
    // Идентификатор привилегий
    $privileges_page = "3fda4364-74ff-4ea7-a4d4-5cca300758a2";

    // Путь к файлу с функциями
    $file_path = 'include/function.php';
    
    // Проверка существования файла function.php
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем пользователя на страницу ошибки 503
        header("Location: /err/50x.html");
        exit(); // Прекращаем выполнение скрипта
    }

    // Подключение файла с функциями
    require_once $file_path;

    // Инициализация сессии, если она еще не начата
    startSessionIfNotStarted(); 
    
    $file_path = 'include/binding/check_auth.php';
    if (!file_exists($file_path)) {
        // Если не существует, переходим 503.php
        header("Location: err/50x.html");
        exit();
    }
    require_once $file_path;
    
    // Проверка привилегий для текущей страницы
    $file_path = 'include/binding/frod.php';

    // Проверка существования файла function.php
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем пользователя на страницу ошибки 503
        header("Location: /err/50x.html");
        exit(); // Прекращаем выполнение скрипта
    }

    // Подключение файла с функциями
    require_once $file_path;

    include "include/binding/inital_error.php";

    // Логирование успешной инициализации страницы
    logger("DEBUG", "dashboard.php успешно инициализирован.");
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include 'include/visible/all_head.html'; ?>
        <!-- Подключение стилей -->
        <link rel="stylesheet" href="include/css/navbar.css"/>
        <link rel="stylesheet" href="include/css/error.css"/>
        <link rel="stylesheet" href="css/dashboard.css"/>
        <title>ЕОС - Дашборд</title>
    </head>
    <body>
        <?php include 'include/visible/eos_header.html'; ?>
        <?php include 'include/visible/navbar.php'; ?>
        <main>
            <div class="message">
                <h1>Добро пожаловать, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гость'; ?>!</h1>
                <p>Единое окно сотрудника в разработке</p>
                <p> Скоро мы добавим информацию для Вас</p>
            </div>
            <?php include 'include/visible/loading.html'; ?>
        </main>
        <?php include 'include/visible/error.php'; ?>
        <?php include 'include/visible/footer.php'; ?>
        <!-- Подключаем скрипты -->
        <script src="/platform/include/js/check_jwt.js"></script>
        <script src="include/js/error.js"></script>
    </body>   
</html>