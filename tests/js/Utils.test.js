/**
 * Unit tests for Utils
 */

require('../../js/main.js');

describe('Utils', () => {
    let SimpleShop;

    beforeEach(() => {
        if (typeof require !== 'undefined') {
            SimpleShop = require('../../js/main.js');
        } else {
            SimpleShop = (typeof window !== 'undefined' && window.SimpleShop) || 
                         (typeof global !== 'undefined' && global.SimpleShop);
        }
        expect(SimpleShop).not.toBeNull();
    });

    describe('formatPrice', () => {
        test('should format price with comma as decimal separator', () => {
            const Utils = SimpleShop.Utils;
            expect(Utils.formatPrice(10.50)).toBe('10,50');
            expect(Utils.formatPrice(0.99)).toBe('0,99');
            expect(Utils.formatPrice(100)).toBe('100,00');
        });

        test('should handle zero', () => {
            const Utils = SimpleShop.Utils;
            expect(Utils.formatPrice(0)).toBe('0,00');
        });

        test('should handle large numbers', () => {
            const Utils = SimpleShop.Utils;
            expect(Utils.formatPrice(1234.56)).toBe('1234,56');
        });
    });

    describe('formatCurrency', () => {
        beforeEach(() => {
            // Set up shopConfig
            if (typeof shopConfig !== 'undefined') {
                shopConfig.currency = '€';
            }
        });

        test('should format price with currency', () => {
            const Utils = SimpleShop.Utils;
            // Ensure currency is set
            if (typeof shopConfig !== 'undefined') {
                shopConfig.currency = '€';
            }
            const result = Utils.formatCurrency(10.50);
            expect(result).toContain('10,50');
            // The formatCurrency function uses shopConfig.currency from the module
            // If currency is set, it should be in the result
            expect(result.length).toBeGreaterThan(5); // At least "10,50 " + currency
        });
    });

    describe('buildFieldName', () => {
        test('should build field name without bundleOptionId', () => {
            const Utils = SimpleShop.Utils;
            const fieldName = Utils.buildFieldName('item1', 'bundle1', '');
            expect(fieldName).toBe('item1[bundle1]');
        });

        test('should build field name with bundleOptionId', () => {
            const Utils = SimpleShop.Utils;
            const fieldName = Utils.buildFieldName('item1', 'bundle1', 'option1');
            expect(fieldName).toBe('item1[bundle1][option1]');
        });

        test('should handle null bundleOptionId', () => {
            const Utils = SimpleShop.Utils;
            const fieldName = Utils.buildFieldName('item1', 'bundle1', null);
            expect(fieldName).toBe('item1[bundle1]');
        });
    });

    describe('findBasketItem', () => {
        beforeEach(() => {
            SimpleShop.clearBasket();
        });

        test('should find existing item', () => {
            const Utils = SimpleShop.Utils;
            SimpleShop.BasketLogic.addItem('item1', 'Item', 'bundle1', 'Bundle', 'option1', 'Option', 1, 10);
            
            const item = Utils.findBasketItem('item1', 'bundle1', 'option1');
            
            expect(item).not.toBeNull();
            expect(item.itemId).toBe('item1');
            expect(item.bundleId).toBe('bundle1');
            expect(item.bundleOptionId).toBe('option1');
        });

        test('should return null for non-existent item', () => {
            const Utils = SimpleShop.Utils;
            const item = Utils.findBasketItem('item999', 'bundle999', 'option999');
            expect(item).toBeNull();
        });

        test('should not find item with different bundleOptionId', () => {
            const Utils = SimpleShop.Utils;
            SimpleShop.BasketLogic.addItem('item1', 'Item', 'bundle1', 'Bundle', 'option1', 'Option', 1, 10);
            
            const item = Utils.findBasketItem('item1', 'bundle1', 'option2');
            expect(item).toBeNull();
        });
    });
});

