# 📋 Лог №8 — ЮMoney платежи + улучшение админки

**Дата:** 30 апреля 2026

---

## Что сделано

### Этап 7 — ЮMoney платежи ✅

1. **Таблица `payments`** в database.sql — id, user_id, server_id, amount, type (promote_7d/14d/30d), status, label, yoomoney_operation_id, paid_at
2. **`includes/yoomoney.php`** — класс YooMoney:
   - `createPaymentForm()` — генерация формы для quickpay
   - `verifyNotification()` — проверка SHA-1 хэша уведомления
   - `parseNotification()` — извлечение данных
   - `createPayment()` — создание платежа в БД + форма
   - `processPaymentNotification()` — обработка webhook, активация продвижения
   - `paymentLog()` — логирование в storage/logs/payments_*.log
3. **Настройки** в config.php: YOOMONEY_WALLET, YOOMONEY_SECRET, URL-ы, тарифы (99/179/299 ₽)
4. **`dashboard/promote.php`** — выбор тарифа (7/14/30 дней), карточки с ценами, авторедирект на ЮMoney
5. **`payment/notify.php`** — webhook от ЮMoney (проверка хэша, codepro, суммы, активация promote)
6. **`payment/success.php`** + **`payment/fail.php`** — страницы после оплаты
7. **`admin/payments.php`** — таблица платежей, фильтры, статистика доходов, график за 30 дней
8. **Кнопка «⭐ Продвинуть»** в dashboard/index.php
9. **Ссылка «💰 Платежи»** в навигации админки

### Этап 8 — Улучшение админки ✅

1. **4 графика в админ-дашборде** (Chart.js): регистрации, серверы, голоса, доходы — за 30 дней
2. **Блок доходов** в дашборде: сегодня, за месяц, всего
3. **Экспорт лога в CSV** — кнопка в admin/log.php, BOM для Excel, разделитель ;

### Тесты

- **`YooMoneyTest.php`** (~25 тестов): создание, форма оплаты, верификация хэша (валидный/невалидный/пустой), парсинг, константы, цены, файлы, SQL

---

## Затронутые файлы

| Файл | Действие |
|------|----------|
| `includes/config.php` | Обновлён (ЮMoney настройки) |
| `includes/yoomoney.php` | Создан |
| `install/database.sql` | Обновлён (таблица payments) |
| `dashboard/promote.php` | Создан |
| `dashboard/index.php` | Обновлён (кнопка Продвинуть) |
| `payment/notify.php` | Создан |
| `payment/success.php` | Создан |
| `payment/fail.php` | Создан |
| `admin/payments.php` | Создан |
| `admin/includes/admin_header.php` | Обновлён (ссылка Платежи) |
| `admin/index.php` | Обновлён (доходы, 4 графика) |
| `admin/log.php` | Обновлён (экспорт CSV) |
| `.kiro/PLAN.md` | Обновлён |
| `tests/Unit/YooMoneyTest.php` | Создан |

---

## Настройка ЮMoney

1. Зайти в https://yoomoney.ru → Настройки → HTTP-уведомления
2. Указать URL: `https://craftradar.ru/payment/notify.php`
3. Скопировать секрет уведомлений
4. В `includes/config.php` заполнить:
   - `YOOMONEY_WALLET` — номер кошелька
   - `YOOMONEY_SECRET` — секрет для проверки уведомлений
