.DEFAULT_GOAL := help
include docker.env

ifeq ($(shell docker --help | grep "compose"),)
	DOCKER_COMPOSE_ALIAS := docker-compose --env-file docker.env
else
	DOCKER_COMPOSE_ALIAS := docker compose --env-file docker.env
endif

.PHONY: up
up: ## Start the development environment
	$(DOCKER_COMPOSE_ALIAS) up -d
	@echo "Connectez-vous à l’adresse http://localhost:$(PORT_WEB)"

.PHONY: up-build
up-build: ## Start the development environment by rebuilding the Docker images
	$(DOCKER_COMPOSE_ALIAS) up -d --build

.PHONY: down
down: ## Shutdown the Docker containers
	$(DOCKER_COMPOSE_ALIAS) down

.PHONY: down-v
down-v: ## Shutdown the Docker containers AND delete the volumes (including the database)
	$(DOCKER_COMPOSE_ALIAS) down -v

.PHONY: install-dev
install-dev: ## Install the development dependencies (Composer + assets)
	$(DOCKER_COMPOSE_ALIAS) exec  web composer install
	make assets-dev

.PHONY: assets-dev
assets-dev: ## Install the assets dependencies
	$(DOCKER_COMPOSE_ALIAS) exec  web yarn install
	$(DOCKER_COMPOSE_ALIAS) restart bundler

compile-sass: ## Compile sass to css
	$(DOCKER_COMPOSE_ALIAS) exec  web sass --watch src/PostparcBundle/Resources/public/sass/styles.scss:src/PostparcBundle/Resources/public/css/styles.css

.PHONY: migration
migration: ## Migrate the database
	$(DOCKER_COMPOSE_ALIAS) exec  web php bin/console doctrine:migrations:migrate --no-interaction

.PHONY: bash
bash: ## Open a bash on web with current user
	$(DOCKER_COMPOSE_ALIAS) exec  -it web /bin/bash

.PHONY: bash-root
bash-root: ## open a bash on web with root user
	$(DOCKER_COMPOSE_ALIAS) exec -u root -it web /bin/bash

.PHONY: entity
entity: ## Create a new entity
	$(DOCKER_COMPOSE_ALIAS) exec web php bin/console make:entity

public/dev_assets:
	mkdir public/dev_assets
	@echo "Répertoire des assets créé."

.yarnrc:
	touch .yarnrc

.PHONY: help
help: ## Show this help
	@grep -hE '^[A-Za-z0-9_ \-]*?:.*##.*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}'

