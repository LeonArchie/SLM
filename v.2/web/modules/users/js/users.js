// Обработка клика по аватарке (можно открыть профиль)
document.addEventListener('click', function(e) {
    if (e.target.closest('.user-avatar')) {
        const row = e.target.closest('tr');
        const userId = row.querySelector('.userCheckbox').dataset.userid;
        redirectToEditUser(userId);
    }
});

function redirectToEditUser(userId) {
    // код для перехода к редактированию пользователя
    console.log('Redirect to edit user:', userId);
    // window.location.href = `/edit-user?id=${userId}`;
}