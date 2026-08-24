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
docker compose exec php php bin/console doctrine:migrations:migrate
```

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
docker compose exec php php bin/console doctrine:database:create --env=test
docker compose exec php php bin/console doctrine:migrations:migrate --env=test
```

**Запуск тестов**:

```bash
docker compose exec php php bin/phpunit
```

**Результат**: CRUD операции продуктов покрыты feature-тестами.
