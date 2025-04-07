#!/bin/bash

# Скрипт для развертывания веб-проекта
# 1. Очищает целевую директорию
# 2. Копирует файлы из исходной директории
# 3. Устанавливает правильные права

# Пути
SOURCE_DIR="/home/archie/PROJECT_FILES/web"
TARGET_DIR="/var/www/html/web"

# Проверка прав доступа
if [ "$(id -u)" -ne 0 ]; then
    echo "Этот скрипт должен запускаться с правами root (sudo)" >&2
    exit 1
fi

# 1. Удаление всех файлов из целевой директории
echo "Очистка целевой директории ${TARGET_DIR}..."
rm -rf "${TARGET_DIR}"/* 2>/dev/null
if [ $? -ne 0 ]; then
    echo "Ошибка при очистке директории" >&2
    exit 1
fi

# 2. Копирование файлов
echo "Копирование файлов из ${SOURCE_DIR} в ${TARGET_DIR}..."
cp -R "${SOURCE_DIR}/." "${TARGET_DIR}/" 2>/dev/null
if [ $? -ne 0 ]; then
    echo "Ошибка при копировании файлов" >&2
    exit 1
fi

# 3. Установка прав владельца
echo "Установка прав владельца для ${TARGET_DIR}..."
chown -R www-data:www-data "${TARGET_DIR}"
if [ $? -ne 0 ]; then
    echo "Ошибка при установке прав" >&2
    exit 1
fi

# 4. Удаление лог файлов
echo "Удаление лог файлов"
rm -f /var/log/slm/web/v1/*
rm -f /var/log/slm/web/v2/*
rm -f /var/log/nginx/*
rm -f /var/log/php8.3-fpm.log.*
# Рестарт NGINX
echo "Рестарт NGINX"
systemctl restart nginx

echo "Операция успешно завершена!"
exit 0
