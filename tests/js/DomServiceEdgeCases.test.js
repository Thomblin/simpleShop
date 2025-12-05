/**
 * Unit tests for DomService edge cases
 * Tests for when jQuery is not available or elements are missing
 */

const SimpleShop = require('../../public/js/main.js');

describe('DomService Edge Cases', () => {
    let SimpleShop;
    let original$;

    beforeEach(() => {
        SimpleShop = require('../../public/js/main.js');
        original$ = global.$;
        jest.clearAllMocks();
    });

    afterEach(() => {
        global.$ = original$;
    });

    describe('when jQuery is not available', () => {
        test('get should return null', () => {
            global.$ = undefined;
            
            const result = SimpleShop.DomService.get('#test');
            
            expect(result).toBeNull();
        });

        test('hide should not throw', () => {
            global.$ = undefined;
            
            expect(() => {
                SimpleShop.DomService.hide('#test');
            }).not.toThrow();
        });

        test('show should not throw', () => {
            global.$ = undefined;
            
            expect(() => {
                SimpleShop.DomService.show('#test');
            }).not.toThrow();
        });

        test('html should not throw when setting', () => {
            global.$ = undefined;
            
            expect(() => {
                SimpleShop.DomService.html('#test', '<div>content</div>');
            }).not.toThrow();
        });

        test('html should return undefined when getting and jQuery unavailable', () => {
            global.$ = undefined;
            
            const result = SimpleShop.DomService.html('#test');
            
            expect(result).toBeUndefined();
        });

        test('text should return empty string', () => {
            global.$ = undefined;
            
            const result = SimpleShop.DomService.text('#test');
            
            expect(result).toBe('');
        });

        test('val should return empty string', () => {
            global.$ = undefined;
            
            const result = SimpleShop.DomService.val('#test');
            
            expect(result).toBe('');
        });

        test('attr should not throw when setting', () => {
            global.$ = undefined;
            
            expect(() => {
                SimpleShop.DomService.attr('#test', 'data-id', '123');
            }).not.toThrow();
        });

        test('attr should return undefined when getting and jQuery unavailable', () => {
            global.$ = undefined;
            
            const result = SimpleShop.DomService.attr('#test', 'data-id');
            
            expect(result).toBeUndefined();
        });
    });

    describe('when element exists but methods are missing', () => {
        test('html should handle missing html method', () => {
            global.$ = jest.fn(() => ({
                // No html method
            }));

            expect(() => {
                SimpleShop.DomService.html('#test', 'content');
            }).not.toThrow();
        });

        test('text should handle missing text method', () => {
            global.$ = jest.fn(() => ({
                // No text method
            }));

            const result = SimpleShop.DomService.text('#test');
            
            expect(result).toBe('');
        });

        test('val should handle missing val method', () => {
            global.$ = jest.fn(() => ({
                // No val method
            }));

            const result = SimpleShop.DomService.val('#test');
            
            expect(result).toBe('');
        });

        test('attr should handle missing attr method', () => {
            global.$ = jest.fn(() => ({
                // No attr method
            }));

            expect(() => {
                SimpleShop.DomService.attr('#test', 'data-id', '123');
            }).not.toThrow();
        });
    });
});

