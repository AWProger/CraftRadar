/**
 * CraftRadar — Основной JS
 */

document.addEventListener('DOMContentLoaded', function () {

    // === Мобильное меню ===
    const burger = document.getElementById('burger');
    if (burger) {
        burger.addEventListener('click', function () {
            document.body.classList.toggle('menu-open');
        });
    }

    // === Копирование IP ===
    document.querySelectorAll('.copy-ip').forEach(function (el) {
        el.addEventListener('click', function () {
            const ip = this.dataset.ip;
            if (!ip) return;

            navigator.clipboard.writeText(ip).then(function () {
                el.textContent = '✓ Скопировано';
                setTimeout(function () {
                    el.textContent = ip + ' 📋';
                }, 2000);
            }).catch(function () {
                // Fallback
                const input = document.createElement('input');
                input.value = ip;
                document.body.appendChild(input);
                input.select();
                document.execCommand('copy');
                document.body.removeChild(input);
                el.textContent = '✓ Скопировано';
                setTimeout(function () {
                    el.textContent = ip + ' 📋';
                }, 2000);
            });
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

    // === Подтверждение опасных действий ===
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            if (!confirm(this.dataset.confirm)) {
                e.preventDefault();
            }
        });
    });

});
