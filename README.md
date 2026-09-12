# Pizza Store API

Backend API для интернет-магазина пиццы и напитков.

## Контекст проекта

**Домен**: backend для интернет-магазина пиццы и напитков.

**Стек**: Symfony или Laravel, PostgreSQL, RabbitMQ, Redis, Docker.

**Цель**: отработать построение масштабируемого API и продемонстрировать инженерную культуру.

## Бизнес-видение

**Пользователи и роли**: гость, покупатель, администратор. Гость просматривает каталог, покупатель управляет корзиной и оформляет заказы, администратор модерирует контент и управляет заказами.

**Регистрация**: имя, телефон и email обязательны. После успешной регистрации пользователю отправляется приветственное SMS (через заглушку).

**Каталог**: на старте две категории ― пицца и напитки. Продукты имеют базовые атрибуты (название, описание, цена, вес, категория) и расширяемые характеристики (КБЖУ, острота, признак вегетарианского блюда и т. п.). Важно заложить гибкость, позволяющую добавлять новые свойства без миграции схемы каждые две недели.

**Корзина**: одна корзина на пользователя, максимум 10 пицц и 20 напитков одновременно. Неавторизованный пользователь видит только каталог.

**Оформление заказа**: для оформления требуется минимум один товар в корзине, выбор способа доставки (самовывоз или курьер) и заполненный адрес для курьерской доставки. Адрес включает: область, город, улица, дом, подъезд, квартира, индекс. В заказе не может быть более 20 позиций. Статусы заказа: `created`, `paid`, `in_progress`, `delivering`, `completed`, `cancelled`. Обновление статуса доступно администратору и внешней системе, которая отправляет событие.

**Уведомления**: при создании заказа и каждом изменении статуса пользователю отправляется email-уведомление (через заглушку).

**Отчётность**: ежедневный scheduler инициирует генерацию отчёта о продажах, присваивает отчёту идентификатор и после завершения публикует событие `report.completed` в RabbitMQ. Готовый отчёт сохраняется в локальный `.jsonl` файл; каждая строка отражает факт продажи (товар повторяется столько раз, сколько был куплен).

Пример строки отчёта:

```jsonl
{"product_name": "Маргарита", "price": 500, "amount":1, "user": {"id":1}}
{"product_name": "Пепперони", "price": 650, "amount":1, "user": {"id":2}}
```

## Стек текущей реализации

- PHP 8.5
- Symfony 8.1
- PostgreSQL 17
- Nginx 1.29
- Docker Compose
- Makefile

## Структура проекта

```text
.
├── app/                         # Symfony-приложение
├── docker/
│   ├── nginx/
│   │   └── conf.d/default.conf   # конфигурация Nginx
│   └── php/
│       ├── Dockerfile            # multi-stage образ PHP-FPM
│       └── conf/                 # конфигурация PHP, FPM и Xdebug
├── docker-compose.yaml           # Docker Compose для разработки
├── docker-compose.prod.yaml      # production override для Docker Compose
├── .env                          # infrastructure env с дефолтными значениями
├── Makefile
└── README.md
```

## Переменные окружения

В проекте разделены настройки приложения и инфраструктуры.

Infrastructure-настройки хранятся в корне проекта:

```text
.env
.env.local
```

Application-настройки Symfony хранятся внутри приложения:

```text
app/.env
app/.env.dev
```

Файл `.env` содержит дефолтные значения для Docker Compose и коммитится в репозиторий. Docker Compose читает его автоматически.

Если нужно переопределить локальные значения, например занятый порт, можно создать `.env.local`:

```bash
cp .env .env.local
```

Файл `.env.local` добавлен в `.gitignore` и не коммитится.

Docker Compose автоматически читает только корневой `.env`. Если нужно запустить окружение с локальными переопределениями из `.env.local`, передайте файл явно:

```bash
docker compose --env-file .env --env-file .env.local up -d
```

HTTP-порт по умолчанию:

```dotenv
HTTP_PORT=8080
```

Если порт занят, его можно изменить в `.env.local`.

PostgreSQL-порт по умолчанию:

```dotenv
POSTGRES_PORT=5432
```

Если на host-машине уже запущен локальный PostgreSQL, можно переопределить порт в `.env.local`, например:

```dotenv
POSTGRES_PORT=5433
```

## Запуск

Запустить dev-окружение:

```bash
make up
```

Собрать образы вручную:

```bash
make build
```

Пересобрать образы без cache:

```bash
make rebuild
```

Проверить запущенные контейнеры:

