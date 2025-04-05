<?php
    // Уникальный идентификатор страницы для проверки привилегий
    $privileges_page = '27cc1a41-fc05-4e9d-931f-8133812e71ba';

    // Путь к файлу platform.php
    $file_path = __DIR__ . '/include/platform.php';
    // Проверка существования файла platform.php
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем на страницу ошибки 500
        header("Location: /err/50x.html");
        exit();
    }
    // Подключение файла platform.php
    require_once $file_path;

    // Инициализация сессии, если она еще не начата
    startSessionIfNotStarted();
    // Проверка авторизации пользователя
    checkAuth();
    // Генерация CSRF-токена для защиты от атак
    csrf_token();

    // Проверка привилегий для доступа к странице
    FROD($privileges_page);

    // Подключение к базе данных с обработкой исключений
    try {
        $pdo = connectToDatabase();
    } catch (PDOException $e) {
        // Логирование ошибки подключения к базе данных
        logger("ERROR", "Ошибка подключения к базе данных: " . $e->getMessage());
        // Перенаправление на страницу ошибки 500
        header("Location: /err/50x.html");
        exit();
    }

    // Подготовка SQL-запроса для получения списка пользователей
    $stmt = $pdo->prepare("SELECT full_name, email, telephone FROM users WHERE active=true");

    // Выполнение запроса и проверка на ошибки
    if (!$stmt->execute()) {
        // Логирование ошибки, если запрос не выполнился
        logger("ERROR", "Ошибка получения списка пользователей.");
        // Перенаправление на страницу ошибки 500
        header("Location: /err/50x.html");
        exit();
    }

    // Получение всех записей из результата запроса
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Инициализация переменной для сообщения об ошибке
    $error_message = "";
    // Проверка наличия параметра ошибки в URL
    if (isset($_GET['error'])) {
        // Получение и экранирование значения ошибки
        $raw_error = $_GET['error'];
        $error_message = htmlspecialchars($raw_error, ENT_QUOTES, 'UTF-8');
    }
?>
<!DOCTYPE html>
    <html lang="ru">
        <head>
            <!-- Подключение общих мета-тегов и стилей -->
            <?php include ROOT_PATH . '/include/all_head.html'; ?>
            <!-- Подключение дополнительных стилей -->
            <link rel="stylesheet" href="/css/navbar.css"/>
            <link rel="stylesheet" href="css/addresbook.css"/>
            <link rel="stylesheet" href="/css/error.css"/>
        </head>
        <body>
            <!-- Подключение шапки сайта -->
            <?php include ROOT_PATH . '/include/eos_header.html'; ?>
            <!-- Подключение навигационной панели -->
            <?php include ROOT_PATH .'/include/navbar.php'; ?>
            <main>
                <!-- Контейнер для формы и таблицы -->
                <div class="form-container">
                    <!-- Контейнер для таблицы пользователей -->
                    <div class="table-container">
                        <table>
                            <thead>
                                <tr>
                                    <th>Полное ФИО</th>
                                    <th>Подразделение</th>
                                    <th>E-mail</th>
                                    <th>Телефон</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                    // Преобразование массива пользователей в индексированный массив
                                    $users = array_values($users);
                                    // Цикл для отображения каждого пользователя в таблице
                                    foreach ($users as $index => $user):
                                        // Экранирование данных пользователя для безопасного отображения
                                        $fullName = htmlspecialchars($user['full_name'] ?? 'Неизвестный');
                                        $useremail = htmlspecialchars($user['email'] ?? 'Не указан');
                                        $usertell = htmlspecialchars($user['telephone'] ?? 'Не указан');

                                ?>
                                    <tr>
                                        <!-- Ссылка на редактирование пользователя -->
                                        <td class="name-cell">
                                            <a href="#" onclick="event.preventDefault(); redirectToUser(<?= json_encode($user['userid']) ?>);">
                                                <?= $fullName ?>
                                            </a>
                                        </td>
                                        <td></td>
                                        <!-- Email пользователя -->
                                        <td><?= $useremail ?></td>
                                        <!-- Телефон пользователя -->
                                        <td><?= $usertell ?></td>                                                                                

                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
            <!-- Подключение блока для отображения ошибок -->
            <?php include ROOT_PATH . '/include/error.php'; ?>
            <!-- Подключение футера -->
            <?php include ROOT_PATH . '/include/footer.php'; ?>
            <!-- Подключение JavaScript-файлов -->
            <script src="/js/error.js"></script>
        </body>
    </html>