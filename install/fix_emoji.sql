-- CraftRadar — Исправление эмодзи (конвертация в utf8mb4)
-- Запустить на продакшене через phpMyAdmin → SQL
-- ВАЖНО: выполнять ВЕСЬ файл целиком!

-- 1. Конвертируем базу данных
ALTER DATABASE u181799_craftradar CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- 2. Конвертируем ВСЕ таблицы в utf8mb4
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

-- 3. Перезаписываем иконки категорий (сохранились как ?)
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

-- 4. Перезаписываем иконки достижений (могли сохраниться как ?)
UPDATE achievements SET icon = '👍' WHERE slug = 'first_vote';
UPDATE achievements SET icon = '🗳️' WHERE slug = 'voter_10';
UPDATE achievements SET icon = '📢' WHERE slug = 'voter_50';
UPDATE achievements SET icon = '🏆' WHERE slug = 'voter_100';
UPDATE achievements SET icon = '💬' WHERE slug = 'first_review';
UPDATE achievements SET icon = '📝' WHERE slug = 'reviewer_5';
UPDATE achievements SET icon = '📡' WHERE slug = 'first_server';
UPDATE achievements SET icon = '🔐' WHERE slug = 'verified_owner';
UPDATE achievements SET icon = '📅' WHERE slug = 'daily_3';
UPDATE achievements SET icon = '🔥' WHERE slug = 'daily_7';
UPDATE achievements SET icon = '⭐' WHERE slug = 'daily_30';
UPDATE achievements SET icon = '💰' WHERE slug = 'points_100';
UPDATE achievements SET icon = '❤️' WHERE slug = 'favorite_5';

-- 5. Добавляем admin_note если ещё нет
ALTER TABLE servers ADD COLUMN admin_note TEXT NULL;
ALTER TABLE users ADD COLUMN admin_note TEXT NULL;
