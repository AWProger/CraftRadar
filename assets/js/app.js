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

    // === Живой поиск (AJAX) ===
    const searchInput = document.querySelector('.search-input[data-live-search]');
    if (searchInput) {
        let searchTimeout = null;
        const dropdown = document.createElement('div');
        dropdown.className = 'search-dropdown';
        searchInput.parentElement.style.position = 'relative';
        searchInput.parentElement.appendChild(dropdown);

        searchInput.addEventListener('input', function () {
            const query = this.value.trim();
            clearTimeout(searchTimeout);

            if (query.length < 2) {
                dropdown.style.display = 'none';
                return;
            }

            searchTimeout = setTimeout(function () {
                fetch(searchInput.dataset.liveSearch + '?q=' + encodeURIComponent(query))
                    .then(function (r) { return r.json(); })
                    .then(function (data) {
                        if (!data.results || data.results.length === 0) {
                            dropdown.innerHTML = '<div class="search-dropdown-empty">Ничего не найдено</div>';
                            dropdown.style.display = 'block';
                            return;
                        }

                        dropdown.innerHTML = data.results.map(function (s) {
                            return '<a href="' + s.url + '" class="search-dropdown-item">' +
                                (s.icon ? '<img src="' + s.icon + '" class="search-dropdown-icon" alt="">' : '<span class="search-dropdown-icon">📡</span>') +
                                '<div class="search-dropdown-info">' +
                                    '<div class="search-dropdown-name">' + s.name + '</div>' +
                                    '<div class="search-dropdown-meta">' + s.ip +
                                        (s.is_online ? ' <span style="color:#3fb950;">● ' + s.players_online + '/' + s.players_max + '</span>' : ' <span style="color:#f85149;">● Оффлайн</span>') +
                                    '</div>' +
                                '</div>' +
                                '<div class="search-dropdown-votes">' + s.votes_month + ' 👍</div>' +
                            '</a>';
                        }).join('');
                        dropdown.style.display = 'block';
                    });
            }, 300);
        });

        // Закрытие при клике вне
        document.addEventListener('click', function (e) {
            if (!searchInput.contains(e.target) && !dropdown.contains(e.target)) {
                dropdown.style.display = 'none';
            }
        });

        // Закрытие при Escape
        searchInput.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') dropdown.style.display = 'none';
        });
    }

});
