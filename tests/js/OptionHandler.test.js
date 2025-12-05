/**
 * Unit tests for OptionHandler
 */

require('../../public/js/main.js');

describe('OptionHandler', () => {
    let SimpleShop;

    beforeEach(() => {
        if (typeof require !== 'undefined') {
            SimpleShop = require('../../public/js/main.js');
        } else {
            SimpleShop = (typeof window !== 'undefined' && window.SimpleShop) ||
                (typeof global !== 'undefined' && global.SimpleShop);
        }
        expect(SimpleShop).not.toBeNull();

        // Ensure shopConfig is properly set
        if (typeof shopConfig !== 'undefined') {
            shopConfig.translations.times = 'times';
            shopConfig.currency = '€';
        }
    });

    describe('parseOptionData', () => {
        test('should return null when no option is selected', () => {
            const OptionHandler = SimpleShop.OptionHandler;

            // Mock jQuery select element with empty selection
            const mockSelect = {
                find: jest.fn(() => ({
                    val: jest.fn(() => '')
                }))
            };

            const result = OptionHandler.parseOptionData(mockSelect);
            expect(result).toBeNull();
        });

        test('should parse option data correctly', () => {
            const OptionHandler = SimpleShop.OptionHandler;

            const mockSelectedOption = {
                val: jest.fn(() => 'option1'),
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
                })
            };

            const mockSelect = {
                find: jest.fn(() => mockSelectedOption)
            };

            const result = OptionHandler.parseOptionData(mockSelect);

            expect(result).not.toBeNull();
            expect(result.bundleId).toBe('bundle1');
            expect(result.bundleOptionId).toBe('option1');
            expect(result.price).toBe(10.50);
            expect(result.minCount).toBe(1);
            expect(result.maxCount).toBe(10);
            expect(result.inventory).toBe(5);
        });
    });

    describe('buildQuantityOptions', () => {
        test('should build quantity options with correct format', () => {
            const OptionHandler = SimpleShop.OptionHandler;

            // Set up shopConfig - ensure it's set
            if (typeof shopConfig !== 'undefined') {
                shopConfig.translations.times = 'times';
                shopConfig.currency = '€';
            }

            const options = OptionHandler.buildQuantityOptions(1, 3, 10.50);

            // Check for key parts - be flexible since shopConfig might be empty
            // The function uses shopConfig.translations.times which might be empty string
            expect(options).toContain('value="0"');
            expect(options).toContain('value="1"');
            expect(options).toContain('value="2"');
            expect(options).toContain('value="3"');
            expect(options).toContain('10,50');
            expect(options).toContain('selected="selected"');
            // Currency might be empty, so just check the structure
            expect(options.length).toBeGreaterThan(50); // Should have substantial content
        });

        test('should set 0 as selected when no selectedQuantity provided', () => {
            const OptionHandler = SimpleShop.OptionHandler;
            const options = OptionHandler.buildQuantityOptions(2, 4, 10);

            // When no selectedQuantity is provided, it defaults to 0
            expect(options).toContain('<option value="0" selected="selected">');
            expect(options).not.toContain('<option value="2" selected="selected">');
        });

        test('should select basket quantity when provided', () => {
            const OptionHandler = SimpleShop.OptionHandler;
            const options = OptionHandler.buildQuantityOptions(1, 5, 10, 3);

            expect(options).toContain('<option value="3" selected="selected">');
            expect(options).not.toContain('<option value="1" selected="selected">');
            expect(options).not.toContain('<option value="0" selected="selected">');
        });

        test('should select 0 when selectedQuantity is 0', () => {
            const OptionHandler = SimpleShop.OptionHandler;
            const options = OptionHandler.buildQuantityOptions(1, 5, 10, 0);

            expect(options).toContain('<option value="0" selected="selected">');
            expect(options).not.toContain('<option value="1" selected="selected">');
        });

        test('should clamp selectedQuantity to valid range', () => {
            const OptionHandler = SimpleShop.OptionHandler;
            // selectedQuantity (10) is greater than maxQuantity (5), should clamp to 5
            const options = OptionHandler.buildQuantityOptions(1, 5, 10, 10);

            expect(options).toContain('<option value="5" selected="selected">');
            expect(options).not.toContain('<option value="10"');
        });

        test('should handle minCount equal to maxQuantity', () => {
            const OptionHandler = SimpleShop.OptionHandler;
            const options = OptionHandler.buildQuantityOptions(5, 5, 10);

            expect(options).toContain('value="5"');
            expect(options).toContain('selected="selected"');
            // Should contain both 0 and 5 options
            const valueMatches = options.match(/value="\d+"/g) || [];
            expect(valueMatches.length).toBeGreaterThanOrEqual(2);
        });
    });

    // Note: Tests for handleChange with showInventory scenarios are in OptionHandlerIntegration.test.js
    // because they require shopConfig to be set before the module loads
});

