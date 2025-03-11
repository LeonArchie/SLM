<?php
    $privileges_page = "3ad33b50-360f-4c8a-b691-aca388b48a8b";

    $file_path = 'include/function.php';
    if (!file_exists($file_path)) {
        // Если не существует, переходим 503.php
        header("Location: /err/50x.html");
        exit();
    }
    
    require_once $file_path;
    
    //Инициализация проверки или запуска сессии
    startSessionIfNotStarted();
    // Проверка авторизации
    checkAuth();
    // Генерация CSRF-токена
    csrf_token();

    frod($privileges_page);

    // Проверка, есть ли сообщение об ошибке
    $error_message = "";
    if (isset($_GET['error'])) {
        $raw_error = $_GET['error']; // Сохраняем сырое значение
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
    }
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include 'include/all_head.html'; ?>
        <!-- Подключение стилей -->
        <link rel="stylesheet" href="css/navbar.css"/>
        <link rel="stylesheet" href="css/dashboard.css"/>
        <link rel="stylesheet" href="css/error.css"/>
    </head>
    <body>
        <?php include 'include/eos_header.html'; ?>
        <?php include 'include/navbar.php'; ?>
        <main>
            <div class="message">
                <h1>Добро пожаловать, <?php echo isset($_SESSION['username']) ? htmlspecialchars($_SESSION['username']) : 'Гость'; ?>!</h1>
                <p>Единое окно сотрудника в разработке</p>
                <p> Скоро мы добавим информацию для Вас</p>
            </div>
            <?php include 'include/loading.html'; ?>
        </main>
        <?php include 'include/error.php'; ?>
        <?php include 'include/footer.php'; ?>
    </body>   
</html>