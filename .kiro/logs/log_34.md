# 📋 Лог №34 — Реферальная программа + логика владения

**Дата:** 1 мая 2026

---

## Что сделано

### Реферальная программа
- Таблица `referral_rewards` в БД
- Поля `referral_code`, `referred_by`, `referral_count` в users
- `includes/referrals.php` — генерация кода, обработка регистрации, статистика
- Константы: REFERRAL_REWARD_REGISTER (3💎), REFERRAL_REWARD_REFERRED (2💎), REFERRAL_REWARD_FIRST_VOTE (1💎)
- Интеграция в register.php — скрытое поле ref_code, бонус при регистрации
- Блок в профиле — ссылка, статистика (приглашено, заработано)
- Уведомление рефереру при регистрации

### Логика владения (подтверждено что работает)
- ✅ При верификации user_id меняется на подтвердившего
- ✅ IP нельзя менять (только удалить и добавить заново → верификация сбрасывается)
- ✅ Серверы на модерации видны в каталоге с бейджем

---

## SQL для хостинга

```sql
ALTER TABLE users ADD COLUMN referral_code VARCHAR(10) UNIQUE NULL;
ALTER TABLE users ADD COLUMN referred_by INT NULL;
ALTER TABLE users ADD COLUMN referral_count INT NOT NULL DEFAULT 0;

CREATE TABLE IF NOT EXISTS referral_rewards (
    id INT AUTO_INCREMENT PRIMARY KEY,
    referrer_id INT NOT NULL, referred_id INT NOT NULL,
    reward_type VARCHAR(20) NOT NULL DEFAULT 'registration',
    points_reward INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (referrer_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (referred_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX idx_referrer (referrer_id)
) ENGINE=InnoDB;
```
