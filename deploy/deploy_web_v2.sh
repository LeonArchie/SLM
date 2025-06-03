#!/bin/bash

# Скрипт для развертывания веб-проекта и Flask-приложения
# 1. Очищает целевые директории
# 2. Копирует файлы
# 3. Устанавливает права
# 4. Настраивает systemd сервис
# 5. Проверяет существование сервиса, останавливает и отключает его при необходимости

# Пути
SOURCE_WEB_DIR="/home/archie/PROJECT_FILES/web"       # Исходная директория для веб-файлов
SOURCE_APP_DIR="/home/archie/PROJECT_FILES/app"       # Исходная директория для файлов Flask-приложения
TARGET_WEB_DIR="/var/www/html/web"                    # Целевая директория для веб-файлов
TARGET_APP_DIR="/opt/slm/app"                         # Целевая директория для Flask-приложения
LOG_DIR="/var/log/slm"                                # Директория для логов
SERVICE_NAME="slm-app"                                # Имя systemd сервиса для Flask-приложения

# Проверка прав доступа
if [ "$(id -u)" -ne 0 ]; then
    echo "ОШИБКА: Этот скрипт должен запускаться с правами root (sudo)." >&2
    exit 1
fi

echo "Проверка прав доступа завершена успешно."

# Создание директорий, если их нет
echo "Создание целевых директорий..."
mkdir -p "$TARGET_WEB_DIR" "$TARGET_APP_DIR" "$LOG_DIR"
echo "Созданы директории: $TARGET_WEB_DIR, $TARGET_APP_DIR, $LOG_DIR"

# 1. Очистка целевых директорий
echo "Очистка целевых директорий..."
rm -rf "${TARGET_WEB_DIR:?}/"* "${TARGET_APP_DIR:?}/"* 2>/dev/null
echo "Очищены директории: $TARGET_WEB_DIR, $TARGET_APP_DIR"

# 2. Копирование файлов
echo "Копирование веб-файлов из $SOURCE_WEB_DIR в $TARGET_WEB_DIR..."
cp -R "${SOURCE_WEB_DIR}/." "${TARGET_WEB_DIR}/" 2>/dev/null || {
    echo "ОШИБКА: Не удалось скопировать веб-файлы из $SOURCE_WEB_DIR в $TARGET_WEB_DIR." >&2
    exit 1
}
echo "Веб-файлы успешно скопированы в $TARGET_WEB_DIR"

echo "Копирование файлов приложения из $SOURCE_APP_DIR в $TARGET_APP_DIR..."
cp -R "${SOURCE_APP_DIR}/." "${TARGET_APP_DIR}/" 2>/dev/null || {
    echo "ОШИБКА: Не удалось скопировать файлы приложения из $SOURCE_APP_DIR в $TARGET_APP_DIR." >&2
    exit 1
}
echo "Файлы приложения успешно скопированы в $TARGET_APP_DIR"

# 3. Установка прав
echo "Установка прав для директории $TARGET_WEB_DIR..."
chown -R www-data:www-data "$TARGET_WEB_DIR"
chmod -R 755 "$TARGET_WEB_DIR"
echo "Права установлены для $TARGET_WEB_DIR: Владелец www-data, права 755"

echo "Установка прав для директории $TARGET_APP_DIR..."
chown -R archie:archie "$TARGET_APP_DIR"
chmod -R 755 "$TARGET_APP_DIR"
echo "Права установлены для $TARGET_APP_DIR: Владелец archie, права 755"

# 4. Настройка systemd сервиса
echo "Проверка существования сервиса $SERVICE_NAME..."
if systemctl list-unit-files | grep -q "^$SERVICE_NAME\.service"; then
    echo "Сервис $SERVICE_NAME уже существует. Останавливаем и отключаем его..."
    systemctl stop "$SERVICE_NAME" 2>/dev/null
    systemctl disable "$SERVICE_NAME" 2>/dev/null
    echo "Сервис $SERVICE_NAME остановлен и отключен."
fi

echo "Настройка нового systemd сервиса..."
cat > "/etc/systemd/system/${SERVICE_NAME}.service" <<EOF
[Unit]
Description=SLM Flask Application
After=network.target

[Service]
User=archie
WorkingDirectory=/opt/slm/app
Environment="PYTHONPATH=/opt/slm/app"
ExecStart=/usr/bin/python3 /opt/slm/app/app.py
Restart=on-failure
RestartSec=5s

[Install]
WantedBy=multi-user.target
EOF
echo "Создан новый systemd сервис: /etc/systemd/system/$SERVICE_NAME.service"

# 5. Очистка логов
echo "Очистка логов..."
rm -f /var/log/nginx/*
rm -f /var/log/slm/*
echo "Очищены логи: /var/log/nginx/*, /var/log/slm/*"

echo "Создание новых лог-файлов..."
cat > "/var/log/slm/web.log" <<EOF
1
EOF

cat > "/var/log/slm/app.log" <<EOF
1
EOF
echo "Созданы новые лог-файлы: /var/log/slm/web.log, /var/log/slm/app.log"

echo "Установка прав для лог-файлов..."
chown -R www-data:www-data "/var/log/slm/web.log"
chown -R archie:archie "/var/log/slm/app.log"
echo "Права установлены для лог-файлов: web.log (www-data), app.log (archie)"

# 6. Перезапуск сервисов
echo "Обновление конфигурации systemd..."
systemctl daemon-reload
echo "Конфигурация systemd обновлена."

echo "Включение сервиса $SERVICE_NAME..."
systemctl enable "$SERVICE_NAME"
echo "Сервис $SERVICE_NAME включен."

echo "Запуск сервиса $SERVICE_NAME..."
systemctl start "$SERVICE_NAME"
echo "Сервис $SERVICE_NAME запущен."

echo "Перезапуск Nginx..."
systemctl restart nginx
echo "Nginx перезапущен."

# Проверка статуса
echo "Проверка статуса сервиса $SERVICE_NAME..."
systemctl status "$SERVICE_NAME" --no-pager
echo "Статус сервиса $SERVICE_NAME выведен."

echo "Развертывание успешно завершено!"
exit 0