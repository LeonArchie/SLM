<?php
    $privileges_page = '3fda4364-74ff-4ea7-a4d4-5cca300758a2';
    $file_path = 'include/platform.php';
        
    if (!file_exists($file_path)) {
        header("Location: /err/50x.html");
        exit();
    }

    require_once $file_path;

    startSessionIfNotStarted();

    $file_path = CHECK_AUTH;
    if (!file_exists($file_path)) {
        header("Location: /err/50x.html");
        exit();
    }
    require_once $file_path;

    // Проверка привилегий для текущей страницы
    $file_path = FROD;

    // Проверка существования файла function.php
    if (!file_exists($file_path)) {
        // Если файл не существует, перенаправляем пользователя на страницу ошибки 503
        header("Location: /err/50x.html");
        exit(); // Прекращаем выполнение скрипта
    }

    // Подключение файла с функциями
    require_once $file_path;

    $contacts = [];
    $error = null;

    try {
        $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
        $host = str_replace([':80',':443'], '', $_SERVER['HTTP_HOST']);
        $apiUrl = "{$protocol}://{$host}:5000/adresbook/list";

        $access_token = $_SESSION['access_token'] ?? null;
        $user_id = $_SESSION['userid'] ?? null;

        if (!$access_token || !$user_id) {
            throw new Exception('Необходима авторизация');
        }

        $response = file_get_contents($apiUrl, false, stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => "Content-Type: application/json\r\n",
                'content' => json_encode([
                    'access_token' => $access_token,
                    'user_id' => $user_id
                ]),
                'timeout' => 5
            ]
        ]));

        if ($response === false) {
            throw new Exception('Ошибка при запросе к API');
        }

        $data = json_decode($response, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new Exception('Ошибка формата ответа API');
        }

        if (isset($data['error'])) {
            throw new Exception($data['error']);
        }

        $contacts = $data['contacts'] ?? [];
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }

    function getInitials($fullName) {
        $parts = explode(' ', $fullName);
        $initials = '';
        foreach ($parts as $part) {
            $initials .= mb_substr($part, 0, 1);
            if (mb_strlen($initials) >= 2) break;
        }
        return mb_strtoupper($initials);

    include "/platform/include/binding/inital_error.php";

    // Логирование успешной инициализации страницы
    logger("DEBUG", "adresbook.php успешно инициализирован.");
    }
?>
<!DOCTYPE html>
<html lang="ru">
    <head>
        <?php include ROOT_PATH . '/platform/include/visible/all_head.html'; ?>
        <link rel="stylesheet" href="/platform/include/css/navbar.css"/>
        <link rel="stylesheet" href="/platform/include/css/error.css"/>
        <link rel="stylesheet" href="css/adresbook.css"/>
        <title>ЕОС - Адресная книга</title>
    </head>
    <body>
        <?php include ROOT_PATH . '/platform/include/visible/eos_header.html'; ?>
        <?php include ROOT_PATH .'/platform/include/visible/navbar.php'; ?>
        
        <main>
            <div class="address-book-wrapper">
                <div class="address-book-header">
                    <h1><span class="address-book-icon"></span> Адресная книга</h1>
                    <div class="search-container">
                        <span class="search-icon"></span>
                        <input type="text" class="search-box" placeholder="Поиск по имени или отделу..." id="searchInput">
                    </div>
                </div>
                
                <?php if ($error): ?>
                    <div class="address-book-error">
                        <span class="error-icon"></span>
                        <span><?= htmlspecialchars($error) ?></span>
                    </div>
                <?php elseif (empty($contacts)): ?>
                    <div class="address-book-empty">
                        <span class="empty-icon"></span>
                        <p>Нет данных для отображения</p>
                    </div>
                <?php else: ?>
                    <div class="contacts-grid" id="contactsGrid">
                        <?php foreach ($contacts as $contact): ?>
                        <div class="contact-card" data-user-id="<?= htmlspecialchars($contact['user_id'] ?? '') ?>">
                            <div class="card-header">
                                <div class="user-avatar">
                                    <?= getInitials($contact['full_name'] ?? '') ?>
                                </div>
                                <div class="user-info">
                                    <h3><?= htmlspecialchars($contact['full_name'] ?? 'Неизвестно') ?></h3>
                                    <p class="position"><?= htmlspecialchars($contact['position'] ?? '—') ?></p>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="contact-field">
                                    <span class="field-icon">📧</span>
                                    <div>
                                        <p class="field-label">Корпоративная почта</p>
                                        <p class="field-value"><?= htmlspecialchars($contact['user_off_email'] ?? '—') ?></p>
                                    </div>
                                </div>
                                <div class="contact-field">
                                    <span class="field-icon">📧</span>
                                    <div>
                                        <p class="field-label">Личная почта</p>
                                        <p class="field-value"><?= htmlspecialchars($contact['personal_mail'] ?? '—') ?></p>
                                    </div>
                                </div>
                                <div class="contact-field">
                                    <span class="field-icon">📱</span>
                                    <div>
                                        <p class="field-label">Корпоративный телефон</p>
                                        <p class="field-value"><?= htmlspecialchars($contact['corp_phone'] ?? '—') ?></p>
                                    </div>
                                </div>
                                <div class="contact-field">
                                    <span class="field-icon">📞</span>
                                    <div>
                                        <p class="field-label">Телефон</p>
                                        <p class="field-value"><?= htmlspecialchars($contact['telephone'] ?? '—') ?></p>
                                    </div>
                                </div>
                                <div class="contact-field">
                                    <span class="field-icon">🏢</span>
                                    <div>
                                        <p class="field-label">Отдел</p>
                                        <p class="field-value"><?= htmlspecialchars($contact['department'] ?? '—') ?></p>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </main>
        
        <?php include ROOT_PATH . '/platform/include/visible/error.php'; ?>
        <?php include ROOT_PATH . '/platform/include/visible/footer.php'; ?>
        
        <script src="/platform/include/js/error.js"></script>
        <script src="/platform/include/js/check_jwt.js"></script>
        <script src="js/adresbook.js"></script>
    </body>
</html>