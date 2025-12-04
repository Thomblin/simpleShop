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
        test('should show basket and set HTML when basket has items', () => {
            SimpleShop.BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle 1', '', '', 2, 10);
            
            const mockBasket = {
                stop: jest.fn().mockReturnThis(),
                css: jest.fn().mockReturnThis(),
                animate: jest.fn().mockReturnThis()
            };
            SimpleShop.DomService.get = jest.fn((selector) => {
                if (selector === '#basket_display') {
                    return mockBasket;
                }
                return null;
            });
            
            SimpleShop.BasketDisplay.render();
            
            expect(SimpleShop.DomService.html).toHaveBeenCalledWith('#basket_items', expect.stringContaining('Item 1'));
            expect(SimpleShop.DomService.show).toHaveBeenCalledWith('#basket_display');
        });

        test('should animate basket highlight', () => {
            SimpleShop.BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle 1', '', '', 1, 10);
            
            const mockBasket = {
                stop: jest.fn().mockReturnThis(),
                css: jest.fn().mockReturnThis(),
                animate: jest.fn().mockReturnThis()
            };
            SimpleShop.DomService.get = jest.fn().mockReturnValue(mockBasket);
            
            SimpleShop.BasketDisplay.render();
            
            expect(mockBasket.stop).toHaveBeenCalled();
            expect(mockBasket.css).toHaveBeenCalledWith('background-color', '#e8f5e9');
            expect(mockBasket.animate).toHaveBeenCalledWith({ backgroundColor: '#ddd' }, 800);
        });

        test('should handle missing animation methods gracefully', () => {
            SimpleShop.BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle 1', '', '', 1, 10);
            
            const mockBasket = {
                // Missing stop, css, animate methods
            };
            SimpleShop.DomService.get = jest.fn().mockReturnValue(mockBasket);
            
            expect(() => {
                SimpleShop.BasketDisplay.render();
            }).not.toThrow();
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

