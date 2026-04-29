# CraftRadar — Cron-задачи

## Добавить в планировщик на хостинге

Замени `yourdomain.com` на свой домен.

### 3 задачи для cron:

```
# 1. Пинг серверов — каждые 10 минут
wget -qO- "https://yourdomain.com/cron/ping_servers.php?key=craftradar_cron_2026_secret" >/dev/null 2>&1

# 2. Сброс месячных голосов — 1-е число каждого месяца, 00:00
wget -qO- "https://yourdomain.com/cron/reset_monthly.php?key=craftradar_cron_2026_secret" >/dev/null 2>&1

# 3. Очистка старых записей — ежедневно в 03:00
wget -qO- "https://yourdomain.com/cron/cleanup_stats.php?key=craftradar_cron_2026_secret" >/dev/null 2>&1
```

### Таблица для панели хостинга

| № | Задача | Команда | Расписание |
|---|--------|---------|------------|
| 1 | Пинг серверов | `wget -qO- "https://yourdomain.com/cron/ping_servers.php?key=craftradar_cron_2026_secret" >/dev/null 2>&1` | Каждые 10 минут |
| 2 | Сброс голосов | `wget -qO- "https://yourdomain.com/cron/reset_monthly.php?key=craftradar_cron_2026_secret" >/dev/null 2>&1` | 1-е число месяца, 00:00 |
| 3 | Очистка | `wget -qO- "https://yourdomain.com/cron/cleanup_stats.php?key=craftradar_cron_2026_secret" >/dev/null 2>&1` | Ежедневно, 03:00 |

### Секретный ключ

Ключ задаётся в `includes/config.php` → константа `CRON_SECRET_KEY`.
По умолчанию: `craftradar_cron_2026_secret`

**Обязательно смени ключ на свой уникальный перед деплоем!**

Без правильного ключа скрипты возвращают HTTP 403.

### Логи

Логи cron-задач сохраняются в `storage/logs/`:
- `cron_ping_YYYY-MM-DD.log` — пинг серверов
- `cron_monthly_YYYY-MM.log` — сброс голосов
- `cron_cleanup_YYYY-MM-DD.log` — очистка

### Что делает каждая задача

**ping_servers.php** (каждые 10 мин)
- Пингует все active серверы по протоколу Minecraft SLP
- Обновляет онлайн, игроков, MOTD, иконку
- Записывает статистику для графиков
- После 3 неудач подряд — помечает оффлайн
- Защита от двойного запуска (lock-файл)

**reset_monthly.php** (1-е число месяца)
- Обнуляет votes_month у всех серверов
- Рейтинг строится по голосам за текущий месяц

**cleanup_stats.php** (ежедневно)
- Удаляет статистику пингов старше 30 дней
- Чистит файлы блокировки логинов
- Чистит старые cron-логи
