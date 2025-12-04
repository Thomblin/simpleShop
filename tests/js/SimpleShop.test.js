/**
 * Integration tests for SimpleShop public API
 */

require('../../js/main.js');

describe('SimpleShop Public API', () => {
    let SimpleShop;

    beforeEach(() => {
        if (typeof require !== 'undefined') {
            SimpleShop = require('../../js/main.js');
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
            shopConfig.translations.errorFillRequired = 'Please fill required fields';
            shopConfig.currency = '€';
        }
    });

    describe('addToBasket', () => {
        test('should add item and update display', () => {
            SimpleShop.addToBasket('item1', 'Item Name', 'bundle1', 'Bundle', 'option1', 'Option', 2, 10.50);
            
            const basket = SimpleShop.getBasket();
            expect(basket.length).toBe(1);
            expect(basket[0].itemId).toBe('item1');
            expect(basket[0].quantity).toBe(2);
        });

        test('should replace quantity for same item', () => {
            SimpleShop.addToBasket('item1', 'Item', 'bundle1', 'Bundle', 'option1', 'Option', 2, 10);
            SimpleShop.addToBasket('item1', 'Item', 'bundle1', 'Bundle', 'option1', 'Option', 3, 10);
            
            const basket = SimpleShop.getBasket();
            expect(basket.length).toBe(1);
            expect(basket[0].quantity).toBe(3); // Replaced with new quantity, not accumulated
        });
    });

    describe('removeFromBasket', () => {
        test('should remove item from basket', () => {
            SimpleShop.addToBasket('item1', 'Item 1', 'bundle1', 'Bundle', '', '', 1, 10);
            SimpleShop.addToBasket('item2', 'Item 2', 'bundle2', 'Bundle', '', '', 1, 10);
            
            const basketBefore = SimpleShop.getBasket();
            const itemId = basketBefore[0].id;
            
            SimpleShop.removeFromBasket(itemId);
            
            const basketAfter = SimpleShop.getBasket();
            expect(basketAfter.length).toBe(1);
            expect(basketAfter[0].itemId).toBe('item2');
        });
    });

    describe('calculateBasketTotal', () => {
        test('should calculate total correctly', () => {
            SimpleShop.addToBasket('item1', 'Item 1', 'bundle1', 'Bundle', '', '', 2, 10);
            SimpleShop.addToBasket('item2', 'Item 2', 'bundle2', 'Bundle', '', '', 3, 15);
            
            const total = SimpleShop.calculateBasketTotal();
            expect(total).toBe(65); // (2 * 10) + (3 * 15)
        });

        test('should return 0 for empty basket', () => {
            const total = SimpleShop.calculateBasketTotal();
            expect(total).toBe(0);
        });
    });

    describe('getBasket', () => {
        test('should return basket array', () => {
            SimpleShop.addToBasket('item1', 'Item', 'bundle1', 'Bundle', '', '', 1, 10);
            
            const basket = SimpleShop.getBasket();
            expect(Array.isArray(basket)).toBe(true);
            expect(basket.length).toBe(1);
        });

        test('should return empty array for empty basket', () => {
            const basket = SimpleShop.getBasket();
            expect(basket.length).toBe(0);
        });
    });

    describe('clearBasket', () => {
        test('should clear all items', () => {
            SimpleShop.addToBasket('item1', 'Item 1', 'bundle1', 'Bundle', '', '', 1, 10);
            SimpleShop.addToBasket('item2', 'Item 2', 'bundle2', 'Bundle', '', '', 1, 10);
            
            SimpleShop.clearBasket();
            
            const basket = SimpleShop.getBasket();
            expect(basket.length).toBe(0);
        });
    });

    describe('getConfig', () => {
        test('should return configuration object', () => {
            const config = SimpleShop.getConfig();
            expect(config).toBeDefined();
            expect(config).toHaveProperty('currency');
            expect(config).toHaveProperty('translations');
        });
    });
});

