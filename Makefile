COMPOSE=docker compose
COMPOSE_PROD=$(COMPOSE) -f docker-compose.yaml -f docker-compose.prod.yaml

.PHONY: help up down restart build rebuild logs ps shell composer console db migrate migrate-prev migrate-test test test-db-reset test-migrations jwt-generate prod-build prod-up prod-down

help:
	@echo "Available commands:"
	@echo "  make up          Start dev environment"
	@echo "  make down        Stop dev environment"
	@echo "  make restart     Restart dev environment"
	@echo "  make build       Build dev images"
	@echo "  make rebuild     Rebuild dev images without cache"
	@echo "  make logs        Show container logs"
	@echo "  make ps          Show container status"
	@echo "  make shell       Open shell in PHP container"
	@echo "  make composer    Run composer install in PHP container"
	@echo "  make console     Run Symfony console in PHP container"
	@echo "  make db          Open PostgreSQL shell"
	@echo "  make prod-build  Build production images"
	@echo "  make prod-up     Start production environment"
	@echo "  make prod-down   Stop production environment"
	@echo "  make migrate     Run dev database migrations"
	@echo "  make migrate-prev Roll back last dev database migration"
	@echo "  make test        Prepare test database and run PHPUnit"
	@echo "  make test-db-reset Reset test database and run migrations"
	@echo "  make test-migrations Check test migrations up/down/up"
	@echo "  make jwt-generate Generate JWT keypair"

up:
	$(COMPOSE) up -d

down:
	$(COMPOSE) down

restart:
	$(COMPOSE) restart

build:
	$(COMPOSE) build

rebuild:
	$(COMPOSE) build --no-cache

logs:
	$(COMPOSE) logs -f

ps:
	$(COMPOSE) ps

shell:
	$(COMPOSE) exec php bash

composer:
	$(COMPOSE) exec php composer install

console:
	$(COMPOSE) exec php bin/console

db:
	$(COMPOSE) exec postgres psql -U pizza_store -d pizza_store

migrate:
	$(COMPOSE) exec php bin/console doctrine:migrations:migrate --no-interaction

migrate-prev:
	$(COMPOSE) exec php bin/console doctrine:migrations:migrate prev

migrate-test:
	$(COMPOSE) exec php bin/console doctrine:database:create --env=test --if-not-exists
	$(COMPOSE) exec php bin/console doctrine:migrations:migrate --env=test --no-interaction

test: up migrate-test
	$(COMPOSE) exec php bin/phpunit

test-db-reset:
	$(COMPOSE) exec php bin/console doctrine:database:drop --env=test --force --if-exists
	$(COMPOSE) exec php bin/console doctrine:database:create --env=test
	$(COMPOSE) exec php bin/console doctrine:migrations:migrate --env=test --no-interaction

test-migrations:
	$(COMPOSE) exec php bin/console doctrine:database:drop --env=test --force --if-exists
	$(COMPOSE) exec php bin/console doctrine:database:create --env=test
	$(COMPOSE) exec php bin/console doctrine:migrations:migrate --env=test --no-interaction
	$(COMPOSE) exec php bin/console doctrine:migrations:migrate prev --env=test --no-interaction
	$(COMPOSE) exec php bin/console doctrine:migrations:migrate --env=test --no-interaction

jwt-generate:
	$(COMPOSE) exec php bin/console lexik:jwt:generate-keypair

prod-build:
	$(COMPOSE_PROD) build

prod-up:
	$(COMPOSE_PROD) up -d

prod-down:
	$(COMPOSE_PROD) down
