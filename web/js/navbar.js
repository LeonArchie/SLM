document.addEventListener('DOMContentLoaded', function () {
    const dropdownToggles = document.querySelectorAll('.dropdown-toggle');

    dropdownToggles.forEach(toggle => {
        toggle.addEventListener('click', function (e) {
            e.preventDefault(); // Предотвращаем переход по ссылке

            const dropdown = this.closest('.dropdown'); // Находим ближайший родительский .dropdown
            const menu = dropdown.querySelector('.dropdown-menu'); // Находим выпадающее меню

            // Переключаем класс 'open' для родительского элемента
            if (dropdown.classList.contains('open')) {
                dropdown.classList.remove('open');
            } else {
                // Закрываем другие открытые выпадающие меню
                document.querySelectorAll('.dropdown.open').forEach(otherDropdown => {
                    if (otherDropdown !== dropdown) {
                        otherDropdown.classList.remove('open');
                    }
                });

                dropdown.classList.add('open');
            }
        });
    });
});