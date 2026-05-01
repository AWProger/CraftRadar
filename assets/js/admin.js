/**
 * CraftRadar — JS админки
 */

document.addEventListener('DOMContentLoaded', function () {

    // === Мобильное меню сайдбара ===
    const adminBurger = document.getElementById('adminBurger');
    const adminSidebar = document.getElementById('adminSidebar');

    if (adminBurger && adminSidebar) {
        adminBurger.addEventListener('click', function () {
            adminSidebar.classList.toggle('open');
        });

        document.addEventListener('click', function (e) {
            if (adminSidebar.classList.contains('open') &&
                !adminSidebar.contains(e.target) &&
                !adminBurger.contains(e.target)) {
                adminSidebar.classList.remove('open');
            }
        });
    }

    // === Выбрать все чекбоксы ===
    const selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.row-checkbox').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateSelectedCount();
        });
    }

    // === Счётчик выбранных элементов ===
    function updateSelectedCount() {
        var checked = document.querySelectorAll('.row-checkbox:checked').length;
        var counter = document.getElementById('selectedCount');
        if (counter) {
            counter.textContent = checked > 0 ? 'Выбрано: ' + checked : '';
            counter.style.display = checked > 0 ? 'inline' : 'none';
        }
    }

    document.querySelectorAll('.row-checkbox').forEach(function(cb) {
        cb.addEventListener('change', updateSelectedCount);
    });

    // Создаём элемент счётчика если есть чекбоксы
    if (document.querySelector('.row-checkbox')) {
        var bulkDiv = document.querySelector('[name="bulk_action"]');
        if (bulkDiv) {
            var span = document.createElement('span');
            span.id = 'selectedCount';
            span.style.cssText = 'color:var(--accent);font-size:0.8rem;font-weight:700;display:none;margin-left:8px;';
            bulkDiv.parentNode.insertBefore(span, bulkDiv.nextSibling);
        }
    }

    // === Подтверждение опасных действий ===
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

    // === Автоскрытие алертов ===
    document.querySelectorAll('.alert').forEach(function (alert) {
        setTimeout(function () {
            alert.style.opacity = '0';
            alert.style.transition = 'opacity 0.5s';
            setTimeout(function () {
                alert.remove();
            }, 500);
        }, 5000);
    });

    // === Копирование текста по клику ===
    document.querySelectorAll('[data-copy]').forEach(function (el) {
        el.addEventListener('click', function () {
            navigator.clipboard.writeText(this.dataset.copy).then(function () {
                el.textContent = '✓ Скопировано';
                setTimeout(function () {
                    el.textContent = el.dataset.copyLabel || el.dataset.copy;
                }, 2000);
            });
        });
    });

    // === Подсветка текущей страницы в навигации ===
    const currentPath = window.location.pathname;
    document.querySelectorAll('.admin-nav-link').forEach(function (link) {
        const href = link.getAttribute('href');
        if (href && currentPath.includes(href.split('?')[0].split('/').pop())) {
            link.classList.add('active');
        }
    });

});
