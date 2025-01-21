// Функция для логирования с уровнями
function log(level, message) {
  const timestamp = new Date().toISOString();
  console.log(`[${timestamp}] [${level}] ${message}`);
}

// Функция для создания навбара
function createNavbar(menuData) {
  log("INFO", "Начало создания навбара.");

  const navbar = document.getElementById('navbar');

  if (!navbar) {
      log("ERROR", "Элемент с ID 'navbar' не найден.");
      return;
  }

  navbar.innerHTML = ''; // Очищаем навбар
  log("DEBUG", "Навбар очищен.");

  menuData.forEach(item => {
      // Пропускаем пункт, если active = false
      if (item.active === false) {
          log("DEBUG", `Пункт меню "${item.title}" пропущен, так как active = false.`);
          return;
      }

      // Создаем элемент списка
      const li = document.createElement('li');
      log("DEBUG", `Создан элемент списка для пункта меню "${item.title}".`);

      // Создаем ссылку
      const a = document.createElement('a');
      a.href = item.url;
      a.innerHTML = `${item.icon ? `<i class="material-icons">${item.icon}</i>` : ''} ${item.title}`;
      log("DEBUG", `Создана ссылка для пункта меню "${item.title}".`);

      // Если есть выпадающее меню
      if (item.dropdown && item.dropdown.length > 0) {
          log("DEBUG", `Пункт меню "${item.title}" имеет выпадающее меню.`);
          const dropdown = document.createElement('ul');
          dropdown.classList.add('dropdown-menu');
          log("DEBUG", "Создано выпадающее меню.");

          // Добавляем вложенные пункты меню
          item.dropdown.forEach(dropdownItem => {
              // Пропускаем пункт, если active = false
              if (dropdownItem.active === false) {
                  log("DEBUG", `Вложенный пункт меню "${dropdownItem.title}" пропущен, так как active = false.`);
                  return;
              }

              const dropdownLi = document.createElement('li');
              const dropdownA = document.createElement('a');
              dropdownA.href = dropdownItem.url;
              dropdownA.innerHTML = `${dropdownItem.icon ? `<i class="material-icons">${dropdownItem.icon}</i>` : ''} ${dropdownItem.title}`;
              dropdownLi.appendChild(dropdownA);
              dropdown.appendChild(dropdownLi);
              log("DEBUG", `Добавлен вложенный пункт меню "${dropdownItem.title}".`);
          });

          // Добавляем выпадающее меню только если есть активные пункты
          if (dropdown.children.length > 0) {
              li.appendChild(a);
              li.appendChild(dropdown);
              li.classList.add('dropdown');
              log("DEBUG", `Выпадающее меню для пункта "${item.title}" добавлено в навбар.`);
          }
      } else {
          // Если нет выпадающего меню, просто добавляем ссылку
          li.appendChild(a);
          log("DEBUG", `Пункт меню "${item.title}" добавлен в навбар без выпадающего меню.`);
      }

      // Добавляем пункт меню в навбар
      navbar.appendChild(li);
      log("DEBUG", `Пункт меню "${item.title}" добавлен в навбар.`);
  });

  log("INFO", "Навбар успешно создан.");
}

// Загрузка JSON и инициализация навбара
log("INFO", "Начало загрузки меню из JSON.");
fetch('/config/menu.json')
  .then(response => {
      if (!response.ok) {
          throw new Error(`Ошибка загрузки меню: ${response.statusText}`);
      }
      return response.json();
  })
  .then(data => {
      log("INFO", "Меню успешно загружено из JSON.");
      createNavbar(data.menu);
  })
  .catch(error => {
      log("ERROR", `Ошибка загрузки меню: ${error.message}`);
  });