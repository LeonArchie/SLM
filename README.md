README.md
Краткое описание проекта и его компонентов.

generator_pass.js
Скрипт для генерации случайного пароля длиной 10 символов. Пароль состоит из букв (в верхнем и нижнем регистре) и цифр. При нажатии на кнопку "Сгенерировать" пароль отображается в поле ввода.

input_err.js
Скрипт для валидации формы авторизации. Проверяет, заполнены ли обязательные поля (логин и пароль). Если поле не заполнено, добавляется класс invalid для стилизации невалидного поля.

all.css
Основные стили для страницы: центрирование контента, стили для шапки, подвала и основного контента. Фон страницы — молочный цвет.

login.css
Стили для страницы авторизации: форма с закруглёнными углами, тенями и отступами, стили для полей ввода, кнопок и сообщений об ошибках.

navbar.css
Стили для навигационной панели: фон меню, тени, выпадающее меню для пункта "Учетные записи", эффекты при наведении на ссылки.

register.css
Стили для страницы регистрации: форма с полями для email, логина, пароля и выбора роли, кнопка для генерации пароля.

authorization.php
Скрипт для обработки авторизации пользователя: проверка CSRF-токена, подключение к базе данных PostgreSQL, проверка логина и пароля, генерация сессии и куки, перенаправление на dashboard.php при успешной авторизации.

dashboard.php
Страница личного кабинета: проверка авторизации пользователя (закомментирована), отображение имени пользователя и ID сессии, подключение шапки, навигационной панели и подвала.

db_connect.php
Скрипт для подключения к базе данных PostgreSQL: параметры хоста, порта, имени базы данных, пользователя и пароля, обработка ошибок подключения.

footer.html
Подвал страницы: отображение версии приложения и информации о лицензии Apache 2.0.

FR_register.php
Страница ручной регистрации пользователя: генерация CSRF-токена, форма для ввода email, логина, пароля и выбора роли, подключение скрипта для генерации пароля.

header.html
Шапка страницы: логотип и название приложения "Server Lifecycle Management".

info.php
Скрипт для отображения информации о PHP (вызов функции phpinfo()).

login.php
Страница авторизации: генерация CSRF-токена, форма для ввода логина и пароля, отображение сообщений об ошибках.

logout.php
Скрипт для выхода из системы: удаление данных сессии и куки, перенаправление на страницу авторизации.

navbar.html
Навигационная панель: ссылки на главную страницу, создание заданий, реестр заданий, оборудование, заявки и учетные записи, выпадающее меню для управления учетными записями.

config.json
Конфигурационный файл с настройками для веб-приложения и базы данных.

50x.css, 403.css, 404.css
Стили для страниц ошибок (500, 403, 404).

50x.html, 403.html, 404.html
HTML-страницы для отображения ошибок сервера, доступа запрещён и страница не найдена.

BC_register.php
Скрипт для обработки регистрации пользователя: проверка CSRF-токена, валидация данных, генерация GUID, хэширование пароля, запись данных в базу данных.

generator.php
Скрипт для генерации GUID (глобально уникального идентификатора).

register.js
Скрипт для обработки формы регистрации: отправка данных на сервер, обработка ответа, отображение ошибок и уведомлений.

index.php
Главная страница: перенаправление на страницу авторизации или личный кабинет в зависимости от статуса авторизации пользователя.

my_account.php, all_accounts.php, createtask.php, hardware.php, incidentsrequests.php, registrytask.php
Страницы для управления учетными записями, создания заданий, работы с оборудованием, инцидентами и запросами, реестром заданий.

Основные технологии:
PHP: для серверной логики, работы с сессиями, CSRF-токенами, взаимодействия с базой данных.

PostgreSQL: база данных для хранения информации о пользователях и других данных.

HTML/CSS: для создания интерфейса и стилизации страниц.

JavaScript: для клиентской валидации, генерации паролей и обработки форм.

JSON: для хранения конфигурационных данных.