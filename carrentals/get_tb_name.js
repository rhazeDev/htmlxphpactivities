document.addEventListener('DOMContentLoaded', (event) => {
    const tableName = document.querySelectorAll(".nav-button");

    tableName.forEach(data => {
        data.addEventListener('click', event => {
            window.location.href = 'dashboard.php?table=' + data.textContent;
        });
    });
});
