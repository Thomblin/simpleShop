# SimpleShop Test Suite

## Overview

This test suite provides comprehensive unit and integration tests for the SimpleShop e-commerce application. The tests cover all refactored components including value objects, services, and business logic.

## Test Structure

```
tests/
├── bootstrap.php           # Test bootstrap and autoloading
├── Unit/                   # Unit tests for individual components
│   ├── ConfigTest.php
│   ├── OrderItemTest.php
│   ├── OrderTest.php
│   ├── PriceResultTest.php
│   ├── TranslationTest.php
│   ├── TemplateTest.php
│   ├── PriceCalculatorTest.php
│   ├── MailServiceTest.php
│   ├── ItemsTest.php
│   └── OrderServiceTest.php
├── Integration/            # Integration tests for complete workflows
│   └── OrderFlowTest.php
├── Mocks/                  # Mock implementations for testing
│   ├── MockDb.php
│   ├── MockConfig.php
│   └── MockTranslationLoader.php
└── fixtures/               # Test data and templates
    └── test_template.php
```

## Prerequisites

1. **PHP 7.4 or higher**
2. **Composer** for dependency management

## Installation

### 1. Install Dependencies

```bash
composer install
```

This will install PHPUnit 9.5 and all required dependencies.

### 2. Verify Installation

```bash
vendor/bin/phpunit --version
```

Should output: `PHPUnit 9.5.x`

## Running Tests

### Run All Tests

```bash
composer test
```

Or directly with PHPUnit:

```bash
vendor/bin/phpunit
```

### Run Specific Test Suites

**Unit Tests Only:**
```bash
vendor/bin/phpunit --testsuite Unit
```

**Integration Tests Only:**
```bash
vendor/bin/phpunit --testsuite Integration
```

### Run Specific Test File

```bash
vendor/bin/phpunit tests/Unit/ItemsTest.php
```

### Run Specific Test Method

```bash
vendor/bin/phpunit --filter testOrderItemWithSufficientInventory
```

### Run Tests with Coverage

```bash
composer test-coverage
```

This generates an HTML coverage report in the `coverage/` directory.

View the report:
```bash
open coverage/index.html  # macOS
xdg-open coverage/index.html  # Linux
```

### Verbose Output

```bash
vendor/bin/phpunit --verbose
```

## Test Coverage

The test suite covers:

### ✅ Value Objects (100% coverage)
- **OrderItem** - 9 tests
- **Order** - 10 tests
- **PriceResult** - 8 tests

### ✅ Core Classes
- **Config** - 10 tests
- **Translation** - 6 tests
- **Template** - 9 tests

### ✅ Services
- **PriceCalculator** - 11 tests
- **MailService** - 6 tests (interface compliance)
- **OrderService** - 11 tests
- **Items** - 10 tests

### ✅ Integration Tests
- **Complete Order Flow** - 5 comprehensive scenarios

**Total: 95+ test cases**

## Writing New Tests

### 1. Unit Test Template

```php
<?php

use PHPUnit\Framework\TestCase;

class MyClassTest extends TestCase
{
    private $subject;

    protected function setUp(): void
    {
        // Set up test fixtures
        $this->subject = new MyClass();
    }

    public function testSomething()
    {
        // Arrange
        $input = 'test';

        // Act
        $result = $this->subject->doSomething($input);

        // Assert
        $this->assertEquals('expected', $result);
    }
}
```

### 2. Using Mocks

```php
public function testWithMockDatabase()
{
    $mockDb = new MockDb();
    $mockDb->setData('items', [
        ['item_id' => 1, 'name' => 'Test Item']
    ]);

    $items = new Items($mockDb);
    $result = $items->getItems();

    $this->assertArrayHasKey(1, $result);
}
```

### 3. Testing Exceptions

```php
public function testThrowsException()
{
    $this->expectException(InvalidArgumentException::class);
    $this->expectExceptionMessage('Invalid input');

    $subject->methodThatThrows('bad input');
}
```

## Mock Objects

### MockDb

Simulates database operations without actual database access.

**Features:**
- Set return data for queries
- Track all executed queries
- Simulate failures
- Transaction support

**Usage:**
```php
$mockDb = new MockDb();

// Set data to return
$mockDb->setData('items', [['item_id' => 1]]);

// Execute query
$result = $mockDb->fetchAll("SELECT * FROM items");

// Verify queries
$queries = $mockDb->getQueries();
```

### MockConfig

Provides test configuration values.

**Usage:**
```php
$config = new MockConfig([
    'mysqlHost' => 'test-host',
    'language' => 'en'
]);
```

### MockTranslationLoader

Provides test translations without file I/O.

**Usage:**
```php
$loader = new MockTranslationLoader([
    'greeting' => 'Hello'
]);

$translation = new Translation($loader, 'en');
```

## Continuous Integration

### GitHub Actions Example

```yaml
name: Tests

on: [push, pull_request]

jobs:
  test:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - uses: php-actions/composer@v6
      - name: Run tests
        run: vendor/bin/phpunit
```

## Test Best Practices

1. **Arrange-Act-Assert**: Structure tests in three clear sections
2. **One assertion per test**: Test one thing at a time (or related assertions)
3. **Descriptive names**: Use `testMethodNameWithCondition` pattern
4. **Independent tests**: Each test should be able to run independently
5. **Use setUp/tearDown**: Initialize and clean up in appropriate methods
6. **Mock external dependencies**: Database, file system, mail, etc.
7. **Test edge cases**: Empty inputs, null values, boundary conditions
8. **Test error conditions**: Exceptions, validation failures, etc.

## Common Issues

### "Class not found" errors
**Solution:** Run `composer dump-autoload`

### Permission errors
**Solution:** Ensure test files are readable: `chmod 644 tests/**/*.php`

### Tests pass locally but fail in CI
**Solution:** Check file paths are relative, not absolute

## Test Metrics

Run tests with metrics:
```bash
vendor/bin/phpunit --coverage-text
```

Expected metrics:
- **Lines:** > 80%
- **Methods:** > 90%
- **Classes:** 100%

## Contributing

When adding new features:

1. Write tests first (TDD)
2. Ensure all tests pass
3. Maintain coverage above 80%
4. Update this README if needed

## Resources

- [PHPUnit Documentation](https://phpunit.de/documentation.html)
- [Testing Best Practices](https://phpunit.de/manual/current/en/writing-tests-for-phpunit.html)
- [Mocking in PHPUnit](https://phpunit.de/manual/current/en/test-doubles.html)
