# 📋 Лог №36 — Навигация кабинета, топ игроков на главной, рефералы в админке

**Дата:** 1 мая 2026

---

## Сделано (5 доработок)

1. **dashboardNav() на всех страницах кабинета** — единая навигация (Профиль, Серверы, Баллы, Монеты, Уведомления, Настройки) на 6 страницах
2. **Ссылка «Топ игроков»** на главной в quick-links
3. **Статистика рефералов** в админ-дашборде
4. **Баллы, монеты, реф. код** в карточке пользователя админки
5. **Убрано дублирование** кнопок навигации в профиле и кабинете

---

## Затронутые файлы

| Файл | Изменение |
|------|-----------|
| `dashboard/index.php` | dashboardNav('servers'), убраны дубли |
| `dashboard/profile.php` | dashboardNav('profile'), убраны дубли |
| `dashboard/points.php` | dashboardNav('points') |
| `dashboard/notifications.php` | dashboardNav('notif') |
| `dashboard/settings.php` | dashboardNav('settings') |
| `dashboard/buy_coins.php` | dashboardNav('coins') |
| `index.php` | Ссылка «Топ игроков» |
| `admin/index.php` | Статистика рефералов |
| `admin/user_view.php` | Баллы, монеты, реф. код |
