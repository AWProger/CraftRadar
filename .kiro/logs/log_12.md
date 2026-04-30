# 📋 Лог №12 — Minecraft-стиль + система баллов

**Дата:** 30 апреля 2026

---

## Что сделано

### 10.1 Полный редизайн CSS — Minecraft-стиль
- Шрифт **Press Start 2P** для заголовков и акцентов
- **Блочный стиль** — border-radius: 0, pixel-border: 3px solid
- **Тени** — box-shadow: 4px 4px 0 (пиксельные тени)
- **Цвета Minecraft** — gold (#ffd700), diamond (#5ce1e6), emerald (#00ff80), redstone (#ff4757)
- **Кнопки** — градиенты, hover с translate, active с вдавливанием
- **Карточки серверов** — promoted (золотая рамка), highlighted (алмазная рамка)
- **Фон** — тёмный с радиальными градиентами
- **image-rendering: pixelated** для иконок серверов

### 10.3 Система баллов
- **Начисление**: 1 балл за каждый голос (интегрировано в vote.php)
- **Трата**: выделение сервера на 1ч (5💎), 6ч (25💎), 24ч (80💎)
- **`includes/points.php`** — getUserPoints, addPoints, rewardVotePoints, highlightServer, isHighlighted, getPointHistory
- **`dashboard/highlight.php`** — страница выделения с тарифами и историей баллов
- **Баллы в header** — 💎 рядом с именем пользователя
- **Кнопка «⚡ Выделить»** в кабинете
- **Каталог** — highlighted серверы получают алмазную рамку и приоритет в сортировке

### БД
- Поле `points` в таблице users
- Поле `highlighted_until` в таблице servers
- Таблица `point_transactions`
- Настройки баллов в settings

### Тесты
- `PointsTest.php` (~20 тестов): функции, isHighlighted, БД, интеграция с голосованием, CSS

---

## Затронутые файлы

| Файл | Действие |
|------|----------|
| `assets/css/style.css` | Полностью переписан (Minecraft-стиль) |
| `includes/points.php` | Создан |
| `dashboard/highlight.php` | Создан |
| `install/database.sql` | Обновлён (points, highlighted_until, point_transactions) |
| `includes/header.php` | Обновлён (баллы) |
| `vote.php` | Обновлён (начисление баллов) |
| `dashboard/index.php` | Обновлён (кнопка Выделить) |
| `servers.php` | Обновлён (highlighted класс, сортировка) |
| `.kiro/PLAN.md` | Обновлён |
| `tests/Unit/PointsTest.php` | Создан |
