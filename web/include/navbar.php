<?php

    // Проверяем наличие конфигов
    if (!file_exists(CONFIG_MENU)) {
        logger("ERROR", "Файл меню не найден: " . CONFIG_MENU);
        echo '';
        exit();
    }

    try {
        $pdo = connectToDatabase();
    } catch (Exception $e) {
        logger("ERROR", "Ошибка подключения к БД: " . $e->getMessage());
        header("Location: " . SERVER_ERROR);
        exit();
    }

    if (!isset($_SESSION['userid'])) {
        logger("ERROR", "Значение userid отсутствует в сессии.");
        header("Location: " . FORBIDDEN);
        exit();
    }

    $allowedModules = [];
    try {
        $stmt = $pdo->prepare("SELECT id_privileges FROM privileges WHERE userid = :userid");
        $stmt->execute([':userid' => $_SESSION['userid']]);
        
        // Результат
        $allowedModules = $stmt->fetchAll(PDO::FETCH_COLUMN);

        if (empty($allowedModules)) {
            logger("WARNING", "Список разрешенных модулей пуст для пользователя с userid: " . $_SESSION['userid']);
        }

    } catch (Exception $e) {
        logger("ERROR", "Ошибка при получении списка разрешенных модулей: " . $e->getMessage());
        header("Location: " . SERVER_ERROR);
        exit();
    }

    if (empty($allowedModules)) {
        logger("ERROR", "У пользователя с userid: " . $_SESSION['userid'] . " нет доступных модулей.");
        echo '<ul class="navbar"></ul>';
        echo '<script src="js/navbar.js"></script>';
        exit();
    }

    $menuData = json_decode(file_get_contents(CONFIG_MENU), true);
    if (empty($menuData['menu'])) {
        logger("ERROR", "Данные меню не найдены.");
        echo '';
        exit();
    }

    // Генерируем HTML
    $menuHtml = '<ul class="navbar">';
    foreach ($menuData['menu'] as $item) {
        // Проверяем guid
        if (!isset($item['guid'])) {
            logger("ERROR", "Пункт меню '{$item['title']}' пропущен, так как отсутствует guid.");
            continue;
        }

        // Проверяем, активен ли пункт меню
        if (!$item['active']) {
            //logger("DEBUG", "Пункт меню '{$item['title']}' пропущен, так как он отключен (active = false).");
            continue;
        }

        // Проверяем, есть ли права доступа к этому пункту
        if (!in_array($item['guid'], $allowedModules)) {
            //logger("DEBUG", "Пункт меню '{$item['title']}' пропущен, так как у пользователя нет прав на guid: {$item['guid']}");
            continue;
        }

        $html = '<li';
        if (!empty($item['dropdown'])) {
            $html .= ' class="dropdown"';
        }
        $html .= '>';

        // Ссылка для пункта меню
        $html .= '<a href="' . htmlspecialchars($item['url']) . '"';
        if (!empty($item['dropdown'])) {
            $html .= ' class="dropdown-toggle"';
        }
        $html .= '>';
        if (!empty($item['icon'])) {
            $html .= '<i class="material-icons">' . htmlspecialchars($item['icon']) . '</i> ';
        }
        $html .= htmlspecialchars($item['title']);
        $html .= '</a>';

        // Если есть выпадающее меню
        if (!empty($item['dropdown'])) {
            $dropdownHtml = '<ul class="dropdown-menu">';
            foreach ($item['dropdown'] as $dropdownItem) {
                // Проверяем, существует ли guid для вложенного пункта
                if (!isset($dropdownItem['guid'])) {
                    logger("ERROR", "Вложенный пункт меню '{$dropdownItem['title']}' пропущен, так как отсутствует guid.");
                    continue;
                }

                // Проверяем, активен ли вложенный пункт меню
                if (!$dropdownItem['active']) {
                    //logger("DEBUG", "Вложенный пункт меню '{$dropdownItem['title']}' пропущен, так как он отключен (active = false).");
                    continue;
                }

                // Проверяем, есть ли права доступа к вложенному пункту
                if (!in_array($dropdownItem['guid'], $allowedModules)) {
                    //logger("DEBUG", "Вложенный пункт меню '{$dropdownItem['title']}' пропущен, так как у пользователя нет прав на guid: {$dropdownItem['guid']}");
                    continue;
                }

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
    $html .= '</ul>';
    // Завершение логирования
    logger("INFO", "Навбар успешно создан.");

    // Выводим сгенерированное меню


?>
    <div class="generate_navbar">
        <?php echo $menuHtml; ?>
        <script src="/js/navbar.js"></script>
    </div>

