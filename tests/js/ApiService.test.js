/**
 * Unit tests for ApiService
 */

const SimpleShop = require('../../js/main.js');

describe('ApiService', () => {
    let ApiService;

    beforeEach(() => {
        ApiService = SimpleShop.ApiService;
        expect(ApiService).toBeDefined();
    });

    describe('post', () => {
        test('should call jQuery.post when available', () => {
            const mockCallback = jest.fn();
            const mockData = { test: 'data' };

            ApiService.post('test.php', mockData, mockCallback, 'json');

            expect($.post).toHaveBeenCalledWith('test.php', mockData, mockCallback, 'json');
        });

        test('should throw error when jQuery is not available', () => {
            const originalPost = $.post;
            delete $.post;

            expect(() => {
                ApiService.post('test.php', {}, jest.fn(), 'json');
            }).toThrow('jQuery not available');

            $.post = originalPost;
        });
    });

    describe('serializeForm', () => {
        test('should call jQuery serialize when available', () => {
            const result = ApiService.serializeForm('#test-form');
            expect($).toHaveBeenCalledWith('#test-form');
        });

        test('should return empty string when jQuery is not available', () => {
            const original$ = global.$;
            global.$ = undefined;

            const result = ApiService.serializeForm('#test-form');
            expect(result).toBe('');

            global.$ = original$;
        });
    });
});

