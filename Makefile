up-rebuild:
	docker compose up -d --build --remove-orphans
up:
	docker compose up -d

down:
	docker compose down

down-clean:
	docker compose down -v

bash:
	docker compose exec php bash

composer:
	docker compose exec php composer $(cmd)

workers:
	docker compose exec php php bin/console messenger:consume async_events -vv

workers-background:
	docker compose exec -d php php bin/console messenger:consume async_events -vv

worker-stop:
	docker compose exec php php bin/console messenger:stop-workers

messenger-stats:
	docker compose exec php php bin/console messenger:stats

messenger-failed:
	docker compose exec php php bin/console messenger:failed:show

messenger-retry:
	docker compose exec php php bin/console messenger:failed:retry

messenger-debug:
	docker compose exec php php bin/console debug:messenger

cache-clear:
	docker compose exec php php bin/console cache:clear

# PHP CS Fixer commands
.PHONY: cs-check cs-fix
csc: ## Check code style without fixing
	@echo "Checking code style..."
	@composer cs-check

csf: ## Fix code style automatically
	@echo "Fixing code style..."
	@composer cs-fix
	@echo "Code style fixed!"