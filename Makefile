DOCKER := docker compose
EXEC := $(DOCKER) exec -u $(shell id -u) app
UID := $(shell id -u)

.PHONY: help up down fresh seed test check pint stan shell logs a

help:
	@echo "Targets:"
	@echo "  make up      build and start the dev stack (Traefik -> betsefer.local)"
	@echo "  make down    stop the stack"
	@echo "  make fresh   migrate:fresh --seed"
	@echo "  make seed    run the seeders"
	@echo "  make test    run Pest (inside the app container)"
	@echo "  make check   pint --test && larastan && pest"
	@echo "  make pint    style check only"
	@echo "  make stan    Larastan analysis only"
	@echo "  make shell   bash inside the app container (as www-data)"
	@echo "  make logs    follow all logs"
	@echo "  make a cmd=\"route:list\"  run any artisan command"

up:
	@if [ ! -f .env ]; then cp .env.example .env; fi
	$(DOCKER) up -d --build
	chmod -R 777 storage bootstrap/cache 2>/dev/null || true
	$(DOCKER) exec -u root app sh -c 'mkdir -p storage/logs && touch storage/logs/laravel.log && chmod 666 storage/logs/laravel.log' 2>/dev/null || true
	@if grep -q '^APP_KEY=$$' .env || ! grep -q '^APP_KEY=.\+' .env; then $(EXEC) php artisan key:generate; fi
	@echo ""
	@echo "App:      http://betsefer.local  (https://betsefer.local via Traefik)"
	@echo "Mailpit:  http://localhost:8025"

down:
	$(DOCKER) down

perms:
	$(DOCKER) exec -u root app sh -c 'mkdir -p storage/logs && touch storage/logs/laravel.log && chmod -R 777 storage/logs && chmod -R 777 bootstrap/cache' 2>/dev/null || true

fresh: perms
	$(EXEC) php artisan migrate:fresh --seed

seed: perms
	$(EXEC) php artisan db:seed

test: perms
	$(EXEC) ./vendor/bin/pest

check: perms
	$(EXEC) ./vendor/bin/pint --test
	$(EXEC) ./vendor/bin/phpstan analyse --memory-limit=1G
	$(EXEC) ./vendor/bin/pest

pint:
	$(EXEC) ./vendor/bin/pint

stan:
	$(EXEC) ./vendor/bin/phpstan analyse --memory-limit=1G

shell:
	$(EXEC) bash

logs:
	$(DOCKER) logs -f --tail=100

a:
	$(EXEC) php artisan $(cmd)
