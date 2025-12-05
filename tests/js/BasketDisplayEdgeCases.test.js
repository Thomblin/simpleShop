/**
 * Unit tests for BasketDisplay edge cases
 * Tests for empty basket, animation, and edge cases
 */

const SimpleShop = require('../../js/main.js');

describe('BasketDisplay Edge Cases', () => {
    let SimpleShop;

    beforeEach(() => {
        SimpleShop = require('../../js/main.js');
        SimpleShop.clearBasket();
        jest.clearAllMocks();

        // Set up shopConfig
        if (typeof shopConfig !== 'undefined') {
            shopConfig.translations.times = 'times';
            shopConfig.translations.remove = 'Remove';
            shopConfig.currency = '€';
        }

        // Mock DomService methods
        SimpleShop.DomService.html = jest.fn();
        SimpleShop.DomService.hide = jest.fn();
        SimpleShop.DomService.show = jest.fn();
        SimpleShop.DomService.get = jest.fn();
    });

    describe('render with empty basket', () => {
        test('should hide basket display when basket is empty', () => {
            jest.clearAllMocks();
            SimpleShop.BasketDisplay.render();

            expect(SimpleShop.DomService.hide).toHaveBeenCalledWith('#basket_display');
            expect(SimpleShop.DomService.html).not.toHaveBeenCalledWith('#basket_items', expect.any(String));
        });
    });

    describe('render with items', () => {
        test('should show basket and total section when basket has items', () => {
            SimpleShop.BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle 1', '', '', 2, 10);

            SimpleShop.BasketDisplay.render();

            expect(SimpleShop.DomService.html).toHaveBeenCalledWith('#basket_items', expect.stringContaining('Item 1'));
            expect(SimpleShop.DomService.show).toHaveBeenCalledWith('#basket_display');
            expect(SimpleShop.DomService.show).toHaveBeenCalledWith('.total-section');
        });

        test('should hide total section when basket is empty', () => {
            SimpleShop.BasketDisplay.render();

            expect(SimpleShop.DomService.hide).toHaveBeenCalledWith('#basket_display');
            expect(SimpleShop.DomService.hide).toHaveBeenCalledWith('.total-section');
        });
    });

    describe('generateItemHtml edge cases', () => {
        test('should handle item with empty optionLabel', () => {
            const item = {
                id: 1,
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

            const html = SimpleShop.BasketDisplay.generateItemHtml(item);

            expect(html).toContain('Test Item');
            expect(html).toContain('Test Bundle');
            expect(html).not.toContain('Test Bundle - '); // Should not have extra dash
        });

        test('should handle item with zero quantity', () => {
            const item = {
                id: 1,
                itemId: 'item1',
                itemName: 'Test Item',
                bundleId: 'bundle1',
                bundleName: 'Test Bundle',
                bundleOptionId: '',
                optionLabel: '',
                quantity: 0,
                price: 10,
                totalPrice: 0
            };

            const html = SimpleShop.BasketDisplay.generateItemHtml(item);

            expect(html).toContain('0');
            expect(html).toContain('0,00');
        });

        test('should handle item with very large price', () => {
            const item = {
                id: 1,
                itemId: 'item1',
                itemName: 'Test Item',
                bundleId: 'bundle1',
                bundleName: 'Test Bundle',
                bundleOptionId: '',
                optionLabel: '',
                quantity: 1,
                price: 9999.99,
                totalPrice: 9999.99
            };

            const html = SimpleShop.BasketDisplay.generateItemHtml(item);

            expect(html).toContain('9999,99');
        });
    });
});

