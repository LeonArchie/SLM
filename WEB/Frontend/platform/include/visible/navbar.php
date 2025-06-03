<?php
    // Автоматически определяем базовый URL с портом 5000
    $protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
    $host = $_SERVER['HTTP_HOST'];

    // Удаляем стандартный порт если он есть в HTTP_HOST
    $host = str_replace([':80', ':443'], '', $host);

    $apiBaseUrl = "{$protocol}://{$host}:5000";

    // Проверяем наличие access_token и userid в сессии
    if (!isset($_SESSION['access_token']) || !isset($_SESSION['userid'])) {
        logger("ERROR", "Отсутствуют access_token или userid в сессии.");
        echo '<ul class="navbar"><li>Меню недоступно</li></ul>';
        exit();
    }

    // Данные для запроса
    $accessToken = $_SESSION['access_token'];
    $userId = $_SESSION['userid'];

    // Формируем URL для API
    $apiUrl = $apiBaseUrl . "/setting/user/modules";

    // Подготовка данных для POST-запроса
    $postData = json_encode([
        "access_token" => $accessToken,
        "user_id" => $userId
    ]);

    // Инициализация cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $apiUrl);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ]);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

    // Выполняем запрос
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

    // Проверяем ошибки cURL
    if (curl_errno($ch)) {
        logger("ERROR", "Ошибка cURL: " . curl_error($ch));
        echo '<ul class="navbar"><li>Меню недоступно</li></ul>';
        curl_close($ch);
        exit();
    }

    curl_close($ch);

    // Обработка ответа
    if ($httpCode !== 200 || empty($response)) {
        logger("ERROR", "API вернул ошибку или пустой ответ. Код: $httpCode, Ответ: $response");
        echo '<ul class="navbar"><li>Меню недоступно</li></ul>';
        exit();
    }

    // Декодируем JSON-ответ
    $menuData = json_decode($response, true);

    // Проверяем структуру ответа
    if (empty($menuData['menu']) || !is_array($menuData['menu'])) {
        logger("ERROR", "API вернул некорректные данные меню.");
        echo '<ul class="navbar"><li>Меню недоступно</li></ul>';
        exit();
    }

    // Генерируем HTML для меню
    $menuHtml = '<ul class="navbar">';
    foreach ($menuData['menu'] as $item) {
        // Начинаем формирование HTML для пункта меню
        $html = '<li';
        if (!empty($item['dropdown'])) {
            $html .= ' class="dropdown"'; // Добавляем класс, если есть выпадающее меню
        }
        $html .= '>';

        // Добавляем ссылку для пункта меню
        $html .= '<a href="' . htmlspecialchars($item['url'] ?? '#') . '"';
        if (!empty($item['dropdown'])) {
            $html .= ' class="dropdown-toggle"'; // Добавляем класс для выпадающего меню
        }
        $html .= '>';
        if (!empty($item['icon'])) {
            $html .= '<i class="material-icons">' . htmlspecialchars($item['icon']) . '</i> '; // Добавляем иконку, если она есть
        }
        $html .= htmlspecialchars($item['title'] ?? 'Без названия'); // Добавляем заголовок пункта меню
        $html .= '</a>';

        // Если есть выпадающее меню, формируем его
        if (!empty($item['dropdown'])) {
            $dropdownHtml = '<ul class="dropdown-menu">';
            foreach ($item['dropdown'] as $dropdownItem) {
                // Формируем HTML для вложенного пункта меню
                $dropdownHtml .= '<li><a href="' . htmlspecialchars($dropdownItem['url'] ?? '#') . '">';
                if (!empty($dropdownItem['icon'])) {
                    $dropdownHtml .= '<i class="material-icons">' . htmlspecialchars($dropdownItem['icon']) . '</i> ';
                }
                $dropdownHtml .= htmlspecialchars($dropdownItem['title'] ?? 'Без названия') . '</a></li>';
            }
            $dropdownHtml .= '</ul>';
            $html .= $dropdownHtml;
        }
        $html .= '</li>';
        $menuHtml .= $html;
    }
    $menuHtml .= '</ul>';

    // Логируем успешное завершение генерации меню
    logger("INFO", "Генерация меню выполнена успешно");

?>
    <div class="generate_navbar">
        <?php echo $menuHtml; ?>
        <script src="/platform/include/js/navbar.js"></script>
    </div>
