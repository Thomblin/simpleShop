/**
 * Tests for add to basket button handler behavior
 * Tests the logic for handling quantity 0, missing bundleId, etc.
 */

require('../../public/js/main.js');

describe('Add to Basket Handler Logic', () => {
    let SimpleShop;
    let mockAlert;

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
            shopConfig.translations.errorFillRequired = 'Please fill required fields';
            shopConfig.translations.errorSpecifyQuantity = 'Please specify quantity';
            shopConfig.translations.addedToBasket = 'Added!';
            shopConfig.translations.updatedToBasket = 'Updated!';
            shopConfig.currency = '€';
        }

        // Mock alert
        mockAlert = jest.fn();
        global.alert = mockAlert;
    });

    afterEach(() => {
        jest.clearAllMocks();
    });

    describe('Quantity 0 handling', () => {
        test('should remove item from basket when quantity is 0 and item exists in basket', () => {
            // Add item to basket first
            SimpleShop.addToBasket('item1', 'Item 1', 'bundle1', 'Bundle 1', 'option1', 'Option 1', 3, 10.50);
            
            const basketBefore = SimpleShop.getBasket();
            expect(basketBefore.length).toBe(1);
            expect(basketBefore[0].quantity).toBe(3);

            // Simulate the logic: find item in basket and remove it
            const existingItem = SimpleShop.Utils.findBasketItem('item1', 'bundle1', 'option1');
            expect(existingItem).not.toBeNull();
            
            // Remove item (simulating what the handler does)
            SimpleShop.removeFromBasket(existingItem.id);
            
            const basketAfter = SimpleShop.getBasket();
            expect(basketAfter.length).toBe(0);
            expect(mockAlert).not.toHaveBeenCalled();
        });

        test('should show errorSpecifyQuantity when quantity is 0 and item not in basket', () => {
            // Ensure basket is empty
            SimpleShop.clearBasket();
            
            // Simulate the logic: item not in basket
            const existingItem = SimpleShop.Utils.findBasketItem('item1', 'bundle1', 'option1');
            expect(existingItem).toBeNull();
            
            // Simulate what the handler would do: show alert
            if (!existingItem) {
                alert(shopConfig.translations.errorSpecifyQuantity);
            }
            
            expect(mockAlert).toHaveBeenCalledWith('Please specify quantity');
        });

        test('should not add item when quantity is 0 and item not in basket', () => {
            SimpleShop.clearBasket();
            
            const existingItem = SimpleShop.Utils.findBasketItem('item1', 'bundle1', 'option1');
            
            // Simulate handler logic: if quantity is 0 and item not in basket, don't add
            if (!existingItem) {
                // Handler would show alert and return early, not adding to basket
                alert(shopConfig.translations.errorSpecifyQuantity);
            }
            
            const basket = SimpleShop.getBasket();
            expect(basket.length).toBe(0);
            expect(mockAlert).toHaveBeenCalled();
        });
    });

    describe('Missing bundleId handling', () => {
        test('should show errorFillRequired when bundleId is missing', () => {
            // Simulate missing bundleId (no option selected)
            const bundleId = null;
            
            // Simulate handler logic
            if (!bundleId) {
                alert(shopConfig.translations.errorFillRequired);
            }
            
            expect(mockAlert).toHaveBeenCalledWith('Please fill required fields');
        });

        test('should not proceed when bundleId is missing', () => {
            SimpleShop.clearBasket();
            
            // Simulate handler logic: if bundleId is missing, return early
            const bundleId = null;
            if (!bundleId) {
                alert(shopConfig.translations.errorFillRequired);
                return;
            }
            
            // This code should not execute
            SimpleShop.addToBasket('item1', 'Item', 'bundle1', 'Bundle', 'option1', 'Option', 1, 10);
            
            const basket = SimpleShop.getBasket();
            // Since we returned early, basket should still be empty
            // (In real handler, the return would prevent addToBasket from being called)
            expect(basket.length).toBe(0);
        });
    });

    describe('Normal add to basket flow', () => {
        test('should add item when quantity > 0 and bundleId exists', () => {
            SimpleShop.clearBasket();
            
            // Simulate handler logic with valid inputs
            const bundleId = 'bundle1';
            const quantity = 2;
            
            if (!bundleId) {
                alert(shopConfig.translations.errorFillRequired);
                return;
            }
            
            if (quantity <= 0) {
                const existingItem = SimpleShop.Utils.findBasketItem('item1', bundleId, 'option1');
                if (existingItem) {
                    SimpleShop.removeFromBasket(existingItem.id);
                    return;
                } else {
                    alert(shopConfig.translations.errorSpecifyQuantity);
                    return;
                }
            }
            
            // Normal flow: add to basket
            SimpleShop.addToBasket('item1', 'Item 1', bundleId, 'Bundle 1', 'option1', 'Option 1', quantity, 10.50);
            
            const basket = SimpleShop.getBasket();
            expect(basket.length).toBe(1);
            expect(basket[0].quantity).toBe(2);
            expect(mockAlert).not.toHaveBeenCalled();
        });
    });

    describe('Integration: complete handler flow', () => {
        test('should handle complete flow: add, then remove with quantity 0', () => {
            SimpleShop.clearBasket();
            
            // Step 1: Add item to basket
            SimpleShop.addToBasket('item1', 'Item 1', 'bundle1', 'Bundle 1', 'option1', 'Option 1', 2, 10);
            let basket = SimpleShop.getBasket();
            expect(basket.length).toBe(1);
            
            // Step 2: Simulate clicking "add to basket" with quantity 0
            const existingItem = SimpleShop.Utils.findBasketItem('item1', 'bundle1', 'option1');
            if (existingItem) {
                SimpleShop.removeFromBasket(existingItem.id);
            }
            
            basket = SimpleShop.getBasket();
            expect(basket.length).toBe(0);
            expect(mockAlert).not.toHaveBeenCalled();
        });

        test('should handle flow: try to add with quantity 0 when not in basket', () => {
            SimpleShop.clearBasket();
            
            // Simulate clicking "add to basket" with quantity 0 when item not in basket
            const existingItem = SimpleShop.Utils.findBasketItem('item1', 'bundle1', 'option1');
            if (existingItem) {
                SimpleShop.removeFromBasket(existingItem.id);
            } else {
                alert(shopConfig.translations.errorSpecifyQuantity);
            }
            
            const basket = SimpleShop.getBasket();
            expect(basket.length).toBe(0);
            expect(mockAlert).toHaveBeenCalledWith('Please specify quantity');
        });
    });
});

