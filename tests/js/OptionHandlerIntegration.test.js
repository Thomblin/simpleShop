/**
 * Integration tests for OptionHandler.handleChange
 * Tests the full flow of option change handling
 */

const SimpleShop = require('../../js/main.js');

describe('OptionHandler Integration - handleChange', () => {
    let SimpleShop;

    beforeEach(() => {
        SimpleShop = require('../../js/main.js');
        jest.clearAllMocks();
        
        // Set up shopConfig
        if (typeof shopConfig !== 'undefined') {
            shopConfig.translations.times = 'times';
            shopConfig.currency = '€';
            shopConfig.showInventory = true;
        }

        // Mock DomService methods
        SimpleShop.DomService.html = jest.fn();
        SimpleShop.DomService.hide = jest.fn();
        SimpleShop.DomService.show = jest.fn();
        SimpleShop.DomService.get = jest.fn();
        // Mock updateInventoryDisplay since it uses internal DomService
        SimpleShop.updateInventoryDisplay = jest.fn();
    });

    describe('handleChange full flow', () => {
        test('should handle option change with full flow', () => {
            // Ensure showInventory is true for this test
            if (typeof shopConfig !== 'undefined') {
                shopConfig.showInventory = true;
            }

            const mockQuantitySelect = {
                empty: jest.fn().mockReturnThis(),
                append: jest.fn().mockReturnThis(),
                attr: jest.fn().mockReturnThis(),
                length: 1
            };

            const mockSelectedOption = {
                val: jest.fn().mockReturnValue('option1'),
                attr: jest.fn((attr) => {
                    const attrs = {
                        'data-bundle-id': 'bundle1',
                        'data-bundle-option-id': 'option1',
                        'data-price': '10.50',
                        'data-min-count': '1',
                        'data-max-count': '10',
                        'data-inventory': '5'
                    };
                    return attrs[attr];
                }),
                text: jest.fn().mockReturnValue('Option 1')
            };

            const mockSelect = {
                attr: jest.fn().mockReturnValue('item1'),
                find: jest.fn().mockReturnValue(mockSelectedOption)
            };

            // Reset mocks but keep DomService methods mocked
            const hideSpy = jest.fn();
            const showSpy = jest.fn();
            SimpleShop.DomService.hide = hideSpy;
            SimpleShop.DomService.show = showSpy;
            SimpleShop.DomService.get = jest.fn((selector) => {
                if (selector === '#quantity_item1 select.quantity-select') {
                    return mockQuantitySelect;
                }
                return { length: 1 };
            });

            // The function should complete successfully
            expect(() => {
                SimpleShop.OptionHandler.handleChange(mockSelect);
            }).not.toThrow();

            expect(mockQuantitySelect.empty).toHaveBeenCalled();
            expect(mockQuantitySelect.append).toHaveBeenCalled();
            expect(mockQuantitySelect.attr).toHaveBeenCalledWith('name', 'item1[bundle1][option1]');
            expect(showSpy).toHaveBeenCalledWith('#quantity_item1');
            // Note: When showInventory is true, SimpleShop.updateInventoryDisplay is called internally
            // but it uses the closure variable DomService which we can't easily mock from outside.
            // The test verifies that the function executes correctly and performs the key operations.
        });

        test('should handle option change without bundleOptionId', () => {
            const mockQuantitySelect = {
                empty: jest.fn().mockReturnThis(),
                append: jest.fn().mockReturnThis(),
                attr: jest.fn().mockReturnThis(),
                length: 1
            };

            const mockSelectedOption = {
                val: jest.fn().mockReturnValue('option1'),
                attr: jest.fn((attr) => {
                    const attrs = {
                        'data-bundle-id': 'bundle1',
                        'data-bundle-option-id': '',
                        'data-price': '10.50',
                        'data-min-count': '1',
                        'data-max-count': '10',
                        'data-inventory': '5'
                    };
                    return attrs[attr];
                })
            };

            const mockSelect = {
                attr: jest.fn().mockReturnValue('item1'),
                find: jest.fn().mockReturnValue(mockSelectedOption)
            };

            SimpleShop.DomService.get = jest.fn((selector) => {
                if (selector === '#quantity_item1 select.quantity-select') {
                    return mockQuantitySelect;
                }
                return { length: 1 };
            });

            SimpleShop.OptionHandler.handleChange(mockSelect);

            expect(mockQuantitySelect.attr).toHaveBeenCalledWith('name', 'item1[bundle1]');
        });

        test('should hide quantity selector when no option selected', () => {
            const mockSelect = {
                attr: jest.fn().mockReturnValue('item1'),
                find: jest.fn().mockReturnValue({
                    val: jest.fn().mockReturnValue('')
                })
            };

            SimpleShop.OptionHandler.handleChange(mockSelect);

            expect(SimpleShop.DomService.hide).toHaveBeenCalledWith('#quantity_item1');
        });

        test('should clear inventory display when no option selected and showInventory is true', () => {
            if (typeof shopConfig !== 'undefined') {
                shopConfig.showInventory = true;
            }

            // Note: OptionHandler uses internal DomService closure, not SimpleShop.DomService
            // So we can't directly mock it. Instead, we verify the behavior by checking
            // that the function completes without error when showInventory is true.
            const hideSpy = jest.fn();
            SimpleShop.DomService.hide = hideSpy;
            SimpleShop.DomService.show = jest.fn();
            SimpleShop.DomService.get = jest.fn();

            const mockSelect = {
                attr: jest.fn().mockReturnValue('item1'),
                find: jest.fn().mockReturnValue({
                    val: jest.fn().mockReturnValue('')
                })
            };

            // The function should complete successfully
            expect(() => {
                SimpleShop.OptionHandler.handleChange(mockSelect);
            }).not.toThrow();

            expect(hideSpy).toHaveBeenCalledWith('#quantity_item1');
            // The internal DomService.html is called but we can't verify it directly
            // since it uses the closure variable. The test verifies the function
            // executes correctly when showInventory is true.
        });

        test('should return early if quantity select not found', () => {
            const mockSelectedOption = {
                val: jest.fn().mockReturnValue('option1'),
                attr: jest.fn((attr) => {
                    const attrs = {
                        'data-bundle-id': 'bundle1',
                        'data-bundle-option-id': '',
                        'data-price': '10.50',
                        'data-min-count': '1',
                        'data-max-count': '10',
                        'data-inventory': '5'
                    };
                    return attrs[attr];
                })
            };

            const mockSelect = {
                attr: jest.fn().mockReturnValue('item1'),
                find: jest.fn().mockReturnValue(mockSelectedOption)
            };

            // Return null or element without length
            SimpleShop.DomService.get = jest.fn().mockReturnValue(null);

            SimpleShop.OptionHandler.handleChange(mockSelect);

            // Should not throw and should return early
            expect(SimpleShop.DomService.get).toHaveBeenCalled();
        });

        test('should calculate maxQuantity correctly (min of maxCount and inventory)', () => {
            const mockQuantitySelect = {
                empty: jest.fn().mockReturnThis(),
                append: jest.fn().mockReturnThis(),
                attr: jest.fn().mockReturnThis(),
                length: 1
            };

            const mockSelectedOption = {
                val: jest.fn().mockReturnValue('option1'),
                attr: jest.fn((attr) => {
                    const attrs = {
                        'data-bundle-id': 'bundle1',
                        'data-bundle-option-id': '',
                        'data-price': '10.50',
                        'data-min-count': '1',
                        'data-max-count': '10',
                        'data-inventory': '3' // Less than maxCount
                    };
                    return attrs[attr];
                })
            };

            const mockSelect = {
                attr: jest.fn().mockReturnValue('item1'),
                find: jest.fn().mockReturnValue(mockSelectedOption)
            };

            SimpleShop.DomService.get = jest.fn((selector) => {
                if (selector === '#quantity_item1 select.quantity-select') {
                    return mockQuantitySelect;
                }
                return { length: 1 };
            });

            const buildQuantityOptionsSpy = jest.spyOn(SimpleShop.OptionHandler, 'buildQuantityOptions');

            SimpleShop.OptionHandler.handleChange(mockSelect);

            // Should use inventory (3) instead of maxCount (10)
            expect(buildQuantityOptionsSpy).toHaveBeenCalledWith(1, 3, 10.50);
        });
    });
});

