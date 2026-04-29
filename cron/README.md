# CraftRadar — Cron-задачи

## Настройка на хостинге

Замените `/path/to/CraftRadar` на реальный путь к проекту на сервере.
Замените `/usr/bin/php` на путь к PHP (узнать: `which php`).

### Добавление в crontab

```bash
crontab -e
```

Вставьте эти строки:

```cron
# === CraftRadar Cron Jobs ===

# Пинг серверов — каждые 10 минут
*/10 * * * *  /usr/bin/php /path/to/CraftRadar/cron/ping_servers.php >> /dev/null 2>&1

# Сброс месячных голосов — 1-го числа каждого месяца в 00:00
0 0 1 * *     /usr/bin/php /path/to/CraftRadar/cron/reset_monthly.php >> /dev/null 2>&1

# Очистка старых записей — ежедневно в 03:00
0 3 * * *     /usr/bin/php /path/to/CraftRadar/cron/cleanup_stats.php >> /dev/null 2>&1
```

### Для панелей хостинга (ISPmanager, cPanel, и т.д.)

Если хостинг не даёт доступ к crontab напрямую, добавьте задачи через панель:

| Задача | Команда | Расписание |
|--------|---------|------------|
| Пинг серверов | `/usr/bin/php /path/to/CraftRadar/cron/ping_servers.php` | Каждые 10 минут |
| Сброс голосов | `/usr/bin/php /path/to/CraftRadar/cron/reset_monthly.php` | 1-е число месяца, 00:00 |
| Очистка | `/usr/bin/php /path/to/CraftRadar/cron/cleanup_stats.php` | Ежедневно, 03:00 |

### Проверка

Логи cron-задач сохраняются в `storage/logs/`:
- `cron_ping_YYYY-MM-DD.log` — логи пинга
- `cron_monthly_YYYY-MM.log` — логи сброса голосов
- `cron_cleanup_YYYY-MM-DD.log` — логи очистки

Ручной запуск для проверки:
```bash
php /path/to/CraftRadar/cron/ping_servers.php
php /path/to/CraftRadar/cron/reset_monthly.php
php /path/to/CraftRadar/cron/cleanup_stats.php
```

## Описание задач

### ping_servers.php (каждые 10 минут)
- Пингует все серверы со статусом `active`
- Обновляет онлайн, игроков, MOTD, иконку
- Записывает статистику в `server_stats`
- После 3 неудачных пингов подряд — помечает сервер оффлайн
- Защита от двойного запуска (lock-файл)

### reset_monthly.php (1-е число месяца)
- Обнуляет `votes_month` у всех серверов
- Рейтинг строится по голосам за текущий месяц

### cleanup_stats.php (ежедневно)
- Удаляет записи `server_stats` старше 30 дней
- Чистит файлы блокировки логинов (старше 1 дня)
- Чистит старые cron-логи (старше 30 дней)
