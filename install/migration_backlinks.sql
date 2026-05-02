-- CraftRadar — Миграция: система обратных ссылок + новая логика голосов
-- Запустить на продакшене

-- Обратные ссылки
ALTER TABLE servers ADD COLUMN has_backlink TINYINT(1) NOT NULL DEFAULT 0 AFTER highlighted_until;
ALTER TABLE servers ADD COLUMN backlink_checked_at DATETIME NULL AFTER has_backlink;

-- Пересчёт votes_total из реальных данных (на всякий случай)
UPDATE servers s SET votes_total = (
    SELECT COUNT(*) FROM votes v WHERE v.server_id = s.id
);
