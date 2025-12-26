.PHONY: help setup install start stop restart logs shell db-shell test cs-check cs-fix analyze phpunit build clean quality qa

# Default target
.DEFAULT_GOAL := help

# Colors
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
NC := \033[0m # No Color

help: ## Show this help message
	@echo "$(BLUE)Available Commands$(NC)"
	@echo "=========================================="
	@grep -E '^[a-zA-Z_-]+:.*?## .*$$' $(MAKEFILE_LIST) | sort | awk 'BEGIN {FS = ":.*?## "}; {printf "  $(GREEN)%-15s$(NC) %s\n", $$1, $$2}'
	@echo ""

setup: ## Initial project setup (run once)
	@./setup.sh

install: ## Install/update dependencies
	@echo "$(BLUE)Installing dependencies...$(NC)"
	@docker run --rm -v "$$(pwd)/backend:/app" -w /app composer:latest install
	@echo "$(GREEN)Dependencies installed$(NC)"

start: ## Start development environment
	@./scripts/dev.sh

stop: ## Stop development environment
	@echo "$(BLUE)Stopping containers...$(NC)"
	@docker-compose down
	@echo "$(GREEN)Containers stopped$(NC)"

restart: stop start ## Restart development environment

logs: ## Show container logs (use logs-f for follow mode)
	@docker-compose logs

logs-f: ## Follow container logs
	@docker-compose logs -f

shell: ## Open bash shell in backend container
	@docker-compose exec backend bash

db-shell: ## Open MySQL shell in database container
	@docker-compose exec database mysql -u $${DB_USER:-app_user} -p$${DB_PASSWORD:-app_password} $${DB_NAME:-app_db}

test: ## Run all tests and quality checks
	@./scripts/test.sh

cs-check: ## Check code style (PHP_CodeSniffer)
	@echo "$(BLUE)Running code style check...$(NC)"
	@docker-compose exec backend composer cs-check

cs-fix: ## Fix code style automatically
	@echo "$(BLUE)Fixing code style...$(NC)"
	@docker-compose exec backend composer cs-fix
	@echo "$(GREEN)Code style fixed$(NC)"

analyze: ## Run static analysis (PHPStan)
	@echo "$(BLUE)Running static analysis...$(NC)"
	@docker-compose exec backend composer analyze

phpunit: ## Run PHPUnit tests
	@echo "$(BLUE)Running PHPUnit tests...$(NC)"
	@docker-compose exec backend composer test

quality: ## Run all quality checks (CS + PHPStan)
	@echo "$(BLUE)Running all quality checks...$(NC)"
	@$(MAKE) cs-check
	@$(MAKE) analyze
	@echo "$(GREEN)All quality checks passed$(NC)"

qa: quality ## Alias for quality

build: ## Build production Docker image
	@./scripts/build.sh

clean: ## Clean up containers, volumes, and cache
	@echo "$(YELLOW)Cleaning up...$(NC)"
	@docker-compose down -v
	@rm -rf backend/data/cache/*
	@echo "$(GREEN)Cleanup complete$(NC)"

deploy-prod: ## Deploy production environment
	@echo "$(BLUE)Deploying production environment...$(NC)"
	@docker-compose -f docker-compose.prod.yml up -d
	@echo "$(GREEN)Production environment deployed$(NC)"

stop-prod: ## Stop production environment
	@echo "$(BLUE)Stopping production containers...$(NC)"
	@docker-compose -f docker-compose.prod.yml down
	@echo "$(GREEN)Production containers stopped$(NC)"
