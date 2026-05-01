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

// === Hero Particles ===
(function() {
    var container = document.getElementById('heroParticles');
    if (!container) return;

    for (var i = 0; i < 30; i++) {
        var particle = document.createElement('div');
        particle.className = 'hero-particle';
        particle.style.left = Math.random() * 100 + '%';
        particle.style.animationDuration = (3 + Math.random() * 5) + 's';
        particle.style.animationDelay = Math.random() * 5 + 's';
        particle.style.width = (2 + Math.random() * 4) + 'px';
        particle.style.height = particle.style.width;
        particle.style.opacity = (0.1 + Math.random() * 0.3).toString();

        // Разные цвета — как частицы Minecraft
        var colors = ['#00ff80', '#5ce1e6', '#ffd700', '#ff6b81', '#70a1ff'];
        particle.style.background = colors[Math.floor(Math.random() * colors.length)];

        container.appendChild(particle);
    }
})();

// === Sidebar Top Servers Carousel ===
(function() {
    const carousel = document.getElementById('sidebarCarousel');
    const track = document.getElementById('sidebarTrack');
    const dotsContainer = document.getElementById('sidebarDots');
    const prevBtn = document.getElementById('sidebarPrev');
    const nextBtn = document.getElementById('sidebarNext');

    if (!carousel || !track) return;

    const VISIBLE_COUNT = 5; // Сколько серверов видно одновременно
    const SLIDE_INTERVAL = parseInt(document.body.dataset.sidebarInterval || '5000');
    const items = track.querySelectorAll('.sidebar-server');
    const totalItems = items.length;
    const totalPages = Math.ceil(totalItems / VISIBLE_COUNT);
    let currentPage = 0;
    let autoSlideTimer = null;

    // Рассчитываем высоту одного элемента
    function getItemHeight() {
        if (items.length === 0) return 56;
        return items[0].offsetHeight;
    }

    // Создаём точки
    function createDots() {
        if (!dotsContainer || totalPages <= 1) return;
        dotsContainer.innerHTML = '';
        for (let i = 0; i < totalPages; i++) {
            const dot = document.createElement('span');
            dot.className = 'sidebar-dot' + (i === 0 ? ' active' : '');
            dot.addEventListener('click', function() { goToPage(i); });
            dotsContainer.appendChild(dot);
        }
    }

    // Переход к странице
    function goToPage(page) {
        currentPage = page;
        if (currentPage >= totalPages) currentPage = 0;
        if (currentPage < 0) currentPage = totalPages - 1;

        const offset = currentPage * VISIBLE_COUNT * getItemHeight();
        track.style.transform = 'translateY(-' + offset + 'px)';

        // Обновляем точки
        if (dotsContainer) {
            dotsContainer.querySelectorAll('.sidebar-dot').forEach(function(dot, i) {
                dot.classList.toggle('active', i === currentPage);
            });
        }

        resetAutoSlide();
    }

    // Автопрокрутка
    function startAutoSlide() {
        if (totalPages <= 1) return;
        autoSlideTimer = setInterval(function() {
            goToPage(currentPage + 1);
        }, SLIDE_INTERVAL);
    }

    function resetAutoSlide() {
        clearInterval(autoSlideTimer);
        startAutoSlide();
    }

    // Кнопки
    if (prevBtn) prevBtn.addEventListener('click', function() { goToPage(currentPage - 1); });
    if (nextBtn) nextBtn.addEventListener('click', function() { goToPage(currentPage + 1); });

    // Пауза при наведении
    carousel.addEventListener('mouseenter', function() { clearInterval(autoSlideTimer); });
    carousel.addEventListener('mouseleave', function() { startAutoSlide(); });

    // Установка высоты карусели
    function setCarouselHeight() {
        var h = getItemHeight() * Math.min(VISIBLE_COUNT, totalItems);
        carousel.style.height = h + 'px';
    }

    // Инициализация
    createDots();
    setCarouselHeight();
    startAutoSlide();

    // Обновление данных через API
    function refreshSidebarData() {
        var apiUrl = (document.body.dataset.siteUrl || '') + '/api/top_servers.php?limit=' + totalItems;
        fetch(apiUrl)
            .then(function(r) { return r.json(); })
            .then(function(data) {
                if (!data.servers) return;
                data.servers.forEach(function(srv, i) {
                    if (!items[i]) return;
                    var nameEl = items[i].querySelector('.sidebar-server-name');
                    var motdEl = items[i].querySelector('.sidebar-server-motd');
                    var statsEl = items[i].querySelector('.sidebar-server-stats');
                    if (nameEl) {
                        nameEl.textContent = (srv.is_promoted ? '⭐' : '') + (srv.is_verified ? '✓' : '') + srv.name;
                    }
                    if (motdEl) motdEl.textContent = srv.motd || srv.ip;
                    if (statsEl) {
                        statsEl.innerHTML = srv.is_online
                            ? '<span class="sidebar-players">👥 ' + srv.players_online + '/' + srv.players_max + '</span><span class="sidebar-votes">👍 ' + srv.votes_month + '</span>'
                            : '<span class="sidebar-offline">● Оффлайн</span><span class="sidebar-votes">👍 ' + srv.votes_month + '</span>';
                    }
                });
            })
            .catch(function() {}); // Тихо игнорируем ошибки
    }

    // Обновляем данные каждые 2 минуты
    setInterval(refreshSidebarData, 120000);
})();

