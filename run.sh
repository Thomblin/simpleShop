#!/bin/bash

# SimpleShop Task Runner
# Manages Docker containers and runs tests

set -e  # Exit on error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Container names
SHOP_CONTAINER="simpleshop-shop-1"
MYSQL_CONTAINER="simpleshop-shop_mysql-1"

# Helper functions
print_header() {
    echo -e "${BLUE}========================================${NC}"
    echo -e "${BLUE}$1${NC}"
    echo -e "${BLUE}========================================${NC}"
}

print_success() {
    echo -e "${GREEN}✓ $1${NC}"
}

print_error() {
    echo -e "${RED}✗ $1${NC}"
}

print_warning() {
    echo -e "${YELLOW}⚠ $1${NC}"
}

print_info() {
    echo -e "${BLUE}ℹ $1${NC}"
}

# Check if Docker is running
check_docker() {
    if ! docker info > /dev/null 2>&1; then
        print_error "Docker is not running. Please start Docker first."
        exit 1
    fi
}

# Start containers
start() {
    print_header "Starting Docker Containers"
    check_docker

    docker compose up -d

    print_info "Waiting for MySQL to be healthy..."
    sleep 2

    # Wait for MySQL to be ready
    local max_attempts=30
    local attempt=0

    while [ $attempt -lt $max_attempts ]; do
        if docker compose exec -T shop_mysql mysqladmin ping -h 127.0.0.1 -uroot -proot > /dev/null 2>&1; then
            print_success "MySQL is ready!"
            break
        fi

        attempt=$((attempt + 1))
        echo -n "."
        sleep 1
    done

    if [ $attempt -eq $max_attempts ]; then
        print_error "MySQL failed to start within the timeout period"
        exit 1
    fi

    print_success "All containers started successfully"
    docker compose ps
}

# Stop containers
stop() {
    print_header "Stopping Docker Containers"
    docker compose stop
    print_success "Containers stopped"
}

# Restart containers
restart() {
    print_header "Restarting Docker Containers"
    stop
    start
}

# Stop and remove containers
down() {
    print_header "Stopping and Removing Containers"
    docker compose down
    print_success "Containers removed"
}

# Show container status
status() {
    print_header "Container Status"
    docker compose ps
}

# Show logs
logs() {
    print_header "Container Logs"
    if [ -n "$1" ]; then
        docker compose logs -f "$1"
    else
        docker compose logs -f
    fi
}

# Run tests inside the container
test() {
    print_header "Running Tests Inside Container"

    # Check if containers are running
    if ! docker compose ps | grep -q "Up"; then
        print_warning "Containers are not running. Starting them first..."
        start
    fi

    # Ensure test_mysql is running
    if ! docker compose ps test_mysql | grep -q "Up"; then
        print_info "Starting test database..."
        docker compose up -d test_mysql
        
        # Wait for test database to be ready
        print_info "Waiting for test database to be ready..."
        local max_attempts=30
        local attempt=0
        
        while [ $attempt -lt $max_attempts ]; do
            if docker compose exec -T test_mysql mysqladmin ping -h 127.0.0.1 -uroot -ptestroot > /dev/null 2>&1; then
                print_success "Test database is ready!"
                break
            fi
            
            attempt=$((attempt + 1))
            echo -n "."
            sleep 1
        done
        
        if [ $attempt -eq $max_attempts ]; then
            print_error "Test database failed to start within the timeout period"
            exit 1
        fi
    fi

    # Install dependencies if needed
    if ! docker compose exec -T shop test -d vendor; then
        print_info "Installing dependencies..."
        docker compose exec -T shop composer install
    fi

    # Run tests
    if [ -n "$1" ]; then
        print_info "Running: $@"
        docker compose exec -T shop vendor/bin/phpunit "$@"
    else
        docker compose exec -T shop vendor/bin/phpunit --testdox
    fi
}

