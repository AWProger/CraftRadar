# 📋 CraftRadar — План разработки

> Детальный план на основе TZ.md. Чекбоксы отмечаются по мере выполнения.

---

## Этап 1 — Фундамент
- [x] 1.1 Создать структуру папок проекта
- [x] 1.2 `includes/config.php` — настройки БД, константы, пути
- [x] 1.3 `includes/db.php` — подключение к БД через PDO
- [x] 1.4 `includes/functions.php` — общие вспомогательные функции (CSRF, sanitize, redirect, и т.д.)
- [x] 1.5 `install/database.sql` — SQL создания всех таблиц (users, servers, votes, server_stats, categories, reviews, reports, admin_log, settings, pages)
- [x] 1.6 `includes/auth.php` — функции авторизации (login, register, check session, logout)
- [x] 1.7 `register.php` — страница регистрации
- [x] 1.8 `login.php` — страница авторизации
- [x] 1.9 `logout.php` — выход
- [x] 1.10 `includes/header.php` + `includes/footer.php` — базовый layout
- [x] 1.11 `assets/css/style.css` — основные стили (тёмная тема, адаптив)
- [x] 1.12 `assets/js/app.js` — базовый JS (копирование IP, уведомления, AJAX)

## Этап 2 — Серверы
- [x] 2.1 `includes/minecraft_ping.php` — класс Minecraft Server List Ping
- [x] 2.2 `dashboard/add.php` — форма добавления сервера + валидация
- [x] 2.3 `dashboard/index.php` — личный кабинет (список моих серверов)
- [x] 2.4 `cron/ping_servers.php` — cron-скрипт пинга серверов
- [x] 2.5 `server.php` — страница сервера (описание, статус, график)
- [x] 2.6 `servers.php` — каталог серверов с пагинацией
- [x] 2.7 `index.php` — главная страница (топы, статистика)

## Этап 3 — Рейтинг и взаимодействие
- [x] 3.1 `vote.php` — система голосования (AJAX)
- [x] 3.2 `review.php` — система отзывов (AJAX)
- [x] 3.3 `report.php` — система жалоб (AJAX)
- [x] 3.4 Сортировка и фильтры в каталоге
- [x] 3.5 График онлайна на странице сервера (Chart.js)
- [x] 3.6 `cron/reset_monthly.php` — сброс месячных голосов
- [x] 3.7 `cron/cleanup_stats.php` — очистка старых записей

## Этап 4 — Личный кабинет
- [ ] 4.1 `dashboard/edit.php` — редактирование сервера
- [ ] 4.2 `dashboard/stats.php` — статистика сервера (графики)
- [ ] 4.3 `dashboard/settings.php` — настройки профиля
- [ ] 4.4 Уведомления в кабинете

## Этап 5 — Админ-панель
- [ ] 5.1 `admin/includes/admin_header.php` + `admin_footer.php` + `admin_auth.php` — layout и проверка прав
- [ ] 5.2 `admin/index.php` — дашборд со статистикой
- [ ] 5.3 `admin/servers.php` + `admin/server_view.php` — управление серверами
- [ ] 5.4 `admin/users.php` + `admin/user_view.php` — управление пользователями
- [ ] 5.5 `admin/reports.php` + `admin/report_view.php` — управление жалобами
- [ ] 5.6 `admin/reviews.php` — управление отзывами
- [ ] 5.7 `admin/categories.php` — управление категориями
- [ ] 5.8 `admin/settings.php` — настройки платформы
- [ ] 5.9 `admin/pages.php` + `admin/page_edit.php` — статические страницы
- [ ] 5.10 `admin/log.php` — лог действий
- [ ] 5.11 `assets/css/admin.css` + `assets/js/admin.js` — стили и JS админки

## Этап 6 — Полировка
- [ ] 6.1 `api/search.php` — AJAX-поиск
- [ ] 6.2 `api/server_status.php` — JSON API для виджетов
- [ ] 6.3 Адаптив под мобильные (доработка CSS)
- [ ] 6.4 Файловый кэш для главной и каталога
- [ ] 6.5 SEO (title, description, OpenGraph)
- [ ] 6.6 `page.php` — статические страницы
- [ ] 6.7 Страница 404
