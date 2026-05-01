-- CraftRadar — Миграция: прокачка админки v2
-- Запустить на продакшене

-- Заметки администратора для серверов
ALTER TABLE servers ADD COLUMN admin_note TEXT NULL AFTER reject_reason;

-- Заметки администратора для пользователей
ALTER TABLE users ADD COLUMN admin_note TEXT NULL AFTER ban_until;