```bash
make ps
```

Открыть приложение:

```text
http://localhost:8080
```

Проверить health endpoint:

```bash
curl http://localhost:8080/health
```

Ожидаемый ответ:

```json
{"status":"ok"}
```

Остановить окружение:

```bash
make down
```

## Полезные команды

Открыть shell в PHP-контейнере:

```bash
make shell
```

Установить Composer-зависимости внутри PHP-контейнера:

```bash
make composer
```

Запустить Symfony console:

```bash
make console
```

Выполнить миграции Doctrine:

```bash
make migrate
```

Откатить последнюю миграцию Doctrine:

```bash
make migrate-prev
```

Запустить тесты:

```bash
make test
```

Полностью пересоздать тестовую БД и накатить миграции:

```bash
make test-db-reset
```

Проверить миграции на чистой тестовой БД вперёд/назад/вперёд:

```bash
make test-migrations
```

Сгенерировать JWT keypair после чистого развёртывания:

```bash
make jwt-generate
```

Загрузить фикстуры для ручной проверки API:

```bash
docker compose exec php bin/console doctrine:fixtures:load
```

Обязательные application-переменные описаны в `app/.env`:

```dotenv
JWT_SECRET_KEY=%kernel.project_dir%/config/jwt/private.pem
JWT_PUBLIC_KEY=%kernel.project_dir%/config/jwt/public.pem
JWT_PASSPHRASE=
JWT_TOKEN_TTL=3600
```

`JWT_TOKEN_TTL` задаётся в секундах и используется и в конфиге JWT, и в ответе login как `expiresIn`.

Открыть PostgreSQL shell:

```bash
make db
```

Посмотреть логи:

```bash
make logs
```

## Production build

Собрать production-образ:

```bash
make prod-build
```

Запустить production-окружение:

```bash
make prod-up
```

Остановить production-окружение:

```bash
make prod-down
```

В production-режиме Symfony-приложение копируется внутрь PHP-образа на этапе сборки. Локальный исходный код не монтируется как volume.

## Реализованные этапы

### Этап 1: Docker окружение

**Цель**: подготовить изолированное Docker-окружение для Symfony backend-приложения.

**Реализовано**:

- Инициализирован Symfony-проект.
- Создан Docker Compose с сервисами:
  - PHP-FPM;
  - PostgreSQL;
  - Nginx.
- Настроен multi-stage PHP image для dev и prod окружений.
- Код приложения вынесен в отдельную директорию `app/`.
- Разделены infrastructure и application env-файлы.
- Порты, проброшенные на host-машину, вынесены в env-переменные.
- Для Docker-образов указаны версии.
- Для PostgreSQL настроен healthcheck.
- Настроены зависимости сервисов друг от друга.
- Настроены volumes для кода и данных БД.
- Приложение доступно через браузер.
- Добавлен Makefile с основными командами.
- PHP-конфигурация вынесена в отдельные файлы:
  - `php.ini`;
  - `xdebug.ini`;
  - `php-fpm.conf`;
  - `www.conf`.
- Для установки PHP extensions используется `mlocati/docker-php-extension-installer`.
- Composer-зависимости устанавливаются по-разному для dev и prod образов.
- Добавлен `docker-compose.prod.yaml` с production override.

**Результат**: Symfony-приложение доступно по адресу:

```text
http://localhost:8080
```

### Этап 2: CRUD продуктов

**Цель**: реализовать RESTful API для управления продуктами каталога.

**Архитектура**: lightweight `clean/ddd/package-by-feature`. Код продукта сгруппирован внутри feature-пакета `Product`:

```text
app/src/Product/
├── Domain/
├── Infrastructure/
└── Presentation/
```

**Реализовано**:

- Создана сущность `Product` с полями `name`, `description`, `price`, `weight`, `category`.
- Добавлена Doctrine migration для таблицы `products`.
- Реализован CRUD API с пагинацией списка продуктов.
- Добавлена валидация входящих данных через request DTO и `MapRequestPayload`.
- Для HTTP-статусов используются константы `Response::HTTP_*`.

**Endpoints**:

```text
GET    /products?page=1&limit=10
GET    /products/{id}
POST   /products
PATCH  /products/{id}
DELETE /products/{id}
```

**Результат**: работает CRUD API для продуктов с миграцией БД, пагинацией и валидацией входящих данных.

### Этап 3: Тестирование

**Цель**: покрыть CRUD API продуктов функциональными тестами.

**Реализовано**:

