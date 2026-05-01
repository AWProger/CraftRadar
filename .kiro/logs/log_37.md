# 📋 Лог №37 — Навигация, хлебные крошки, удаление дублей CSS

**Дата:** 1 мая 2026

---

## Сделано

### 1. dashboardNav() добавлен на все оставшиеся страницы
- `dashboard/add.php` → `dashboardNav('servers')`
- `dashboard/edit.php` → `dashboardNav('servers')`
- `dashboard/stats.php` → `dashboardNav('servers')`
- `dashboard/highlight.php` → `dashboardNav('servers')`
- `dashboard/promote.php` → `dashboardNav('servers')`
- `dashboard/verify.php` → `dashboardNav('servers')`

### 2. breadcrumbs() добавлен на ВСЕ 12 страниц кабинета
- `dashboard/index.php` — Главная › Кабинет
- `dashboard/profile.php` — Главная › Кабинет › Профиль
- `dashboard/add.php` — Главная › Кабинет › Добавить сервер
- `dashboard/edit.php` — Главная › Кабинет › Редактировать
- `dashboard/stats.php` — Главная › Кабинет › Статистика: {имя}
- `dashboard/highlight.php` — Главная › Кабинет › Выделить сервер
- `dashboard/promote.php` — Главная › Кабинет › Продвижение
- `dashboard/verify.php` — Главная › Кабинет › Верификация
- `dashboard/points.php` — Главная › Кабинет › Баллы
- `dashboard/buy_coins.php` — Главная › Кабинет › Купить монеты
- `dashboard/notifications.php` — Главная › Кабинет › Уведомления
- `dashboard/settings.php` — Главная › Кабинет › Настройки

### 3. Удалены дублирующие кнопки «← Назад»
Убраны из 7 файлов: add, edit, stats, highlight, promote, verify, settings
Также убраны «← Кабинет» из points, notifications и «← Профиль» из buy_coins

### 4. Удалены дублирующие inline `<style>` блоки
- `.form-row` — удалён из `add.php` и `edit.php` (уже в style.css)
- `.stat-card`, `.stats-grid`, `.dashboard-header` — удалены из `index.php` и `stats.php`

### 5. Добавлены CSS-классы в основной style.css
- `.dashboard-header` — flex layout для заголовков
- `.stats-grid` — grid для карточек статистики
- `.stat-card`, `.stat-card-value`, `.stat-card-label` — стилизация в Minecraft-теме
- `.sort-tabs`, `.sort-tab` — табы переключения периодов
- Мобильная адаптация для всех новых классов

---

## Затронутые файлы (15)

| Файл | Изменение |
|------|-----------|
| `dashboard/add.php` | +dashboardNav, +breadcrumbs, -← Назад, -inline .form-row |
| `dashboard/edit.php` | +dashboardNav, +breadcrumbs, -← Назад, -inline .form-row |
| `dashboard/stats.php` | +dashboardNav, +breadcrumbs, -← Назад, -inline .stat-card/.stats-grid/.sort-tabs |
| `dashboard/highlight.php` | +dashboardNav, +breadcrumbs, -← Назад |
| `dashboard/promote.php` | +dashboardNav, +breadcrumbs, -← Назад |
| `dashboard/verify.php` | +dashboardNav, +breadcrumbs, -← Назад |
| `dashboard/settings.php` | +breadcrumbs, -← Назад |
| `dashboard/index.php` | +breadcrumbs, -inline .stat-card/.stats-grid/.dashboard-header |
| `dashboard/profile.php` | +breadcrumbs |
| `dashboard/points.php` | +breadcrumbs, -← Кабинет |
| `dashboard/buy_coins.php` | +breadcrumbs, -← Профиль |
| `dashboard/notifications.php` | +breadcrumbs, -← Кабинет |
| `assets/css/style.css` | +.dashboard-header, +.stats-grid, +.stat-card, +.sort-tabs, +mobile |
| `includes/components.php` | без изменений (уже было) |

---

## Итог задачи №7 (Навигация и хлебные крошки)

✅ `dashboardNav()` — на всех 12 страницах кабинета
✅ `breadcrumbs()` — на всех 12 страницах кабинета
✅ Кнопки «← Назад» — удалены из всех 10 файлов
✅ Дублирующий inline CSS — удалён из 5 файлов
✅ CSS перенесён в основной style.css
✅ Git commit + push

---

## Что дальше
- Проверить работу на продакшене (craftradar.ru)
- Продолжить по IMPROVEMENTS.md
