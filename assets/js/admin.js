/**
 * CraftRadar — JS админки
 */

document.addEventListener('DOMContentLoaded', function () {

    // Мобильное меню сайдбара
    const adminBurger = document.getElementById('adminBurger');
    const adminSidebar = document.getElementById('adminSidebar');

    if (adminBurger && adminSidebar) {
        adminBurger.addEventListener('click', function () {
            adminSidebar.classList.toggle('open');
        });

        // Закрытие при клике вне сайдбара
        document.addEventListener('click', function (e) {
            if (adminSidebar.classList.contains('open') &&
                !adminSidebar.contains(e.target) &&
                !adminBurger.contains(e.target)) {
                adminSidebar.classList.remove('open');
            }
        });
    }

    // Выбрать все чекбоксы
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.row-checkbox').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
        });
    }

});
