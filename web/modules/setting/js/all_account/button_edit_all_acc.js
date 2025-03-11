document.addEventListener("DOMContentLoaded", function () {
    const editButton = document.getElementById('editButton');

    editButton.addEventListener('click', function () {
        const selectedCheckbox = document.querySelector('.userCheckbox:checked');
        if (selectedCheckbox) {
            const userId = selectedCheckbox.dataset.userid;
            redirectToEditUser(userId); 
        }
    });

    function redirectToEditUser(userid) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = 'edituser.php'; 
        form.style.display = 'none';

        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'userid';
        input.value = userid;

        form.appendChild(input);

        document.body.appendChild(form);

        form.submit();
    }
});