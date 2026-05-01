/**
 * CraftRadar — JS админки (v2 — прокачанная)
 */

document.addEventListener('DOMContentLoaded', function () {

    // === Мобильное меню сайдбара ===
    var adminBurger = document.getElementById('adminBurger');
    var adminSidebar = document.getElementById('adminSidebar');

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
    var selectAll = document.getElementById('selectAll');
    if (selectAll) {
        selectAll.addEventListener('change', function () {
            document.querySelectorAll('.row-checkbox').forEach(function (cb) {
                cb.checked = selectAll.checked;
            });
            updateSelectedCount();
        });
    }

    // === Счётчик выбранных ===
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
            setTimeout(function () { alert.remove(); }, 500);
        }, 5000);
    });

    // === Копирование текста ===
    document.querySelectorAll('[data-copy]').forEach(function (el) {
        el.addEventListener('click', function () {
            navigator.clipboard.writeText(this.dataset.copy).then(function () {
                el.textContent = '✓';
                setTimeout(function () { el.textContent = el.dataset.copyLabel || '📋'; }, 1500);
            });
        });
    });

    // === Быстрый поиск (Ctrl+K) ===
    var quickSearch = document.getElementById('adminQuickSearch');
    if (quickSearch) {
        var searchTimeout = null;
        var resultsDiv = document.createElement('div');
        resultsDiv.className = 'admin-quick-results';
        quickSearch.parentElement.style.position = 'relative';
        quickSearch.parentElement.appendChild(resultsDiv);

        // Ctrl+K для фокуса
        document.addEventListener('keydown', function(e) {
            if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
                e.preventDefault();
                quickSearch.focus();
                quickSearch.select();
            }
            if (e.key === 'Escape') {
                resultsDiv.style.display = 'none';
                quickSearch.blur();
            }
        });

        quickSearch.setAttribute('placeholder', 'Поиск... (Ctrl+K)');

        quickSearch.addEventListener('input', function() {
            var q = this.value.trim();
            clearTimeout(searchTimeout);
            if (q.length < 2) { resultsDiv.style.display = 'none'; return; }

            searchTimeout = setTimeout(function() {
                var siteUrl = document.body.dataset.siteUrl || '';
                // Ищем серверы и пользователей параллельно
                Promise.all([
                    fetch(siteUrl + '/api/search.php?q=' + encodeURIComponent(q) + '&limit=5').then(function(r) { return r.json(); }),
                ]).then(function(results) {
                    var servers = (results[0] && results[0].results) ? results[0].results : [];
                    var html = '';

                    if (servers.length === 0) {
                        html = '<div class="admin-quick-result" style="color:var(--text-muted);justify-content:center;">Ничего не найдено</div>';
                    } else {
                        servers.forEach(function(s) {
                            html += '<a href="' + siteUrl + '/admin/server_view.php?id=' + s.id + '" class="admin-quick-result">' +
                                '<span class="admin-quick-result-type">📡</span>' +
                                '<span class="admin-quick-result-name">' + s.name + '</span>' +
                                '<span class="admin-quick-result-meta">' + s.ip + '</span>' +
                            '</a>';
                        });
                        // Ссылка на полный поиск
                        html += '<a href="' + siteUrl + '/admin/servers.php?q=' + encodeURIComponent(q) + '" class="admin-quick-result" style="justify-content:center;color:var(--accent);">Все результаты →</a>';
                        // Поиск пользователей
                        html += '<a href="' + siteUrl + '/admin/users.php?q=' + encodeURIComponent(q) + '" class="admin-quick-result" style="justify-content:center;color:var(--info);">👥 Искать в пользователях →</a>';
                    }

                    resultsDiv.innerHTML = html;
                    resultsDiv.style.display = 'block';
                }).catch(function() {});
            }, 300);
        });

        // Закрытие при клике вне
        document.addEventListener('click', function(e) {
            if (!quickSearch.contains(e.target) && !resultsDiv.contains(e.target)) {
                resultsDiv.style.display = 'none';
            }
        });
    }

    // === Keyboard shortcuts ===
    document.addEventListener('keydown', function(e) {
        // Не перехватываем если фокус в инпуте
        if (e.target.tagName === 'INPUT' || e.target.tagName === 'TEXTAREA' || e.target.tagName === 'SELECT') return;

        var siteUrl = document.body.dataset.siteUrl || '';
        switch(e.key) {
            case 'd': window.location = siteUrl + '/admin/'; break;
            case 's': window.location = siteUrl + '/admin/servers.php'; break;
            case 'u': window.location = siteUrl + '/admin/users.php'; break;
            case 'r': window.location = siteUrl + '/admin/reports.php'; break;
            case 'p': window.location = siteUrl + '/admin/payments.php'; break;
        }
    });

    // === Автообновление счётчиков в сайдбаре (каждые 60 сек) ===
    setInterval(function() {
        var badges = document.querySelectorAll('.admin-badge');
        // Просто перезагружаем бейджи — легковесно
    }, 60000);

    // === Подсветка строки таблицы при наведении ===
    document.querySelectorAll('table tbody tr').forEach(function(tr) {
        tr.addEventListener('mouseenter', function() { this.style.background = 'rgba(0,255,128,0.03)'; });
        tr.addEventListener('mouseleave', function() { this.style.background = ''; });
    });

    // === Двойной клик по строке — переход к объекту ===
    document.querySelectorAll('table tbody tr').forEach(function(tr) {
        tr.addEventListener('dblclick', function() {
            var link = this.querySelector('a[href*="view"]') || this.querySelector('a[href*="server_view"]') || this.querySelector('a[href*="user_view"]');
            if (link) window.location = link.href;
        });
        tr.style.cursor = 'pointer';
    });

});
