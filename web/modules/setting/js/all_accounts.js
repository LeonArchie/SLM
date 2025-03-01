document.getElementById('selectAll').addEventListener('change', function () {
    const checkboxes = document.querySelectorAll('.userCheckbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    updateButtonStates();
});

document.querySelectorAll('.userCheckbox').forEach(cb => {
    cb.addEventListener('change', updateButtonStates);
});

document.querySelectorAll('.name-cell a').forEach(link => {
    link.addEventListener('click', function (e) {
        e.preventDefault();
        const userId = this.dataset.userid;
        window.location.href = `edituser.php?userid=${userId}`;
    });
});

document.getElementById('refreshButton').addEventListener('click', function () {
    location.reload();
});

function updateButtonStates() {
    const checkedCount = document.querySelectorAll('.userCheckbox:checked').length;
    document.getElementById('editButton').disabled = checkedCount !== 1;
    document.getElementById('blockButton').disabled = checkedCount === 0;
    document.getElementById('deleteButton').disabled = checkedCount === 0;
}