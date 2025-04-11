/**
 * save.js - Полная версия с индикатором загрузки
 */

// Конфигурация API
const API_BASE_URL = `${window.location.protocol}//${window.location.hostname}:5000`;
const API_ENDPOINT = `${API_BASE_URL}/user/update`;

// Полная конфигурация полей формы
const FORM_FIELDS = {
  email: {
    id: 'email',
    required: true,
    pattern: /^[^\s@]+@[^\s@]+\.[^\s@]+$/,
    error: 'Некорректный формат email'
  },
  full_name: {
    id: 'fullName',
    required: true,
    pattern: /^[а-яА-ЯёЁ\s-]{1,70}$/,
    error: 'Только русские буквы и пробелы (макс. 70)'
  },
  family: {
    id: 'lastName',
    pattern: /^[а-яА-ЯёЁ]{0,20}$/,
    error: 'Только русские буквы (макс. 20)'
  },
  name: {
    id: 'firstName',
    pattern: /^[а-яА-ЯёЁ]{0,20}$/,
    error: 'Только русские буквы (макс. 20)'
  },
  telephone: {
    id: 'phone',
    pattern: /^(\+7|8)\d{10}$/,
    normalize: value => value.replace(/^8/, '+7'),
    error: 'Формат: +79991234567 или 89991234567'
  },
  tg_id: {
    id: 'telegramID',
    pattern: /^\d{0,15}$/,
    error: 'Только цифры (макс. 15)'
  },
  tg_username: {
    id: 'telegramUsername',
    pattern: /^[a-zA-Z0-9@_\-]{0,32}$/,
    error: 'Латиница, цифры, @, _, - (макс. 32)'
  }
};

// Функция показа/скрытия индикатора загрузки
function toggleLoading(show = true) {
  const loader = document.getElementById('loading');
  if (loader) {
    loader.style.display = show ? 'flex' : 'none';
  }
}

// Основная функция обработки сохранения
async function handleSave() {
  try {
    // Показываем индикатор загрузки
    toggleLoading(true);

    // 1. Проверка авторизации
    const authCheck = checkAuth();
    if (!authCheck.isValid) {
      showErrorMessage('error', 'Ошибка', authCheck.error, 5000);
      return;
    }

    // 2. Сбор и валидация данных
    const { data: formData, errors } = collectFormData();
    if (errors.length > 0) {
      errors.forEach(err => showErrorMessage('warning', 'Ошибка', err, 3000));
      return;
    }

    // 3. Отправка данных на сервер
    const response = await sendData(authCheck.token, authCheck.userId, formData);

    // 4. Обработка ответа сервера
    handleServerResponse(response);

  } catch (error) {
    console.error('Неожиданная ошибка:', error);
    showErrorMessage('error', 'Ошибка', 'Произошла непредвиденная ошибка', 5000);
  } finally {
    // Всегда скрываем индикатор загрузки
    toggleLoading(false);
  }
}

// Проверка авторизации
function checkAuth() {
  const token = localStorage.getItem('access_token');
  const userId = localStorage.getItem('user_id');

  if (!token || !userId) {
    return {
      isValid: false,
      error: 'Требуется авторизация. Перенаправление на страницу входа...',
      redirect: '/login'
    };
  }

  return { isValid: true, token, userId };
}

// Сбор и валидация данных формы
function collectFormData() {
  const data = {};
  const errors = [];

  Object.entries(FORM_FIELDS).forEach(([field, config]) => {
    const element = document.getElementById(config.id);
    const value = element?.value.trim() || '';

    // Проверка обязательных полей
    if (config.required && !value) {
      errors.push(`${getFieldLabel(field)} - обязательное поле`);
      return;
    }

    // Проверка по шаблону
    if (value && config.pattern && !config.pattern.test(value)) {
      errors.push(`${getFieldLabel(field)}: ${config.error}`);
      return;
    }

    // Нормализация и сохранение значения
    if (value) {
      data[field] = config.normalize ? config.normalize(value) : value;
    }
  });

  return { data, errors };
}

// Отправка данных на сервер
async function sendData(token, userId, formData) {
  try {
    const response = await fetch(API_ENDPOINT, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
      },
      body: JSON.stringify({
        access_token: token,
        userid: userId,
        ...formData
      })
    });

    return {
      ok: response.ok,
      status: response.status,
      data: await response.json()
    };
  } catch (error) {
    console.error('Ошибка сети:', error);
    return {
      ok: false,
      error: 'Ошибка соединения с сервером'
    };
  }
}

// Обработка ответа сервера
function handleServerResponse(response) {
  if (!response.ok) {
    const errorMsg = response.data?.error || 'Ошибка сервера';
    const details = response.data?.details ? ` (${response.data.details})` : '';
    
    showErrorMessage('error', 'Ошибка', `${errorMsg}${details}`, 5000);
    
    // Перенаправление при невалидном токене
    if (response.status === 401) {
      setTimeout(() => window.location.href = '/login', 2000);
    }
    return;
  }

  // Успешное сохранение
  showErrorMessage('success', 'Успех', 'Данные успешно сохранены', 3000);
  
  // Обновление данных в localStorage при необходимости
  if (response.data?.email) {
    localStorage.setItem('user_email', response.data.email);
  }
}

// Вспомогательные функции
function getFieldLabel(field) {
  const labels = {
    email: 'Email',
    family: 'Фамилия',
    full_name: 'Полное имя',
    name: 'Имя',
    telephone: 'Телефон',
    tg_id: 'Telegram ID',
    tg_username: 'Telegram username'
  };
  return labels[field] || field;
}

// Инициализация
document.addEventListener('DOMContentLoaded', () => {
  document.getElementById('saveButton')?.addEventListener('click', handleSave);
});