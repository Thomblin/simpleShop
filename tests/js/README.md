# JavaScript Unit Tests

This directory contains unit tests for the SimpleShop JavaScript module (`js/main.js`).

## Test Structure

- **BasketLogic.test.js** - Tests for basket business logic (add, remove, calculate totals)
- **BasketDisplay.test.js** - Tests for basket HTML generation
- **Utils.test.js** - Tests for utility functions (price formatting, field name building)
- **OptionHandler.test.js** - Tests for option selection handling
- **SimpleShop.test.js** - Integration tests for the public SimpleShop API
- **ApiService.test.js** - Tests for API service abstraction
- **DomService.test.js** - Tests for DOM service abstraction
- **FormService.test.js** - Tests for form field generation
- **setup.js** - Jest configuration and mocks

## Running Tests

### From Command Line

```bash
# Run all tests
npm test

# Run tests in watch mode (for development)
npm run test:watch

# Run tests with coverage
npm run test:coverage

# Run tests in CI mode
npm run test:ci
```

### Using Makefile

```bash
# Run JavaScript tests
make test-js

# Run JavaScript tests in watch mode
make test-js-watch

# Run JavaScript tests with coverage
make test-js-coverage
```

## Test Environment

Tests run in a jsdom environment (simulated browser) using Jest. jQuery is mocked to allow testing without a real DOM.

## Coverage

Coverage reports are generated in `coverage/js/` directory. The coverage includes:
- Line coverage
- Function coverage
- Branch coverage

Coverage thresholds (if configured):
- Lines: 80%+
- Functions: 80%+
- Branches: 70%+

## Writing New Tests

When adding new functionality to `main.js`, ensure you:

1. Add corresponding unit tests
2. Test both success and error cases
3. Mock external dependencies (jQuery, API calls)
4. Test edge cases and boundary conditions

## Example Test Structure

```javascript
const SimpleShop = require('../../js/main.js');

describe('MyFeature', () => {
    let SimpleShop;

    beforeEach(() => {
        SimpleShop = require('../../js/main.js');
        // Setup test state
    });

    test('should do something', () => {
        // Arrange
        // Act
        // Assert
    });
});
```