# Run tests with coverage
test_coverage() {
    print_header "Running Tests with Coverage"

    if ! docker compose ps | grep -q "Up"; then
        print_warning "Containers are not running. Starting them first..."
        start
    fi

    # Ensure test_mysql is running
    if ! docker compose ps test_mysql | grep -q "Up"; then
        print_info "Starting test database..."
        docker compose up -d test_mysql
        
        # Wait for test database to be ready
        print_info "Waiting for test database to be ready..."
        local max_attempts=30
        local attempt=0
        
        while [ $attempt -lt $max_attempts ]; do
            if docker compose exec -T test_mysql mysqladmin ping -h 127.0.0.1 -uroot -ptestroot > /dev/null 2>&1; then
                print_success "Test database is ready!"
                break
            fi
            
            attempt=$((attempt + 1))
            echo -n "."
            sleep 1
        done
        
        if [ $attempt -eq $max_attempts ]; then
            print_error "Test database failed to start within the timeout period"
            exit 1
        fi
    fi

    # Install dependencies if needed
    if ! docker compose exec -T shop test -d vendor; then
        print_info "Installing dependencies..."
        docker compose exec -T shop composer install
    fi

    # Run tests with coverage (text output)
    print_info "Running tests with coverage report..."
    docker compose exec -T shop vendor/bin/phpunit --coverage-text --coverage-html coverage

    if [ -d "coverage" ] && [ -f "coverage/index.html" ]; then
        print_success "Coverage report generated in coverage/ directory"
        COVERAGE_PATH=$(realpath coverage/index.html)
        print_info "Coverage report is available at: file://$COVERAGE_PATH"
        print_info "Open it in your browser with:"
        print_info "  - Linux: xdg-open coverage/index.html"
        print_info "  - macOS: open coverage/index.html"
        print_info "  - Windows: start coverage/index.html"
    fi
}

# Run specific test suite
test_unit() {
    print_header "Running Unit Tests"
    test --testsuite Unit
}

test_integration() {
    print_header "Running Integration Tests"
    test --testsuite Integration
}

# Run JavaScript tests
test_js() {
    print_header "Running JavaScript Tests in Docker Container"
    check_docker
    
    # Build the node_test container if needed
    print_info "Building node_test container..."
    docker compose build node_test
    
    # Install dependencies if node_modules doesn't exist or is empty
    print_info "Installing npm dependencies..."
    docker compose run --rm node_test sh -c "if [ ! -d node_modules ] || [ ! -f node_modules/.package-lock.json ] && [ ! -f node_modules/package-lock.json ]; then npm install; fi"
    
    # Run tests in container
    docker compose run --rm node_test npm test
}

# Run JavaScript tests in watch mode
test_js_watch() {
    print_header "Running JavaScript Tests (Watch Mode) in Docker Container"
    check_docker
    
    # Build the node_test container if needed
    print_info "Building node_test container..."
    docker compose build node_test
    
    # Install dependencies if node_modules doesn't exist or is empty
    print_info "Installing npm dependencies..."
    docker compose run --rm node_test sh -c "if [ ! -d node_modules ] || [ ! -f node_modules/.package-lock.json ] && [ ! -f node_modules/package-lock.json ]; then npm install; fi"
    
    # Run tests in watch mode (interactive)
    print_info "Starting watch mode (press Ctrl+C to stop)..."
    docker compose run --rm node_test npm run test:watch
}

# Run JavaScript tests with coverage
test_js_coverage() {
    print_header "Running JavaScript Tests with Coverage in Docker Container"
    check_docker
    
    # Build the node_test container if needed
    print_info "Building node_test container..."
    docker compose build node_test
    
    # Install dependencies if node_modules doesn't exist or is empty
    print_info "Installing npm dependencies..."
    docker compose run --rm node_test sh -c "if [ ! -d node_modules ] || [ ! -f node_modules/.package-lock.json ] && [ ! -f node_modules/package-lock.json ]; then npm install; fi"
    
    # Run tests with coverage in container
    docker compose run --rm node_test npm run test:coverage
    
    if [ -d "coverage/js" ] && [ -f "coverage/js/index.html" ]; then
        print_success "Coverage report generated in coverage/js/ directory"
        COVERAGE_PATH=$(realpath coverage/js/index.html)
        print_info "Coverage report is available at: file://$COVERAGE_PATH"
        print_info "Open it in your browser with:"
        print_info "  - Linux: xdg-open coverage/js/index.html"
        print_info "  - macOS: open coverage/js/index.html"
        print_info "  - Windows: start coverage/js/index.html"
    fi
}

# Install dependencies
install() {
    print_header "Installing Dependencies"

    if ! docker compose ps | grep -q "Up"; then
        print_warning "Containers are not running. Starting them first..."
        start
    fi

    docker compose exec -T shop composer install
    print_success "Dependencies installed"
}

# Run composer command
composer() {
    if ! docker compose ps | grep -q "Up"; then
        print_warning "Containers are not running. Starting them first..."
        start
    fi

    docker compose exec shop composer "$@"
}

