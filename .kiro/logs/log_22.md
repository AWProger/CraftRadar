# 📋 Лог №22 — Пользовательские интеграции

**Дата:** 1 мая 2026

---

## Что сделано

### Новые таблицы БД
- `favorites` — избранные серверы (user_id, server_id)
- `achievements` — список достижений (slug, name, icon, points_reward)
- `user_achievements` — полученные достижения (user_id, achievement_slug)
- Поля в `users`: `minecraft_nick`, `daily_streak`, `last_daily_visit`

### Новые файлы
- `includes/achievements.php` — модуль достижений и избранного
- `api/favorite.php` — AJAX API для добавления/удаления из избранного
- `dashboard/profile.php` — страница профиля (ник, достижения, избранное, статистика)

### Интеграции
1. **Избранное** — кнопка ❤️ на странице сервера, AJAX toggle, список в профиле
2. **Minecraft ник** — сохраняется в профиле, автоподставляется при голосовании
3. **13 достижений** — first_vote, voter_10/50, first_review, first_server, verified_owner, daily_3/7/30, favorite_5 и др.
4. **Ежедневные визиты** — трекинг streak (дней подряд), достижения за 3/7/30 дней
5. **Автопроверка достижений** — при голосовании, отзыве, добавлении сервера, верификации
6. **Уведомления о достижениях** — при получении + начисление баллов
7. **Профиль** — сводка (баллы, голоса, отзывы, streak, достижения), сетка достижений, избранные серверы

---

## Затронутые файлы

| Файл | Изменение |
|------|-----------|
| `install/database.sql` | 3 новые таблицы + ALTER users |
| `includes/db.php` | SQLite seed обновлён |
| `includes/achievements.php` | Создан |
| `includes/header.php` | Трекинг визитов |
| `api/favorite.php` | Создан |
| `dashboard/profile.php` | Создан |
| `dashboard/index.php` | Ссылка на профиль |
| `server.php` | Кнопка избранного + автоник |
| `vote.php` | Сохранение ника + достижения |
| `review.php` | Достижения при отзыве |

---

## SQL для хостинга (выполнить в phpMyAdmin)

```sql
CREATE TABLE IF NOT EXISTS favorites (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, server_id INT NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (server_id) REFERENCES servers(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_user_server (user_id, server_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    slug VARCHAR(50) NOT NULL UNIQUE, name VARCHAR(100) NOT NULL,
    description VARCHAR(255) NOT NULL, icon VARCHAR(10) NOT NULL,
    points_reward INT NOT NULL DEFAULT 0, sort_order INT NOT NULL DEFAULT 0
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS user_achievements (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL, achievement_slug VARCHAR(50) NOT NULL,
    earned_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    UNIQUE INDEX idx_user_achievement (user_id, achievement_slug)
) ENGINE=InnoDB;

INSERT INTO achievements (slug, name, description, icon, points_reward, sort_order) VALUES
('first_vote', 'Первый голос', 'Проголосовал за сервер впервые', '👍', 5, 1),
('voter_10', 'Активный избиратель', 'Проголосовал 10 раз', '🗳️', 10, 2),
('voter_50', 'Голос народа', 'Проголосовал 50 раз', '📢', 25, 3),
('voter_100', 'Легенда голосований', 'Проголосовал 100 раз', '🏆', 50, 4),
('first_review', 'Критик', 'Оставил первый отзыв', '💬', 5, 5),
('reviewer_5', 'Обозреватель', 'Оставил 5 отзывов', '📝', 15, 6),
('first_server', 'Владелец', 'Добавил свой первый сервер', '📡', 10, 7),
('verified_owner', 'Подтверждённый', 'Подтвердил владение сервером', '🔐', 20, 8),
('daily_3', 'Постоянный', 'Заходил 3 дня подряд', '📅', 5, 9),
('daily_7', 'Недельный марафон', 'Заходил 7 дней подряд', '🔥', 15, 10),
('daily_30', 'Месячный марафон', 'Заходил 30 дней подряд', '⭐', 50, 11),
('points_100', 'Копилка', 'Накопил 100 баллов', '💎', 0, 12),
('favorite_5', 'Коллекционер', 'Добавил 5 серверов в избранное', '❤️', 5, 13);

ALTER TABLE users ADD COLUMN minecraft_nick VARCHAR(32) NULL AFTER email;
ALTER TABLE users ADD COLUMN daily_streak INT NOT NULL DEFAULT 0;
ALTER TABLE users ADD COLUMN last_daily_visit DATE NULL;
```
