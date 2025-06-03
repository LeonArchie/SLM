"""
Сервис для работы с конфигурацией приложения.
Особенности:
- Чтение конфигурации из .env файла
- Шифрование секретов в оперативной памяти
- Автоматическое обновление при изменении .env
- Потокобезопасный доступ к конфигурации
"""

import os
import asyncio
from typing import Dict, Any
from dotenv import load_dotenv
from cryptography.fernet import Fernet
from watchdog.observers import Observer
from watchdog.events import FileSystemEventHandler
from services.logger_service import LoggerService

# Инициализация логгера для модуля конфигурации
logger = LoggerService.get_logger('app.config')

class SecretManager:
    """
    Класс для управления шифрованием секретных данных в памяти.
    Использует алгоритм Fernet (AES-128) для шифрования.
    """
    
    def __init__(self, encryption_key: str):
        """
        Инициализация менеджера секретов.
        
        :param encryption_key: Ключ шифрования в формате base64 (32 байта)
        """
        # Инициализация шифровальщика с переданным ключом
        self.cipher = Fernet(encryption_key.encode())

    def encrypt(self, value: str) -> str:
        """
        Шифрует строку для безопасного хранения в памяти.
        
        :param value: Исходное значение для шифрования
        :return: Зашифрованная строка в base64
        """
        return self.cipher.encrypt(value.encode()).decode()

    def decrypt(self, encrypted: str) -> str:
        """
        Дешифрует строку из памяти.
        
        :param encrypted: Зашифрованная строка в base64
        :return: Расшифрованное исходное значение
        """
        return self.cipher.decrypt(encrypted.encode()).decode()

    @staticmethod
    def generate_key() -> str:
        """
        Генерирует новый ключ шифрования.
        
        :return: Ключ в формате base64 (32 байта)
        """
        return Fernet.generate_key().decode()

class ConfigService:
    """
    Основной сервис управления конфигурацией.
    Реализует паттерн Singleton для глобального доступа.
    """
    
    # Статические переменные для реализации Singleton
    _instance = None
    _lock = asyncio.Lock()  # Блокировка для потокобезопасности
    _config: Dict[str, Any] = {}  # Кеш конфигурации
    _running = False  # Флаг работы сервиса
    _observer: Observer = None  # Наблюдатель за файлами

    def __new__(cls):
        """
        Реализация паттерна Singleton.
        Гарантирует единственный экземпляр сервиса.
        """
        if cls._instance is None:
            cls._instance = super().__new__(cls)
        return cls._instance

    async def start(self) -> None:
        """
        Запускает сервис конфигурации.
        Инициализирует загрузку конфига и наблюдение за файлами.
        """
        if self._running:
            return

        self._running = True
        
        # Первоначальная загрузка конфигурации
        await self._load_config()

        # Инициализация наблюдателя за .env файлом
        self._observer = Observer()
        self._observer.schedule(
            ConfigFileEventHandler(self._on_config_change),
            path=os.path.dirname(os.path.abspath('.env')),
            recursive=False
        )
        self._observer.start()
        
        logger.info("Config service started with hot-reload")

    async def stop(self) -> None:
        """
        Корректно останавливает сервис.
        Останавливает наблюдение за файлами и освобождает ресурсы.
        """
        if not self._running:
            return

        self._running = False
        
        # Остановка наблюдателя
        if self._observer:
            self._observer.stop()
            self._observer.join()
            
        logger.info("Config service stopped")

    async def _on_config_change(self) -> None:
        """
        Обработчик изменений в .env файле.
        Вызывается при обнаружении модификации файла.
        """
        logger.debug("Detected .env change, reloading config")
        await self._load_config()

    async def _load_config(self) -> None:
        """
        Загружает и обрабатывает конфигурацию из .env файла.
        Шифрует чувствительные данные перед сохранением в памяти.
        """
        try:
            # Загрузка переменных из .env
            load_dotenv()
            raw_config = dict(os.environ)

            # Шифрование чувствительных полей
            if 'ENCRYPTION_KEY' in raw_config:
                secret_manager = SecretManager(raw_config['ENCRYPTION_KEY'])
                sensitive_fields = ['FLASK_KEY', 'JTW_KEY', 'DB_MASTER_PASS', 'DB_REPLICA_PASS', 'LDAP_PASSWORD']
                
                for field in sensitive_fields:
                    if field in raw_config:
                        raw_config[field] = secret_manager.encrypt(raw_config[field])

            # Безопасное обновление конфигурации
            async with self._lock:
                old_config = self._config
                self._config = raw_config
                self._log_config_diff(old_config, raw_config)

        except Exception as e:
            logger.error(f"Failed to load config: {str(e)}", exc_info=True)

    def _log_config_diff(self, old: Dict[str, Any], new: Dict[str, Any]) -> None:
        """
        Логирует изменения между старой и новой версиями конфигурации.
        Автоматически скрывает значения секретных полей.
        
        :param old: Предыдущая версия конфигурации
        :param new: Новая версия конфигурации
        """
        changed = {
            k: "<REDACTED>" if any(s in k for s in ['PASSWORD', 'SECRET', 'KEY']) 
               else new[k]
            for k in new if k not in old or old[k] != new[k]
        }
        
        if changed:
            logger.info(f"Config updated. Changed keys: {list(changed.keys())}")

    async def get_config(self, decrypt: bool = False) -> Dict[str, Any]:
        """
        Возвращает текущую конфигурацию.
        
        :param decrypt: Флаг дешифровки секретных полей
        :return: Словарь с конфигурацией
        """
        async with self._lock:
            config = self._config.copy()

            # Дешифровка по требованию
            if decrypt and 'ENCRYPTION_KEY' in config:
                secret_manager = SecretManager(config['ENCRYPTION_KEY'])
                sensitive_fields = ['DB_PASSWORD', 'API_KEY', 'SECRET']
                
                for field in sensitive_fields:
                    if field in config:
                        try:
                            config[field] = secret_manager.decrypt(config[field])
                        except Exception:
                            logger.warning(f"Failed to decrypt {field}")
                            continue

            return config

class ConfigFileEventHandler(FileSystemEventHandler):
    """
    Обработчик событий файловой системы для .env файла.
    Наследуется от базового класса watchdog.
    """
    
    def __init__(self, callback):
        """
        :param callback: Функция для вызова при изменении файла
        """
        self.callback = callback

    def on_modified(self, event):
        """
        Вызывается при изменении файла.
        
        :param event: Событие файловой системы
        """
        if os.path.basename(event.src_path) == '.env':
            # Запуск асинхронного колбэка
            asyncio.create_task(self.callback())

# Глобальный экземпляр сервиса для импорта
config_service = ConfigService()

async def get_config(decrypt_secrets: bool = False) -> Dict[str, Any]:
    """
    Публичный интерфейс для получения конфигурации.
    
    :param decrypt_secrets: Флаг дешифровки секретных полей
    :return: Словарь с конфигурацией
    """
    return await config_service.get_config(decrypt=decrypt_secrets)