- Установлен PHPUnit для Symfony-приложения.
- Настроено тестовое окружение `APP_ENV=test`.
- Настроена отдельная тестовая БД `pizza_store_test`.
- Для подготовки тестовых данных используются Doctrine fixtures.
- Добавлены feature-тесты для CRUD endpoint'ов продуктов:
  - success и error case для списка продуктов;
  - success и error case для просмотра продукта;
  - success и error case для создания продукта;
  - success и error case для обновления продукта;
  - success и error case для удаления продукта.

**Команды для тестовой БД**:

```bash
make migrate-test
```

**Запуск тестов**:

```bash
make test
```

**Результат**: CRUD операции продуктов покрыты feature-тестами.

### Этап 4: Авторизация и ролевая модель

**Цель**: реализовать регистрацию, JWT login и разграничение прав доступа к API продуктов.

**Реализовано**:

- Создана сущность `User` с полями `name`, `phone`, `email`, `password`, `roles`.
- Добавлена Doctrine migration для таблицы `users`.
- Реализованы роли guest, user и admin через `PUBLIC_ACCESS`, `ROLE_USER` и `ROLE_ADMIN`.
- Подключён `lexik/jwt-authentication-bundle`.
- Реализованы регистрация, login и получение текущего пользователя.
- Регистрация и login валидируются через request DTO.
- Выпуск JWT и проверка credentials вынесены в сервис.
- После успешной регистрации вызывается SMS-заглушка.
- Добавлен единый JSON-формат ошибок API без stack trace.
- Роуты продуктов защищены по ролям:
  - `GET` доступен всем;
  - `POST`, `PATCH`, `DELETE` доступны только admin.
- Добавлены feature-тесты для регистрации, login, JWT и матрицы прав guest/user/admin.

**Auth endpoints**:

```text
POST /auth/register
POST /auth/login
GET  /auth/me
```

**Пример регистрации**:

```bash
curl -X POST http://localhost:8080/auth/register \
  -H 'Content-Type: application/json' \
  -d '{"name":"New User","phone":"+79990000003","email":"new-user@example.com","password":"password123"}'
```

**Пример login**:

```bash
curl -X POST http://localhost:8080/auth/login \
  -H 'Content-Type: application/json' \
  -d '{"email":"admin@example.com","password":"admin123"}'
```

Успешный login возвращает access token:

```json
{
  "accessToken": "...",
  "tokenType": "Bearer",
  "expiresIn": 3600
}
```

**Пример запроса текущего пользователя**:

```bash
curl http://localhost:8080/auth/me \
  -H 'Authorization: Bearer <accessToken>'
```

**Проверка миграций**:

```bash
make test-migrations
```

**Запуск тестов**:

```bash
make test
```

**Результат**: защищённый API с регистрацией, JWT login, `GET /auth/me`, безопасными JSON-ошибками и разграничением прав доступа.

### Этап 5: Качество кода и CI/CD

**Цель**: автоматизировать проверку качества кода и настроить GitLab CI/CD pipeline для проверки каждого push и Merge Request.

**Реализовано**:

- Установлен и настроен PHP CS Fixer.
- Добавлена конфигурация PHP CS Fixer в `app/.php-cs-fixer.dist.php`.
- Установлен и настроен PHPStan level 6.
- Для корректного статического анализа Doctrine entities подключён `phpstan/phpstan-doctrine`.
- Добавлена конфигурация PHPStan в `app/phpstan.dist.neon`.
- Установлен и настроен Rector в dry-run режиме.
- Добавлена конфигурация Rector в `app/rector.php`.
- Добавлены Composer scripts для локального запуска проверок из директории `app/`.
- Добавлены Make-команды для короткого запуска проверок из корня проекта.
- Добавлен GitLab CI/CD pipeline в `.gitlab-ci.yml`.
- Pipeline разделён на stages:
  - `build` — сборка Docker image из `docker/php/Dockerfile` с production target `prod`;
  - `code-quality` — запуск тестов, PHP CS Fixer, PHPStan и Rector.
- Quality jobs запускаются из директории `app`, где находится `composer.json`.
- JWT keypair для тестов генерируется внутри CI и не хранится в репозитории.
- Кеши quality-инструментов складываются в `app/var/` и игнорируются Git.

**Локальные команды проверки**:

```bash
make test
make cs-check
make stan
make rector-check
```

Запустить все проверки одной командой:

```bash
make quality
```

**Composer-команды внутри приложения**:

