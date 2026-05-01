-- CraftRadar — Исправление эмодзи (конвертация в utf8mb4)
-- Запустить на продакшене через phpMyAdmin → SQL

-- Конвертируем ВСЕ таблицы в utf8mb4
ALTER TABLE categories CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE servers CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE users CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE votes CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE reviews CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE reports CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE pages CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE settings CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE payments CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE admin_log CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE server_stats CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE notifications CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE point_transactions CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE favorites CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE achievements CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE user_achievements CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
ALTER TABLE referral_rewards CONVERT TO CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Конвертируем саму базу данных
ALTER DATABASE u181799_craftradar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Перезаписываем иконки категорий (могли сохраниться как ?)
UPDATE categories SET icon = '🔥' WHERE slug = 'anarchy';
UPDATE categories SET icon = '🌿' WHERE slug = 'vanilla';
UPDATE categories SET icon = '⚔️' WHERE slug = 'survival';
UPDATE categories SET icon = '🎨' WHERE slug = 'creative';
UPDATE categories SET icon = '🎮' WHERE slug = 'minigames';
UPDATE categories SET icon = '🗡️' WHERE slug = 'rpg';
UPDATE categories SET icon = '☁️' WHERE slug = 'skyblock';
UPDATE categories SET icon = '🏹' WHERE slug = 'pvp';
UPDATE categories SET icon = '🏰' WHERE slug = 'towny';
UPDATE categories SET icon = '⚙️' WHERE slug = 'tech';
