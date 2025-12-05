/**
 * Unit tests for BasketDisplay
 */

require('../../public/js/main.js');

describe('BasketDisplay', () => {
    let SimpleShop;

    beforeEach(() => {
        if (typeof require !== 'undefined') {
            SimpleShop = require('../../public/js/main.js');
        } else {
            SimpleShop = (typeof window !== 'undefined' && window.SimpleShop) ||
                (typeof global !== 'undefined' && global.SimpleShop);
        }
        expect(SimpleShop).not.toBeNull();
        SimpleShop.clearBasket();

        // Set up shopConfig
        if (typeof shopConfig !== 'undefined') {
            shopConfig.translations.times = 'times';
            shopConfig.translations.remove = 'Remove';
            shopConfig.currency = '€';
        }
    });

    describe('generateItemHtml', () => {
        test('should generate HTML for basket item without optionLabel', () => {
            const BasketDisplay = SimpleShop.BasketDisplay;
            const item = {
                id: 1,
                itemId: 'item1',
                itemName: 'Test Item',
                bundleId: 'bundle1',
                bundleName: 'Test Bundle',
                bundleOptionId: '',
                optionLabel: '',
                quantity: 2,
                price: 10.50,
                totalPrice: 21.00
            };

            const html = BasketDisplay.generateItemHtml(item);

            expect(html).toContain('<strong>Test Item</strong>');
            expect(html).toContain('Test Bundle');
            // Check for quantity (2) - times might be empty or have value
            expect(html).toContain('2');
            expect(html).toContain('10,50');
            expect(html).toContain('21,00');
            // Check for remove link - might have empty brackets if translation is empty
            expect(html).toContain('remove-basket-item');
            expect(html).toContain('data-basket-id="1"');
        });

        test('should generate HTML for basket item with optionLabel', () => {
            const BasketDisplay = SimpleShop.BasketDisplay;
            const item = {
                id: 1,
                itemId: 'item1',
                itemName: 'Test Item',
                bundleId: 'bundle1',
                bundleName: 'Test Bundle',
                bundleOptionId: 'option1',
                optionLabel: 'Test Option',
                quantity: 1,
                price: 15.00,
                totalPrice: 15.00
            };

            const html = BasketDisplay.generateItemHtml(item);

            expect(html).toContain('Test Option');
            expect(html).toContain('Test Bundle - Test Option');
        });

        test('should include remove link with correct basket id', () => {
            const BasketDisplay = SimpleShop.BasketDisplay;
            const item = {
                id: 42,
                itemId: 'item1',
                itemName: 'Test Item',
                bundleId: 'bundle1',
                bundleName: 'Test Bundle',
                bundleOptionId: '',
                optionLabel: '',
                quantity: 1,
                price: 10,
                totalPrice: 10
            };

            const html = BasketDisplay.generateItemHtml(item);

            expect(html).toContain('data-basket-id="42"');
            expect(html).toContain('remove-basket-item');
        });
    });
});

