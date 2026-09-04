DOCKER := docker compose
UID := $(shell id -u)
# Runtime (artisan/tests) runs as the same user as php-fpm so storage has one owner.
EXEC := $(DOCKER) exec -u 33 app
# Source-writing tools (pint fix, phpstan cache, .env keygen) run as the host user.
HOST := $(DOCKER) exec -u $(UID) app

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
	$(MAKE) perms
	@if grep -q '^APP_KEY=$$' .env || ! grep -q '^APP_KEY=.\+' .env; then $(HOST) php artisan key:generate; fi
	@echo ""
	@echo "App:      http://betsefer.local  (https://betsefer.local via Traefik)"
	@echo "Mailpit:  http://localhost:8025"

down:
	$(DOCKER) down

perms:
	$(DOCKER) exec -u root app sh -c 'mkdir -p storage/logs storage/framework/cache/data storage/framework/sessions storage/framework/views storage/app/public/covers bootstrap/cache && touch storage/logs/laravel.log && chown -R 1000:1000 storage bootstrap/cache && chmod -R a+rwX storage bootstrap/cache' 2>/dev/null || true

fresh: perms
	$(EXEC) php artisan migrate:fresh --seed

seed: perms
	$(EXEC) php artisan db:seed

test: perms
	$(EXEC) ./vendor/bin/pest

check: perms
	$(EXEC) ./vendor/bin/pint --test
	$(HOST) ./vendor/bin/phpstan analyse --memory-limit=1G
	$(EXEC) ./vendor/bin/pest

pint:
	$(HOST) ./vendor/bin/pint

stan:
	$(HOST) ./vendor/bin/phpstan analyse --memory-limit=1G

shell:
	$(EXEC) bash

logs:
	$(DOCKER) logs -f --tail=100

a:
	$(EXEC) php artisan $(cmd)