# Execute shell in container
shell() {
    print_header "Opening Shell in Container"

    if ! docker compose ps | grep -q "Up"; then
        print_warning "Containers are not running. Starting them first..."
        start
    fi

    docker compose exec shop /bin/bash
}

# Run PHP command
php() {
    if ! docker compose ps | grep -q "Up"; then
        print_warning "Containers are not running. Starting them first..."
        start
    fi

    docker compose exec shop php "$@"
}

# Clean up everything
clean() {
    print_header "Cleaning Up"
    print_warning "This will remove all containers, volumes, and vendor directory"
    read -p "Are you sure? (y/N) " -n 1 -r
    echo

    if [[ $REPLY =~ ^[Yy]$ ]]; then
        docker compose down -v
        rm -rf vendor
        rm -f composer.lock
        print_success "Cleanup complete"
    else
        print_info "Cleanup cancelled"
    fi
}

# Fresh start (rebuild containers)
fresh() {
    print_header "Fresh Start (Rebuild)"
    docker compose down -v
    docker compose build --no-cache
    start
    install
    print_success "Fresh start complete"
}

# Database access
db() {
    print_header "MySQL Database Access"

    if ! docker compose ps | grep -q "Up"; then
        print_warning "Containers are not running. Starting them first..."
        start
    fi

    print_info "Connecting to MySQL..."
    docker compose exec shop_mysql mysql -uuser -puser shop
}


# Database access
db_test() {
    print_header "MySQL Database Access"

    if ! docker compose ps | grep -q "Up"; then
        print_warning "Containers are not running. Starting them first..."
        start
    fi

    print_info "Connecting to MySQL..."
    docker compose exec test_mysql mysql -utestuser -ptestpass test_shop
}

# Show help
help() {
    cat << EOF
${BLUE}SimpleShop Task Runner${NC}

${GREEN}Usage:${NC}
  ./run.sh <command> [options]

${GREEN}Container Management:${NC}
  start              Start Docker containers
  stop               Stop Docker containers
  restart            Restart Docker containers
  down               Stop and remove containers
  status             Show container status
  logs [service]     Show logs (optionally for specific service)
  shell              Open bash shell in shop container
  fresh              Fresh start (rebuild everything)
  clean              Clean up all containers and volumes

${GREEN}Testing:${NC}
  test [options]     Run all PHP tests (pass PHPUnit options)
  test-unit          Run PHP unit tests only
  test-integration   Run PHP integration tests only
  test-coverage      Run PHP tests with coverage report
  test-js            Run JavaScript unit tests
  test-js-watch      Run JavaScript tests in watch mode
  test-js-coverage   Run JavaScript tests with coverage

${GREEN}Development:${NC}
  install            Install composer dependencies
  composer <cmd>     Run composer command
  php <cmd>          Run PHP command
  db                 Access MySQL database
  db_test            Access MySQL test database

${GREEN}Examples:${NC}
  ./run.sh start                    # Start containers
  ./run.sh test                     # Run all tests
  ./run.sh test-unit                # Run unit tests only
  ./run.sh test tests/Unit/ItemsTest.php
  ./run.sh test --filter testOrderItem
  ./run.sh logs shop                # View shop container logs
  ./run.sh shell                    # Open shell in container
  ./run.sh composer require package # Install a package
  ./run.sh db                       # Connect to database

${GREEN}Quick Start:${NC}
  ./run.sh start                    # Start everything
  ./run.sh test                     # Run tests

EOF
}

# Main command router
case "$1" in
    start)
        start
        ;;
    stop)
        stop
        ;;
    restart)
        restart
        ;;
    down)
        down
        ;;
    status)
        status
        ;;
    logs)
        logs "$2"
        ;;
    test)
        shift
        test "$@"
        ;;
    test-unit)
        test_unit
        ;;
    test-integration)
        test_integration
        ;;
    test-coverage)
        test_coverage
        ;;
    test-js)
        test_js
        ;;
    test-js-watch)
        test_js_watch
        ;;
    test-js-coverage)
        test_js_coverage
        ;;
    install)
        install
        ;;
    composer)
        shift
        composer "$@"
        ;;
    shell)
        shell
        ;;
    php)
        shift
        php "$@"
        ;;
    db)
        db
        ;;
    db_test)
        db_test
        ;;
    clean)
        clean
        ;;
    fresh)
        fresh
        ;;
    help|--help|-h|"")
        help
        ;;
    *)
        print_error "Unknown command: $1"
        echo ""
        help
        exit 1
        ;;
esac
