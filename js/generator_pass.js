document.addEventListener("DOMContentLoaded", function () {
    const generateButton = document.getElementById("generate-password");
    const passwordField = document.getElementById("password");
    generateButton.addEventListener("click", function () {
        const randomPassword = generateRandomPassword(5);
        passwordField.value = randomPassword;
    });
    function generateRandomPassword(length) {
        const charset = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        let password = "";
        for (let i = 0; i < length; i++) {
            const randomIndex = Math.floor(Math.random() * charset.length);
            password += charset[randomIndex];
        }
        return password;
    }
});