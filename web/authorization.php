<?php
require_once 'include/function.php';

// Логирование начала выполнения скрипта
logger("INFO", "Начало выполнения скрипта authorization.php.");

// Начало сессии
startSessionIfNotStarted();
logger("INFO", "Сессия успешно запущена. ID сессии: " . session_id());

// Проверка CSRF-токена
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    $error_message = "Ошибка безопасности: неверный CSRF-токен.";
    logger("ERROR", $error_message . " Ожидаемый токен: " . $_SESSION['csrf_token'] . ", Полученный токен: " . $_POST['csrf_token']);
    header("Location: login.php?error=" . urlencode($error_message));
    exit();
}

// Подключение к базе данных
require_once 'db_connect.php'; // Файл с подключением к PostgreSQL
logger("INFO", "Подключение к базе данных успешно установлено.");

// Проверка, была ли отправлена форма
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    logger("INFO", "Начало обработки формы авторизации. Метод запроса: POST.");

    // Получение данных из формы
    $login = trim($_POST['login']); // Удаляем лишние пробелы
    $password = trim($_POST['password']);
    logger("DEBUG", "Получены данные из формы. Логин: " . $login . ", Пароль: [скрыт].");

    // Проверка на пустые поля
    if (empty($login) || empty($password)) {
        $error_message = "Логин и пароль обязательны для заполнения!";
        logger("ERROR", $error_message . " Логин: " . (empty($login) ? "пустой" : "заполнен") . ", Пароль: " . (empty($password) ? "пустой" : "заполнен"));
        header("Location: login.php?error=" . urlencode($error_message));
        exit();
    }

    try {
        logger("INFO", "Попытка авторизации пользователя: " . $login);

        // Поиск пользователя в базе данных
        $sql = "SELECT userid, userlogin, password_hash, roleid, usernames FROM users WHERE userlogin = :userlogin";
        $stmt = $pdo->prepare($sql);
        $stmt->execute(['userlogin' => $login]);
        $user = $stmt->fetch();
        logger("DEBUG", "Запрос к базе данных выполнен. Найден пользователь: " . ($user ? "да" : "нет"));

        // Проверка пароля
        if ($user && password_verify($password, $user['password_hash'])) {
            logger("INFO", "Успешная авторизация пользователя: " . $login);

            // Успешная авторизация
            // Генерация уникального ID сессии
            $session_id = session_id();
            logger("DEBUG", "Сгенерирован ID сессии: " . $session_id);

            // Сохранение данных пользователя в сессии
            $_SESSION['username'] = htmlspecialchars($user['usernames']);
            $_SESSION['userid'] = $user['userid'];
            $_SESSION['session_id'] = $session_id;
            $_SESSION['roleid'] = $user['roleid'];
            logger("DEBUG", "Данные пользователя сохранены в сессии. Username: " . $_SESSION['username'] . ", UserID: " . $_SESSION['userid'] . ", RoleID: " . $_SESSION['roleid']);

            // Запись ID сессии в куки
            setcookie("session_id", $session_id, time() + 3600, "/"); // Куки действует 1 час
            logger("DEBUG", "ID сессии записан в куки. Время жизни куки: 1 час.");

            // Перенаправление на защищенную страницу
            logger("INFO", "Перенаправление на dashboard.php.");
            header("Location: dashboard.php");
            exit();
        } else {
            // Неудачная авторизация
            logger("ERROR", "Неверный логин или пароль для пользователя: " . $login);
            sleep(2); // Задержка для защиты от брутфорса
            $error_message = "Неверный логин или пароль!";
            header("Location: login.php?error=" . urlencode($error_message));
            exit();
        }
    } catch (PDOException $e) {
        logger("ERROR", "Ошибка выполнения запроса: " . $e->getMessage() . ". SQL: " . $sql);
        $error_message = "Произошла ошибка. Пожалуйста, попробуйте позже.";
        header("Location: login.php?error=" . urlencode($error_message));
        exit();
    }
} else {
    // Если форма не была отправлена, перенаправляем на страницу авторизации
    logger("INFO", "Попытка доступа к authorization.php без отправки формы. Метод запроса: " . $_SERVER["REQUEST_METHOD"]);
    header("Location: login.php");
    exit();
}
?>