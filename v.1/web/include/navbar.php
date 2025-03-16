<?php

    // Проверяем наличие файла конфигурации меню
    if (!file_exists(CONFIG_MENU)) {
        logger("ERROR", "Файл меню не найден: " . CONFIG_MENU);
        echo ''; // Выводим пустую строку, если файл не найден
        exit(); // Завершаем выполнение скрипта
    }

    // Пытаемся подключиться к базе данных
    try {
        $pdo = connectToDatabase();
        logger("INFO", "Подключение к базе данных выполнено успешно.");
    } catch (Exception $e) {
        logger("ERROR", "Ошибка подключения к БД: " . $e->getMessage());
        header("Location: " . SERVER_ERROR); // Перенаправляем на страницу ошибки сервера
        exit(); // Завершаем выполнение скрипта
    }

    // Проверяем, установлен ли идентификатор пользователя в сессии
    if (!isset($_SESSION['userid'])) {
        logger("ERROR", "Значение userid отсутствует в сессии.");
        header("Location: " . FORBIDDEN); // Перенаправляем на страницу "Доступ запрещен"
        exit(); // Завершаем выполнение скрипта
    }

    $allowedModules = []; // Массив для хранения разрешенных модулей
    try {
        // Подготавливаем запрос для получения списка привилегий пользователя
        $stmt = $pdo->prepare("SELECT id_privileges FROM privileges WHERE userid = :userid");
        $stmt->execute([':userid' => $_SESSION['userid']]);
        
        // Получаем результат в виде массива
        $allowedModules = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Логируем предупреждение, если список разрешенных модулей пуст
        if (empty($allowedModules)) {
            logger("WARNING", "Список разрешенных модулей пуст для пользователя с userid: " . $_SESSION['userid']);
        }

    } catch (Exception $e) {
        logger("ERROR", "Ошибка при получении списка разрешенных модулей: " . $e->getMessage());
        header("Location: " . SERVER_ERROR); // Перенаправляем на страницу ошибки сервера
        exit(); // Завершаем выполнение скрипта
    }

    // Если список разрешенных модулей пуст, выводим пустое меню и завершаем выполнение
    if (empty($allowedModules)) {
        logger("ERROR", "У пользователя с userid: " . $_SESSION['userid'] . " нет доступных модулей.");
        echo '<ul class="navbar"></ul>';
        echo '<script src="js/navbar.js"></script>';
        exit();
    }

    // Читаем данные меню из файла конфигурации
    $menuData = json_decode(file_get_contents(CONFIG_MENU), true);
    if (empty($menuData['menu'])) {
        logger("ERROR", "Данные меню не найдены.");
        echo ''; // Выводим пустую строку, если данные меню отсутствуют
        exit(); // Завершаем выполнение скрипта
    }

    // Генерируем HTML для меню
    $menuHtml = '<ul class="navbar">';
    foreach ($menuData['menu'] as $item) {
        // Проверяем наличие GUID у пункта меню
        if (!isset($item['guid'])) {
            logger("ERROR", "Пункт меню '{$item['title']}' пропущен, так как отсутствует guid.");
            continue; // Пропускаем пункт меню, если GUID отсутствует
        }

        // Проверяем, активен ли пункт меню
        if (!$item['active']) {
            logger("DEBUG", "Пункт меню '{$item['title']}' пропущен, так как он отключен (active = false).");
            continue; // Пропускаем пункт меню, если он не активен
        }

        // Проверяем, есть ли у пользователя права доступа к этому пункту меню
        if (!in_array($item['guid'], $allowedModules)) {
            logger("DEBUG", "Пункт меню '{$item['title']}' пропущен, так как у пользователя нет прав на guid: {$item['guid']}");
            continue; // Пропускаем пункт меню, если нет прав доступа
        }

        // Начинаем формирование HTML для пункта меню
        $html = '<li';
        if (!empty($item['dropdown'])) {
            $html .= ' class="dropdown"'; // Добавляем класс, если есть выпадающее меню
        }
        $html .= '>';

        // Добавляем ссылку для пункта меню
        $html .= '<a href="' . htmlspecialchars($item['url']) . '"';
        if (!empty($item['dropdown'])) {
            $html .= ' class="dropdown-toggle"'; // Добавляем класс для выпадающего меню
        }
        $html .= '>';
        if (!empty($item['icon'])) {
            $html .= '<i class="material-icons">' . htmlspecialchars($item['icon']) . '</i> '; // Добавляем иконку, если она есть
        }
        $html .= htmlspecialchars($item['title']); // Добавляем заголовок пункта меню
        $html .= '</a>';

        // Если есть выпадающее меню, формируем его
        if (!empty($item['dropdown'])) {
            $dropdownHtml = '<ul class="dropdown-menu">';
            foreach ($item['dropdown'] as $dropdownItem) {
                // Проверяем наличие GUID у вложенного пункта меню
                if (!isset($dropdownItem['guid'])) {
                    logger("ERROR", "Вложенный пункт меню '{$dropdownItem['title']}' пропущен, так как отсутствует guid.");
                    continue; // Пропускаем вложенный пункт, если GUID отсутствует
                }

                // Проверяем, активен ли вложенный пункт меню
                if (!$dropdownItem['active']) {
                    logger("DEBUG", "Вложенный пункт меню '{$dropdownItem['title']}' пропущен, так как он отключен (active = false).");
                    continue; // Пропускаем вложенный пункт, если он не активен
                }

                // Проверяем, есть ли у пользователя права доступа к вложенному пункту
                if (!in_array($dropdownItem['guid'], $allowedModules)) {
                    logger("DEBUG", "Вложенный пункт меню '{$dropdownItem['title']}' пропущен, так как у пользователя нет прав на guid: {$dropdownItem['guid']}");
                    continue; // Пропускаем вложенный пункт, если нет прав доступа
                }

                // Формируем HTML для вложенного пункта меню
                $dropdownHtml .= '<li><a href="' . htmlspecialchars($dropdownItem['url']) . '">';
                if (!empty($dropdownItem['icon'])) {
                    $dropdownHtml .= '<i class="material-icons">' . htmlspecialchars($dropdownItem['icon']) . '</i> ';
                }
                $dropdownHtml .= htmlspecialchars($dropdownItem['title']) . '</a></li>';
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
        <script src="/js/navbar.js"></script>
    </div>