// === Toggle password visibility ===
(function() {
    document.querySelectorAll('input[type="password"]').forEach(function(input) {
        var wrapper = document.createElement('div');
        wrapper.style.position = 'relative';
        input.parentNode.insertBefore(wrapper, input);
        wrapper.appendChild(input);

        var toggle = document.createElement('button');
        toggle.type = 'button';
        toggle.textContent = '👁';
        toggle.style.cssText = 'position:absolute;right:10px;top:50%;transform:translateY(-50%);background:none;border:none;cursor:pointer;font-size:1rem;opacity:0.5;padding:4px;';
        toggle.addEventListener('click', function() {
            if (input.type === 'password') {
                input.type = 'text';
                toggle.style.opacity = '1';
            } else {
                input.type = 'password';
                toggle.style.opacity = '0.5';
            }
        });
        wrapper.appendChild(toggle);
        input.style.paddingRight = '40px';
    });
})();

// === Animated counters ===
(function() {
    function animateCounter(el) {
        var target = parseInt(el.textContent.replace(/\D/g, '')) || 0;
        if (target === 0) return;
        var duration = 1000;
        var start = 0;
        var startTime = null;

        function step(timestamp) {
            if (!startTime) startTime = timestamp;
            var progress = Math.min((timestamp - startTime) / duration, 1);
            var current = Math.floor(progress * target);
            el.textContent = current.toLocaleString('ru');
            if (progress < 1) requestAnimationFrame(step);
            else el.textContent = target.toLocaleString('ru');
        }

        var observer = new IntersectionObserver(function(entries) {
            if (entries[0].isIntersecting) {
                requestAnimationFrame(step);
                observer.disconnect();
            }
        });
        observer.observe(el);
    }

    document.querySelectorAll('.home-stat-value, .stat-card-value').forEach(animateCounter);
})();

// === Scroll to top button ===
(function() {
    var btn = document.createElement('button');
    btn.innerHTML = '▲';
    btn.className = 'scroll-top-btn';
    btn.style.cssText = 'position:fixed;bottom:20px;right:20px;width:40px;height:40px;background:var(--bg-card);border:3px solid var(--border);color:var(--accent);font-size:1rem;cursor:pointer;z-index:80;display:none;align-items:center;justify-content:center;box-shadow:4px 4px 0 rgba(0,0,0,0.5);transition:all 0.15s;';
    btn.addEventListener('click', function() { window.scrollTo({top: 0, behavior: 'smooth'}); });
    btn.addEventListener('mouseenter', function() { this.style.borderColor = 'var(--accent)'; });
    btn.addEventListener('mouseleave', function() { this.style.borderColor = 'var(--border)'; });
    document.body.appendChild(btn);

    window.addEventListener('scroll', function() {
        btn.style.display = window.scrollY > 300 ? 'flex' : 'none';
    });
})();
