COMPOSE=docker compose
COMPOSE_PROD=$(COMPOSE) -f docker-compose.yaml -f docker-compose.prod.yaml

.PHONY: help up down restart build rebuild logs ps shell composer console db prod-build prod-up prod-down

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
	$(COMPOSE) exec php php bin/console

db:
	$(COMPOSE) exec postgres psql -U pizza_store -d pizza_store

prod-build:
	$(COMPOSE_PROD) build

prod-up:
	$(COMPOSE_PROD) up -d

prod-down:
	$(COMPOSE_PROD) down