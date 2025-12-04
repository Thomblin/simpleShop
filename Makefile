.PHONY: help start stop restart down status logs test test-unit test-integration test-coverage install shell db clean fresh

# Default target
.DEFAULT_GOAL := help

# Colors
BLUE := \033[0;34m
GREEN := \033[0;32m
YELLOW := \033[1;33m
NC := \033[0m

## help: Show this help message
help:
	@echo "$(BLUE)SimpleShop - Available Commands$(NC)"
	@echo ""
	@echo "$(GREEN)Container Management:$(NC)"
	@echo "  make start              - Start Docker containers"
	@echo "  make stop               - Stop Docker containers"
	@echo "  make restart            - Restart Docker containers"
	@echo "  make down               - Stop and remove containers"
	@echo "  make status             - Show container status"
	@echo "  make logs               - Show container logs"
	@echo "  make fresh              - Fresh start (rebuild everything)"
	@echo "  make clean              - Clean up all containers and volumes"
	@echo ""
	@echo "$(GREEN)Testing:$(NC)"
	@echo "  make test               - Run all tests (PHP + JS)"
	@echo "  make test-unit          - Run PHP unit tests only"
	@echo "  make test-integration   - Run PHP integration tests only"
	@echo "  make test-coverage      - Run PHP tests with coverage"
	@echo "  make test-js            - Run JavaScript unit tests"
	@echo "  make test-js-watch      - Run JavaScript tests in watch mode"
	@echo "  make test-js-coverage   - Run JavaScript tests with coverage"
	@echo ""
	@echo "$(GREEN)Development:$(NC)"
	@echo "  make install            - Install composer dependencies"
	@echo "  make shell              - Open bash shell in container"
	@echo "  make db                 - Access MySQL database"
	@echo "  make db_test            - Access MySQL test database"
	@echo ""
	@echo "$(GREEN)Quick Start:$(NC)"
	@echo "  make start && make test"
	@echo ""

## start: Start Docker containers
start:
	@./run.sh start

## stop: Stop Docker containers
stop:
	@./run.sh stop

## restart: Restart Docker containers
restart:
	@./run.sh restart

## down: Stop and remove containers
down:
	@./run.sh down

## status: Show container status
status:
	@./run.sh status

## logs: Show container logs
logs:
	@./run.sh logs

## test: Run all tests inside Docker
test:
	@./run.sh test

## test-unit: Run unit tests inside Docker
test-unit:
	@./run.sh test-unit

## test-integration: Run integration tests inside Docker
test-integration:
	@./run.sh test-integration

## test-coverage: Run tests with coverage inside Docker
test-coverage:
	@./run.sh test-coverage

## install: Install composer dependencies
install:
	@./run.sh install

## shell: Open bash shell in container
shell:
	@./run.sh shell

## db: Access MySQL database
db:
	@./run.sh db

## db_test: Access MySQL test database
db_test:
	@./run.sh db_test

## clean: Clean up all containers and volumes
clean:
	@./run.sh clean

## fresh: Fresh start (rebuild everything)
fresh:
	@./run.sh fresh

## up: Alias for start
up: start

## setup: Initial setup (start + install)
setup: start install
	@echo "$(GREEN)Setup complete! Run 'make test' to verify.$(NC)"

## test-js: Run JavaScript unit tests
test-js:
	@./run.sh test-js

## test-js-watch: Run JavaScript tests in watch mode
test-js-watch:
	@./run.sh test-js-watch

## test-js-coverage: Run JavaScript tests with coverage
test-js-coverage:
	@./run.sh test-js-coverage

## ci: Run CI pipeline (start, install, test, down)
ci: start install test down
	@echo "$(GREEN)CI pipeline complete!$(NC)"
