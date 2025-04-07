#!/bin/bash

# Скрипт для развертывания веб-проекта и Flask-приложения
# 1. Очищает целевые директории
# 2. Копирует файлы
# 3. Устанавливает права
# 4. Настраивает systemd сервис
# 5. Проверяет существование сервиса, останавливает и отключает его при необходимости

# Пути
SOURCE_WEB_DIR="/home/archie/PROJECT_FILES/web"
SOURCE_APP_DIR="/home/archie/PROJECT_FILES/app"
TARGET_WEB_DIR="/var/www/html/web"
TARGET_APP_DIR="/opt/slm/app"
LOG_DIR="/var/log/slm"
SERVICE_NAME="slm-app"

# Проверка прав доступа
if [ "$(id -u)" -ne 0 ]; then
    echo "Этот скрипт должен запускаться с правами root (sudo)" >&2
    exit 1
fi

# Создание директорий, если их нет
echo "Создание целевых директорий..."
mkdir -p "$TARGET_WEB_DIR" "$TARGET_APP_DIR" "$LOG_DIR/web/v1" "$LOG_DIR/web/v2" "$LOG_DIR/app/v2"
mkdir -p "$LOG_DIR/app" /etc/systemd/system/

# 1. Очистка целевых директорий
echo "Очистка целевых директорий..."
rm -rf "${TARGET_WEB_DIR:?}/"* "${TARGET_APP_DIR:?}/"* 2>/dev/null

# 2. Копирование файлов
echo "Копирование веб-файлов..."
cp -R "${SOURCE_WEB_DIR}/." "${TARGET_WEB_DIR}/" 2>/dev/null || {
    echo "Ошибка при копировании веб-файлов" >&2
    exit 1
}

echo "Копирование файлов приложения..."
cp -R "${SOURCE_APP_DIR}/." "${TARGET_APP_DIR}/" 2>/dev/null || {
    echo "Ошибка при копировании файлов приложения" >&2
    exit 1
}

# 3. Установка прав
echo "Установка прав..."
chown -R www-data:www-data "$TARGET_WEB_DIR"
chown -R archie:archie "$TARGET_APP_DIR"
chmod -R 755 "$TARGET_WEB_DIR"
chmod -R 755 "$TARGET_APP_DIR"

# 4. Настройка systemd сервиса
echo "Проверка существования сервиса $SERVICE_NAME..."
if systemctl list-unit-files | grep -q "^$SERVICE_NAME\.service"; then
    echo "Сервис $SERVICE_NAME уже существует. Останавливаем и отключаем его..."
    systemctl stop "$SERVICE_NAME" 2>/dev/null
    systemctl disable "$SERVICE_NAME" 2>/dev/null
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

# 5. Очистка логов
echo "Очистка логов..."
rm -f /var/log/nginx/*
rm -f /var/log/slm/app/v2/*
rm -f /var/log/slm/web/v2/*

# 6. Перезапуск сервисов
echo "Перезапуск сервисов..."
systemctl daemon-reload
systemctl enable "$SERVICE_NAME"
systemctl start "$SERVICE_NAME"
systemctl restart nginx

# Проверка статуса
echo "Проверка статуса сервиса $SERVICE_NAME..."
systemctl status "$SERVICE_NAME" --no-pager

echo "Развертывание успешно завершено!"
exit 0