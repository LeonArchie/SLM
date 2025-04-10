import json
from services.logger_service import LoggerService

# Создаем логгер для модуля
logger = LoggerService.get_logger('app.modules')

def load_modules():
    """
    Загружает данные меню из файла modules.json.
    """
    try:
        logger.info("Загрузка данных меню из modules.json...")
        with open("modules.json", "r", encoding="utf-8") as file:
            data = json.load(file)
            logger.debug(f"Успешно загружено {len(data.get('menu', []))} пунктов меню.")
            return data
    except FileNotFoundError:
        logger.error("Файл modules.json не найден.")
        raise Exception("Файл modules.json не найден.")
    except json.JSONDecodeError:
        logger.error("Ошибка при декодировании JSON в файле modules.json.")
        raise Exception("Ошибка при декодировании JSON в файле modules.json.")