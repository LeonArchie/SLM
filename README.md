Проект представляет собой веб-приложение для управления жизненным циклом серверов 
и приложений (Server Lifecycle Management, SLM). 

Оно включает в себя функционал для авторизации пользователей, регистрации новых 
учетных записей, управления задачами, оборудованием, инцидентами и запросами. 
Проект написан на PHP с использованием PostgreSQL в качестве базы данных и включает 
в себя HTML, CSS и JavaScript для фронтенда.

Основные компоненты проекта:
    1. Авторизация и аутентификация:
        login.php: Страница для входа в систему. Пользователь вводит логин и пароль, которые 
        проверяются через authorization.php.

        authorization.php: Обрабатывает данные из формы авторизации, проверяет CSRF-токен, 
        подключается к базе данных и проверяет логин и пароль. В случае успеха создает 
        сессию и куки для пользователя.

        logout.php: Завершает сессию пользователя, удаляет куки и перенаправляет на 
        страницу авторизации.

    CSRF-защита: На всех страницах, где происходит взаимодействие с формами, 
    используется CSRF-токен для защиты от межсайтовой подделки запросов.


    2. Регистрация пользователей:
        FR_register.php: Страница для ручной регистрации новых пользователей. 
        Включает форму для ввода данных (логин, пароль, email, роль и имя пользователя).

        BC_register.php: Обрабатывает данные из формы регистрации, проверяет 
        валидность данных, генерирует GUID для нового пользователя и сохраняет данные в базу данных.

        generator.php: Генерирует GUID (глобально уникальный идентификатор) 
        для новых пользователей. Используется как внешний сервис для генерации 
        уникальных идентификаторов.

    3. Управление учетными записями:
        all_accounts.php: Страница для просмотра всех учетных записей 
        пользователей (пока не реализована).

        my_account.php: Страница для управления личной учетной записью 
        пользователя (пока не реализована).


    4. Управление задачами:
        createtask.php: Страница для создания новых 
        задач (пока не реализована).

        registrytask.php: Страница для просмотра 
        реестра задач (пока не реализована).


    5. Управление оборудованием:
        hardware.php: Страница для управления оборудованием (пока не реализована).


    6. Управление инцидентами и запросами:
        incidentsrequests.php: Страница для управления инцидентами 
        и запросами (пока не реализована).


    7. Основные страницы:
        dashboard.php: Главная страница после авторизации. Отображает приветствие 
        и информацию о текущей сессии пользователя.

        index.php: Перенаправляет пользователя на страницу авторизации (login.php), 
        если он не авторизован, или на главную страницу (dashboard.php), если авторизован.

        info.php: Страница для отображения информации о PHP (используется функция phpinfo()).


    8. Обработка ошибок:
        403.html, 404.html, 50x.html: Страницы для отображения ошибок 
        (доступ запрещен, страница не найдена, ошибка сервера).

        50x.css, 403.css, 404.css: Стили для страниц ошибок.


    9. База данных:
        db_connect.php: Подключается к базе данных PostgreSQL, используя параметры 
        из конфигурационного файла config.json. Включает обработку ошибок и логирование.

        config.json: Содержит конфигурационные данные для подключения к базе данных 
        и настройки веб-приложения (например, таймаут сессии, хост, URL генератора GUID).


    10. Фронтенд:
        header.html: Шапка сайта с логотипом и названием.

        navbar.html: Навигационная панель с ссылками на основные разделы сайта.

        footer.html: Подвал сайта с информацией о версии и лицензии.

        all.css: Основные стили для всех страниц.

        login.css, register.css: Стили для страниц авторизации и регистрации.

        navbar.css: Стили для навигационной панели.

        generator_pass.js: Скрипт для генерации случайного пароля на странице регистрации.

        input_err.js: Скрипт для обработки ошибок ввода на странице авторизации.

        register.js: Скрипт для обработки формы регистрации, отправки данных на сервер и отображения уведомлений.



Основные технологии:
    Backend:

        PHP: Основной язык для серверной логики.
        PostgreSQL: База данных для хранения информации о пользователях, задачах, оборудовании и инцидентах.
        Сессии и куки: Используются для управления авторизацией пользователей.
        CSRF-токены: Для защиты от межсайтовой подделки запросов.


    Frontend:

        HTML: Структура страниц.
        CSS: Стилизация страниц.
        JavaScript: Интерактивные элементы (генерация пароля, обработка форм).


    Дополнительные инструменты:

        JSON: Используется для хранения конфигурационных данных (config.json).
        GUID: Генерация уникальных идентификаторов для пользователей.



Основные функции:
    Авторизация и аутентификация:
        Пользователь может войти в систему, используя логин и пароль.
        Сессия пользователя защищена CSRF-токенами.

    Регистрация новых пользователей:
        Администратор может зарегистрировать нового пользователя, указав его данные и роль.
        Пароль генерируется автоматически или вводится вручную.

    Управление задачами и оборудованием:
        Пользователь может создавать задачи, просматривать реестр задач и управлять оборудованием (функционал пока не реализован).

    Управление инцидентами и запросами:
        Пользователь может создавать и просматривать инциденты и запросы (функционал пока не реализован).

    Обработка ошибок:
        Приложение отображает страницы ошибок (403, 404, 500) с понятными сообщениями.

Архитектура проекта:
    Структура файлов:
        PHP-файлы отвечают за серверную логику.
        HTML-файлы (header, navbar, footer) используются для повторяющихся элементов на всех страницах.
        CSS и JavaScript файлы отвечают за стилизацию и интерактивность.

    База данных:
        Используется PostgreSQL для хранения данных.
        Подключение к базе данных осуществляется через PDO (PHP Data Objects).

    Безопасность:
        CSRF-токены защищают формы от подделки запросов.
        Пароли хранятся в виде хэшей (используется функция password_hash).