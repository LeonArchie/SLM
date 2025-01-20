// Функция для создания навбара
function createNavbar(menuData) {
    const navbar = document.getElementById('navbar');
    navbar.innerHTML = ''; // Очищаем навбар
  
    menuData.forEach(item => {
      // Пропускаем пункт, если active = false
      if (item.active === false) {
        return;
      }
  
      // Создаем элемент списка
      const li = document.createElement('li');
  
      // Создаем ссылку
      const a = document.createElement('a');
      a.href = item.url;
      a.innerHTML = `${item.icon ? `<i class="material-icons">${item.icon}</i>` : ''} ${item.title}`;
  
      // Если есть выпадающее меню
      if (item.dropdown && item.dropdown.length > 0) {
        const dropdown = document.createElement('ul');
        dropdown.classList.add('dropdown-menu');
  
        // Добавляем вложенные пункты меню
        item.dropdown.forEach(dropdownItem => {
          // Пропускаем пункт, если active = false
          if (dropdownItem.active === false) {
            return;
          }
  
          const dropdownLi = document.createElement('li');
          const dropdownA = document.createElement('a');
          dropdownA.href = dropdownItem.url;
          dropdownA.innerHTML = `${dropdownItem.icon ? `<i class="material-icons">${dropdownItem.icon}</i>` : ''} ${dropdownItem.title}`;
          dropdownLi.appendChild(dropdownA);
          dropdown.appendChild(dropdownLi);
        });
  
        // Добавляем выпадающее меню только если есть активные пункты
        if (dropdown.children.length > 0) {
          li.appendChild(a);
          li.appendChild(dropdown);
          li.classList.add('dropdown');
        }
      } else {
        // Если нет выпадающего меню, просто добавляем ссылку
        li.appendChild(a);
      }
  
      // Добавляем пункт меню в навбар
      navbar.appendChild(li);
    });
  }
  
  // Загрузка JSON и инициализация навбара
  fetch('/config/menu.json')
    .then(response => response.json())
    .then(data => createNavbar(data.menu))
    .catch(error => console.error('Ошибка загрузки меню:', error));