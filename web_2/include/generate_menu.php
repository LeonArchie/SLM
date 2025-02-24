<?php

// Функция для создания пунктов меню
function createMenuItem($item) {
    if (!$item['active']) {
        logger("DEBUG", "Пункт меню '{$item['title']}' пропущен, так как active = false.");
        return '';
    }

    $html = '<li>';
    $html .= '<a href="' . htmlspecialchars($item['url']) . '">';
    if (!empty($item['icon'])) {
        $html .= '<i class="material-icons">' . htmlspecialchars($item['icon']) . '</i> ';
    }
    $html .= htmlspecialchars($item['title']);
    $html .= '</a>';

    // Если есть выпадающее меню
    if (!empty($item['dropdown'])) {
        $dropdownHtml = '';
        foreach ($item['dropdown'] as $dropdownItem) {
            if (!$dropdownItem['active']) {
                logger("DEBUG", "Вложенный пункт меню '{$dropdownItem['title']}' пропущен, так как active = false.");
                continue;
            }
            $dropdownHtml .= '<li><a href="' . htmlspecialchars($dropdownItem['url']) . '">';
            if (!empty($dropdownItem['icon'])) {
                $dropdownHtml .= '<i class="material-icons">' . htmlspecialchars($dropdownItem['icon']) . '</i> ';
            }
            $dropdownHtml .= htmlspecialchars($dropdownItem['title']) . '</a></li>';
        }

        if (!empty($dropdownHtml)) {
            $html .= '<ul class="dropdown-menu">' . $dropdownHtml . '</ul>';
            $html = '<li class="dropdown">' . $html;
        }
    }

    $html .= '</li>';
    return $html;
}

// Функция для генерации всего меню
function generateMenu() {
    global $CONFIG_MENU; // Используем глобальную переменную CONFIG_MENU

    logger("INFO", "Начало создания навбара.");

    // Читаем данные меню из JSON-файла
    if (!file_exists($CONFIG_MENU)) {
        logger("ERROR", "Файл меню не найден: $CONFIG_MENU");
        return '';
    }

    $menuData = json_decode(file_get_contents($CONFIG_MENU), true);

    if (empty($menuData['menu'])) {
        logger("ERROR", "Данные меню не найдены.");
        return '';
    }

    $menuHtml = '<ul id="navbar">';
    foreach ($menuData['menu'] as $item) {
        $menuItem = createMenuItem($item);
        if (!empty($menuItem)) {
            $menuHtml .= $menuItem;
        }
    }
    $menuHtml .= '</ul>';

    logger("INFO", "Навбар успешно создан.");
    return $menuHtml;
}

// Выводим сгенерированное меню
echo generateMenu();

?>