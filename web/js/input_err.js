document.addEventListener("DOMContentLoaded", function () {
    const form = document.querySelector(".authorization form");
    const inputs = document.querySelectorAll(".authorization input[required]");

    // Проверка при отправке формы
    form.addEventListener("submit", function (event) {
        let isFormValid = true;

        inputs.forEach(input => {
            if (!input.value.trim()) {
                input.classList.add("invalid"); // Добавляем класс для невалидного поля
                isFormValid = false;
            } else {
                input.classList.remove("invalid"); // Убираем класс, если поле валидно
            }
        });

        if (!isFormValid) {
            event.preventDefault(); // Останавливаем отправку формы, если есть ошибки
        }
    });
});