```bash
docker compose exec php composer --working-dir=/var/www/app test
docker compose exec php composer --working-dir=/var/www/app cs-check
docker compose exec php composer --working-dir=/var/www/app stan
docker compose exec php composer --working-dir=/var/www/app rector-check
```

**CI/CD**:

GitLab pipeline запускается на каждый push и Merge Request. Падение любого quality job блокирует merge.

**Проверено локально**:

```bash
make test
make cs-check
make stan
make rector-check
```

**Результат**: проект имеет зафиксированные конфиги PHP CS Fixer, PHPStan и Rector, локальные команды качества и GitLab CI/CD pipeline для автоматической проверки кода.

### Этап 6: Корзина и заказы

**Цель**: реализовать корзину пользователя, оформление заказов и бизнес-инварианты домена.

**Реализовано**:

- Созданы сущности `Cart`, `CartItem`, `Order` и `OrderItem`.
- Корзина принадлежит конкретному авторизованному пользователю.
- Пользователь может читать и изменять только свою корзину и свои заказы.
- В доменной модели централизованы лимиты корзины:
  - максимум 10 пицц;
  - максимум 20 напитков;
  - максимум 20 позиций при оформлении заказа.
- Реализованы операции корзины:
  - получение текущей корзины;
  - установка количества товара;
  - удаление товара из корзины;
  - очистка корзины.
- Реализованы операции заказов:
  - список заказов пользователя с пагинацией;
  - просмотр заказа пользователя;
  - оформление заказа из корзины;
  - изменение статуса заказа.
- Поддержаны способы получения заказа `pickup` и `courier`.
- Для курьерской доставки адрес обязателен и представлен value object'ом `DeliveryAddress`.
- Адрес доставки валидируется по полям: область, город, улица, дом, подъезд, квартира, индекс.
- Заказ хранит snapshot товара: product id, название, цену за единицу и количество.
- Статусы заказа ограничены enum'ом: `created`, `paid`, `in_progress`, `delivering`, `completed`, `cancelled`.
- Переходы статусов явно заданы в доменной модели заказа.
- Изменение статуса заказа доступно только пользователю с ролью `ROLE_ADMIN`.
- Добавлена email-заглушка уведомлений при создании заказа и изменении статуса.
- Создание корзины, изменение количества, очистка корзины и оформление заказа выполняются атомарно в транзакциях.
- Для конкурентных изменений корзины используется pessimistic lock, чтобы не было lost update и выхода за лимиты при параллельных запросах.
- Параллельное создание первой корзины обрабатывается без необработанного unique constraint violation.
- Некорректные пользовательские сценарии возвращают JSON-ошибки вместо HTML/stack trace.
- Добавлены feature-тесты, включая конкурентные сценарии корзины.

**Cart endpoints**:

```text
GET    /cart
PATCH  /cart/items/{productId}
DELETE /cart/items/{productId}
DELETE /cart
```

**Пример обновления количества товара в корзине**:

```bash
curl -X PATCH http://localhost:8080/cart/items/1 \
  -H 'Authorization: Bearer <accessToken>' \
  -H 'Content-Type: application/json' \
  -d '{"quantity":2}'
```

**Orders endpoints**:

```text
GET   /orders?page=1&limit=10
GET   /orders/{id}
POST  /orders
PATCH /orders/{id}/status
```

**Пример оформления заказа самовывозом**:

```bash
curl -X POST http://localhost:8080/orders \
  -H 'Authorization: Bearer <accessToken>' \
  -H 'Content-Type: application/json' \
  -d '{"deliveryType":"pickup"}'
```

**Пример оформления заказа курьером**:

```bash
curl -X POST http://localhost:8080/orders \
  -H 'Authorization: Bearer <accessToken>' \
  -H 'Content-Type: application/json' \
  -d '{
    "deliveryType": "courier",
    "deliveryAddress": {
      "region": "Московская область",
      "city": "Москва",
      "street": "Тверская",
      "house": "1",
      "entrance": "2",
      "apartment": "10",
      "postalCode": "125009"
    }
  }'
```

**Пример изменения статуса заказа**:

```bash
curl -X PATCH http://localhost:8080/orders/1/status \
  -H 'Authorization: Bearer <accessToken>' \
  -H 'Content-Type: application/json' \
  -d '{"status":"paid"}'
```

**Проверено локально**:

```bash
make test
make cs-check
make stan
make test-migrations
```

**Результат**: реализованы корзина, оформление заказов, доменные ограничения, атомарность конкурентных операций и feature-тесты для основных success/error/concurrency сценариев.
