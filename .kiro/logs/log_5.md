# 📋 Лог №5 — .gitignore и тесты

**Дата:** 30 апреля 2026

---

## Что сделано

1. **Добавлены правила №4 и №5** в RULES.md:
   - №4: Поддерживать .gitignore
   - №5: Писать тесты под каждую часть задачи

2. **Создан `.gitignore`** — исключены: vendor, node_modules, storage, кэш, загрузки пользователей (icons/*.png, banners/*), .env, IDE файлы, PHPUnit кэш.

3. **Созданы `.gitkeep`** для `assets/img/icons/` и `assets/img/banners/`.

4. **Настроен PHPUnit:**
   - `composer.json` — зависимость phpunit/phpunit ^10.0
   - `phpunit.xml` — конфигурация (Unit + Integration suites)
   - `tests/bootstrap.php` — загрузка конфига и функций

5. **Написаны Unit-тесты (5 файлов, ~120 тестов):**
   - `FunctionsTest.php` — e(), truncate(), now(), formatDate(), isValidServerIP(), isValidPort(), CSRF, flash, isPost(), post/get/getInt, paginate
   - `MinecraftPingTest.php` — создание экземпляра, пинг несуществующего сервера, parseMotd, stripMinecraftColors, packVarInt
   - `AuthTest.php` — isLoggedIn (сессия, IP, UA), currentUserId, currentUserRole, isAdmin, isModerator, валидация registerUser (пустые поля, короткий логин, невалидные символы, email, пароль)
   - `ConfigTest.php` — проверка всех констант (БД, пути, безопасность, серверы, голосование, отзывы, загрузки), разумность значений
   - `DatabaseSchemaTest.php` — наличие 10 таблиц, ключевые поля, ENUM, индексы, FK, начальные данные, InnoDB, utf8mb4
   - `FileStructureTest.php` — существование всех файлов проекта (includes, страницы, dashboard, admin, cron, api, assets)

---

## Затронутые файлы

| Файл | Действие |
|------|----------|
| `.gitignore` | Создан |
| `.kiro/RULES.md` | Обновлён (правила 4-5) |
| `composer.json` | Создан |
| `phpunit.xml` | Создан |
| `tests/bootstrap.php` | Создан |
| `tests/Unit/FunctionsTest.php` | Создан |
| `tests/Unit/MinecraftPingTest.php` | Создан |
| `tests/Unit/AuthTest.php` | Создан |
| `tests/Unit/ConfigTest.php` | Создан |
| `tests/Unit/DatabaseSchemaTest.php` | Создан |
| `tests/Unit/FileStructureTest.php` | Создан |
| `assets/img/icons/.gitkeep` | Создан |
| `assets/img/banners/.gitkeep` | Создан |

---

## Как запустить тесты

```bash
composer install
vendor/bin/phpunit
```

---

## Следующие шаги
- Установить PHP и Composer в PATH для запуска тестов
- Добавить Integration-тесты (с тестовой БД) при необходимости
