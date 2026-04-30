# 📋 Лог №11 — UX-улучшения: уведомления, поиск, виджеты, графики

**Дата:** 30 апреля 2026

---

## Что сделано

### 9.1 Система уведомлений
- Таблица `notifications` в БД
- `includes/notifications.php` — createNotification, getUnread, markRead, markAllRead, icons
- `dashboard/notifications.php` — страница со списком, пометка прочитанных, ссылки
- 🔔 Колокольчик с бейджем в header сайта

### 9.2 Живой AJAX-поиск
- `data-live-search` атрибут на поиске главной
- Автодополнение в `app.js` — debounce 300ms, dropdown с результатами
- Показывает: иконку, название, IP, статус, онлайн, голоса
- Закрытие по Escape и клику вне

### 9.3 Баннеры/виджеты
- `api/widget.php` — два формата:
  - JS: `<script src="...?id=1"></script>` — вставляет HTML-баннер
  - HTML: `<iframe src="...?id=1&format=html">` — iframe-виджет
- Блок «Виджет для сайта» на странице сервера (для владельца)
- Кэширование 2 мин, CORS-заголовки

### 9.4 Переключение графиков
- `api/server_chart.php` — API для данных графика (24ч/7д/30д)
- Табы на странице сервера — переключение без перезагрузки
- 24ч: все точки, 7д: усреднение по часу, 30д: усреднение по дню

### Тесты
- `UxImprovementsTest.php` (~25 тестов): уведомления, поиск, виджеты, графики

---

## Затронутые файлы

| Файл | Действие |
|------|----------|
| `install/database.sql` | Обновлён (таблица notifications) |
| `includes/notifications.php` | Создан |
| `includes/header.php` | Обновлён (колокольчик) |
| `dashboard/notifications.php` | Создан |
| `assets/js/app.js` | Обновлён (живой поиск) |
| `assets/css/style.css` | Обновлён (dropdown, bell, tabs) |
| `index.php` | Обновлён (data-live-search) |
| `api/widget.php` | Создан |
| `api/server_chart.php` | Создан |
| `server.php` | Обновлён (табы графика, виджет) |
| `.kiro/PLAN.md` | Обновлён |
| `tests/Unit/UxImprovementsTest.php` | Создан |
