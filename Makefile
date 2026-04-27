# Library Management System - Docker helpers
# Run `make` or `make help` to list the targets.

SHELL := /bin/bash
.DEFAULT_GOAL := help

export HOST_UID := $(shell id -u)
export HOST_GID := $(shell id -g)

DEV      := docker compose -f compose.yml
PROD     := docker compose -f compose.prod.yml --env-file .env.production
EXEC     := $(DEV) exec app
RUN      := $(DEV) run --rm --no-deps app
PROD_ENV := .env.production

cmd ?=

.PHONY: help
help: ## Энэ тусламжийг харуулна
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z0-9_-]+:.*?## / {printf "  \033[36m%-15s\033[0m %s\n", $$1, $$2} /^## / {printf "\n\033[1m%s\033[0m\n", substr($$0, 4)}' $(MAKEFILE_LIST)

## ---- Анх удаа (dev) -------------------------------------------------------

.PHONY: init
init: ## Dev орчныг анх удаа бэлдэнэ: build, composer/npm install, key, migrate+seed
	@test -f .env || cp .env.example .env
	$(DEV) build
	$(RUN) composer install
	$(RUN) npm install
	@grep -q '^APP_KEY=base64' .env || $(RUN) php artisan key:generate
	$(DEV) up -d
	$(EXEC) php artisan migrate --seed
	@echo ""
	@echo "Бэлэн: http://localhost:$${APP_PORT:-8080}  (admin@lms.local / password)"

## ---- Dev ---------------------------------------------------------------------

.PHONY: dev
dev: ## Dev стекийг асаана (app, nginx, vite, mysql)
	$(DEV) up -d --build
	@echo "App:  http://localhost:$${APP_PORT:-8080}"
	@echo "Vite: http://localhost:$${VITE_PORT:-5173}"

.PHONY: stop
stop: ## Dev контейнеруудыг зогсооно (өгөгдөл хадгалагдана)
	$(DEV) stop

.PHONY: down
down: ## Dev контейнеруудыг устгана (DB volume хадгалагдана)
	$(DEV) down

.PHONY: destroy
destroy: ## Dev контейнер + DB volume-ийг бүрэн устгана
	@read -p "Dev өгөгдлийн санг бүрэн устгах уу? [y/N] " ans && [ "$$ans" = y ]
	$(DEV) down -v

.PHONY: ps
ps: ## Dev контейнеруудын төлөв
	$(DEV) ps

.PHONY: logs
logs: ## Dev логийг дагаж харна
	$(DEV) logs -f --tail=100

.PHONY: shell
shell: ## app контейнер руу bash-аар орно
	$(EXEC) bash

.PHONY: artisan
artisan: ## php artisan <cmd>     жишээ: make artisan cmd="route:list"
	$(EXEC) php artisan $(cmd)

.PHONY: composer
composer: ## composer <cmd>        жишээ: make composer cmd="require foo/bar"
	$(EXEC) composer $(cmd)

.PHONY: npm
npm: ## npm <cmd>             жишээ: make npm cmd="install dayjs"
	$(EXEC) npm $(cmd)

.PHONY: tinker
tinker: ## Laravel tinker
	$(EXEC) php artisan tinker

.PHONY: migrate
migrate: ## Шинэ migration-уудыг ажиллуулна
	$(EXEC) php artisan migrate

.PHONY: fresh
fresh: ## DB-г цэвэрлээд migrate + demo өгөгдөл (dev only)
	$(EXEC) php artisan migrate:fresh --seed

.PHONY: test
test: ## PHPUnit тестүүд (lms_test DB дээр)
	$(EXEC) php artisan config:clear --ansi
	$(EXEC) php artisan test $(cmd)

.PHONY: lint
lint: ## PHP (Pint) + JS/Vue (vp check) форматыг засна
	$(EXEC) composer lint
	$(EXEC) npm run check:fix

.PHONY: check
check: ## Lint + type check + тест (CI-тэй адил)
	$(EXEC) composer lint:check
	$(EXEC) composer types:check
	$(EXEC) npm run check
	$(EXEC) npm run types:check
	$(MAKE) test

