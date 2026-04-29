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
- [x] 4.1 `dashboard/edit.php` — редактирование сервера
- [x] 4.2 `dashboard/stats.php` — статистика сервера (графики)
- [x] 4.3 `dashboard/settings.php` — настройки профиля
- [x] 4.4 Уведомления в кабинете

## Этап 5 — Админ-панель
- [x] 5.1 `admin/includes/admin_header.php` + `admin_footer.php` + `admin_auth.php` — layout и проверка прав
- [x] 5.2 `admin/index.php` — дашборд со статистикой
- [x] 5.3 `admin/servers.php` + `admin/server_view.php` — управление серверами
- [x] 5.4 `admin/users.php` + `admin/user_view.php` — управление пользователями
- [x] 5.5 `admin/reports.php` + `admin/report_view.php` — управление жалобами
- [x] 5.6 `admin/reviews.php` — управление отзывами
- [x] 5.7 `admin/categories.php` — управление категориями
- [x] 5.8 `admin/settings.php` — настройки платформы
- [x] 5.9 `admin/pages.php` + `admin/page_edit.php` — статические страницы
- [x] 5.10 `admin/log.php` — лог действий
- [x] 5.11 `assets/css/admin.css` + `assets/js/admin.js` — стили и JS админки

## Этап 6 — Полировка
- [x] 6.1 `api/search.php` — AJAX-поиск
- [x] 6.2 `api/server_status.php` — JSON API для виджетов
- [x] 6.3 Адаптив под мобильные (доработка CSS)
- [ ] 6.4 Файловый кэш для главной и каталога
- [x] 6.5 SEO (title, description, OpenGraph)
- [x] 6.6 `page.php` — статические страницы
- [x] 6.7 Страница 404
