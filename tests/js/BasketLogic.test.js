/**
 * Unit tests for BasketLogic
 */

// Load the main.js file - SimpleShop will be available as a global
require('../../js/main.js');

describe('BasketLogic', () => {
    let SimpleShop;

    beforeEach(() => {
        // Get reference to SimpleShop module
        // In browser: window.SimpleShop, in Node: global.SimpleShop or require
        if (typeof require !== 'undefined') {
            SimpleShop = require('../../js/main.js');
        } else {
            SimpleShop = (typeof window !== 'undefined' && window.SimpleShop) || 
                         (typeof global !== 'undefined' && global.SimpleShop);
        }
        
        expect(SimpleShop).not.toBeNull();
        
        // Clear basket before each test
        SimpleShop.clearBasket();
    });

    describe('createBasketItem', () => {
        test('should create a basket item with correct structure', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            const item = BasketLogic.createBasketItem(
                'item1', 'Item Name', 'bundle1', 'Bundle Name', 'option1', 'Option Label', 2, 10.50
            );

            expect(item).toHaveProperty('id');
            expect(item.itemId).toBe('item1');
            expect(item.itemName).toBe('Item Name');
            expect(item.bundleId).toBe('bundle1');
            expect(item.bundleName).toBe('Bundle Name');
            expect(item.bundleOptionId).toBe('option1');
            expect(item.optionLabel).toBe('Option Label');
            expect(item.quantity).toBe(2);
            expect(item.price).toBe(10.50);
            expect(item.totalPrice).toBe(21.00);
        });

        test('should create unique IDs for each item', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            const item1 = BasketLogic.createBasketItem('item1', 'Item 1', 'bundle1', 'Bundle', '', '', 1, 10);
            const item2 = BasketLogic.createBasketItem('item2', 'Item 2', 'bundle2', 'Bundle', '', '', 1, 20);

            expect(item1.id).not.toBe(item2.id);
        });
    });

    describe('addItem', () => {
        test('should add new item to empty basket', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            const result = BasketLogic.addItem(
                'item1', 'Item Name', 'bundle1', 'Bundle', 'option1', 'Option', 2, 10.50
            );

            expect(result.basket.length).toBe(1);
            expect(result.basket[0].itemId).toBe('item1');
            expect(result.basket[0].quantity).toBe(2);
            expect(result.basket[0].totalPrice).toBe(21.00);
            expect(result.wasUpdate).toBe(false);
        });

        test('should replace quantity when adding same item', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            
            // Add item first time
            BasketLogic.addItem('item1', 'Item', 'bundle1', 'Bundle', 'option1', 'Option', 2, 10);
            
            // Add same item again - should replace quantity, not add
            const result = BasketLogic.addItem('item1', 'Item', 'bundle1', 'Bundle', 'option1', 'Option', 3, 10);

            expect(result.basket.length).toBe(1);
            expect(result.basket[0].quantity).toBe(3); // Replaced with new quantity
            expect(result.basket[0].totalPrice).toBe(30.00); // 3 * 10
            expect(result.wasUpdate).toBe(true);
        });

        test('should add separate items with different bundleOptionId', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            
            BasketLogic.addItem('item1', 'Item', 'bundle1', 'Bundle', 'option1', 'Option 1', 1, 10);
            const result = BasketLogic.addItem('item1', 'Item', 'bundle1', 'Bundle', 'option2', 'Option 2', 1, 10);

            expect(result.basket.length).toBe(2);
            expect(result.wasUpdate).toBe(false);
        });

        test('should add separate items with same bundleId but no bundleOptionId', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            
            BasketLogic.addItem('item1', 'Item', 'bundle1', 'Bundle', '', '', 1, 10);
            const result = BasketLogic.addItem('item2', 'Item', 'bundle2', 'Bundle', '', '', 1, 10);

            expect(result.basket.length).toBe(2);
            expect(result.wasUpdate).toBe(false);
        });
    });

    describe('removeItem', () => {
        test('should remove item by basketItemId', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            
            BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle', '', '', 1, 10);
            const basketAfterAdd = BasketLogic.getBasket();
            const itemId = basketAfterAdd[0].id;
            
            const basket = BasketLogic.removeItem(itemId);

            expect(basket.length).toBe(0);
        });

        test('should not remove item with wrong id', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            
            BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle', '', '', 1, 10);
            const basketAfterAdd = BasketLogic.getBasket();
            
            const basket = BasketLogic.removeItem(99999);

            expect(basket.length).toBe(1);
        });
    });

    describe('calculateTotal', () => {
        test('should return 0 for empty basket', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            const total = BasketLogic.calculateTotal();
            expect(total).toBe(0);
        });

        test('should calculate total for single item', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            BasketLogic.addItem('item1', 'Item', 'bundle1', 'Bundle', '', '', 2, 10.50);
            const total = BasketLogic.calculateTotal();
            expect(total).toBe(21.00);
        });

        test('should calculate total for multiple items', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle', '', '', 2, 10);
            BasketLogic.addItem('item2', 'Item 2', 'bundle2', 'Bundle', '', '', 3, 15);
            const total = BasketLogic.calculateTotal();
            expect(total).toBe(65); // (2 * 10) + (3 * 15)
        });
    });

    describe('getBasket', () => {
        test('should return copy of basket', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            BasketLogic.addItem('item1', 'Item', 'bundle1', 'Bundle', '', '', 1, 10);
            
            const basket1 = BasketLogic.getBasket();
            const basket2 = BasketLogic.getBasket();

            expect(basket1).not.toBe(basket2); // Different references
            expect(basket1).toEqual(basket2); // Same content
        });
    });

    describe('clearBasket', () => {
        test('should clear all items from basket', () => {
            const BasketLogic = SimpleShop.BasketLogic;
            BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle', '', '', 1, 10);
            BasketLogic.addItem('item2', 'Item 2', 'bundle2', 'Bundle', '', '', 1, 10);
            
            SimpleShop.clearBasket();
            const basket = BasketLogic.getBasket();

            expect(basket.length).toBe(0);
        });
    });
});