.PHONY: build-assets
build-assets: ## Front-end-ийг production горимоор build хийнэ (public/build)
	$(EXEC) npm run build

## ---- Production ----------------------------------------------------------------

.PHONY: prod-env
prod-env:
	@test -f $(PROD_ENV) || { echo "$(PROD_ENV) байхгүй. Эхлээд: cp .env.production.example $(PROD_ENV)"; exit 1; }

.PHONY: prod-key
prod-key: prod-env ## .env.production-д APP_KEY үүсгэнэ (хоосон бол)
	@if grep -q '^APP_KEY=base64' $(PROD_ENV); then \
		echo "APP_KEY аль хэдийн тохируулагдсан байна."; \
	else \
		KEY="base64:$$(openssl rand -base64 32)"; \
		sed "s|^APP_KEY=.*|APP_KEY=$$KEY|" $(PROD_ENV) > $(PROD_ENV).tmp && mv $(PROD_ENV).tmp $(PROD_ENV); \
		echo "APP_KEY үүслээ."; \
	fi

.PHONY: prod-build
prod-build: prod-env ## Prod image-үүдийг build хийнэ (app + nginx)
	$(PROD) build --pull

.PHONY: prod-up
prod-up: prod-env ## Prod стекийг асаана
	$(PROD) up -d --remove-orphans

.PHONY: prod-migrate
prod-migrate: prod-env ## Prod DB дээр migration ажиллуулна
	$(PROD) run --rm app php artisan migrate --force

.PHONY: prod-seed
prod-seed: prod-env ## Анхны номын санч + үндсэн ангиллуудыг үүсгэнэ (нэг удаа)
	$(PROD) run --rm app php artisan db:seed --class=ProductionSeeder --force

.PHONY: prod-deploy
prod-deploy: prod-env ## build -> migrate -> up (git pull-ийн дараа энэ нэг команд)
	$(PROD) build --pull
	$(PROD) up -d mysql --wait
	$(PROD) run --rm app php artisan migrate --force
	$(PROD) up -d --remove-orphans --wait
	$(PROD) ps

.PHONY: prod-down
prod-down: prod-env ## Prod контейнеруудыг зогсоож устгана (volume хадгалагдана)
	$(PROD) down

.PHONY: prod-restart
prod-restart: prod-env ## Prod app + nginx-ийг дахин асаана
	$(PROD) restart app nginx

.PHONY: prod-ps
prod-ps: prod-env ## Prod контейнеруудын төлөв
	$(PROD) ps

.PHONY: prod-logs
prod-logs: prod-env ## Prod логийг дагаж харна
	$(PROD) logs -f --tail=200

.PHONY: prod-shell
prod-shell: prod-env ## Prod app контейнер руу sh-аар орно
	$(PROD) exec app sh

.PHONY: prod-artisan
prod-artisan: prod-env ## Prod дээр php artisan <cmd>
	$(PROD) exec app php artisan $(cmd)

.PHONY: prod-backup
prod-backup: prod-env ## Prod DB-ийн нөөц -> backups/lms-YYYYmmdd-HHMMSS.sql.gz
	@mkdir -p backups
	@FILE=backups/lms-$$(date +%Y%m%d-%H%M%S).sql.gz; \
	$(PROD) exec -T mysql sh -c 'exec mysqldump --single-transaction --routines --no-tablespaces -uroot -p"$$MYSQL_ROOT_PASSWORD" "$$MYSQL_DATABASE"' | gzip > $$FILE \
		&& echo "Нөөц: $$FILE"

.PHONY: prod-restore
prod-restore: prod-env ## Нөөцөөс сэргээнэ:  make prod-restore file=backups/xxx.sql.gz
	@test -n "$(file)" && test -f "$(file)" || { echo "file= заана уу"; exit 1; }
	@read -p "Prod DB-г $(file)-аас сэргээх үү? Одоогийн өгөгдөл дарагдана. [y/N] " ans && [ "$$ans" = y ]
	gunzip -c $(file) | $(PROD) exec -T mysql sh -c 'exec mysql -uroot -p"$$MYSQL_ROOT_PASSWORD" "$$MYSQL_DATABASE"'
