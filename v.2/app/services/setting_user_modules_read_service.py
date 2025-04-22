import json
from services.logger_service import LoggerService

# Создаем логгер для модуля
logger = LoggerService.get_logger('app.modules')

def load_modules():
    """
    Загружает данные меню из файла modules.json.
    Возвращает:
        dict: Словарь с данными меню.
    Выбрасывает:
        Exception: Если файл не найден или содержит некорректный JSON.
    """
    try:
        # Логирование начала загрузки данных меню
        logger.info("Загрузка данных меню из modules.json...")
        
        # Открытие и чтение файла modules.json
        with open("modules.json", "r", encoding="utf-8") as file:
            data = json.load(file)
            
            # Логирование успешной загрузки данных
            logger.debug(f"Успешно загружено {len(data.get('menu', []))} пунктов меню.")
            return data

    except FileNotFoundError:
        # Логирование ошибки, если файл modules.json не найден
        logger.error("Файл modules.json не найден.")
        raise Exception("Файл modules.json не найден.")

    except json.JSONDecodeError:
        # Логирование ошибки, если содержимое файла не является корректным JSON
        logger.error("Ошибка при декодировании JSON в файле modules.json.")
        raise Exception("Ошибка при декодировании JSON в файле modules.json.")