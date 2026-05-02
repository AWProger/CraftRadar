-- Пересчёт votes_month из реальных данных таблицы votes
UPDATE servers s SET votes_month = (
    SELECT COUNT(*) FROM votes v 
    WHERE v.server_id = s.id 
    AND MONTH(v.voted_at) = MONTH(NOW()) 
    AND YEAR(v.voted_at) = YEAR(NOW())
);
