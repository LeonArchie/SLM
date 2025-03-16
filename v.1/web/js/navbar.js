// Ожидаем, пока весь HTML-документ будет загружен и готов к взаимодействию
document.addEventListener('DOMContentLoaded', function () {

    // Находим все элементы с классом 'dropdown-toggle' (это могут быть кнопки или ссылки, открывающие выпадающие меню)
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

    // Перебираем каждый найденный элемент
    dropdownToggles.forEach(toggle => {

        // Добавляем обработчик события 'click' на каждый элемент
        toggle.addEventListener('click', function (e) {

            // Предотвращаем стандартное поведение элемента (например, переход по ссылке)
            e.preventDefault();

            // Находим ближайший родительский элемент с классом 'dropdown'
            const dropdown = this.closest('.dropdown');

            // Внутри этого родительского элемента находим выпадающее меню с классом 'dropdown-menu'
            const menu = dropdown.querySelector('.dropdown-menu');

            // Проверяем, есть ли у родительского элемента класс 'open'
            if (dropdown.classList.contains('open')) {

                // Если класс 'open' есть, удаляем его, чтобы закрыть меню
                dropdown.classList.remove('open');
            } else {

                // Если класс 'open' отсутствует, сначала закрываем все другие открытые выпадающие меню
                document.querySelectorAll('.dropdown.open').forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('open');
                    }
                });

                // Добавляем класс 'open' текущему элементу, чтобы открыть его меню
                dropdown.classList.add('open');
            }
        });
    });
});