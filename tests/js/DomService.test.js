/**
 * Unit tests for DomService
 */

const SimpleShop = require('../../public/js/main.js');

describe('DomService', () => {
    let DomService;

    beforeEach(() => {
        DomService = SimpleShop.DomService;
        expect(DomService).toBeDefined();
        jest.clearAllMocks();
    });

    describe('get', () => {
        test('should return jQuery element when jQuery is available', () => {
            const result = DomService.get('#test');
            expect($).toHaveBeenCalledWith('#test');
            expect(result).toBeDefined();
        });

        test('should return null when jQuery is not available', () => {
            const original$ = global.$;
            global.$ = undefined;

            const result = DomService.get('#test');
            expect(result).toBeNull();

            global.$ = original$;
        });
    });

    describe('hide', () => {
        test('should call hide on jQuery element', () => {
            const mockElement = { hide: jest.fn().mockReturnThis() };
            $.mockReturnValue(mockElement);

            DomService.hide('#test');

            expect($).toHaveBeenCalledWith('#test');
            expect(mockElement.hide).toHaveBeenCalled();
        });
    });

    describe('show', () => {
        test('should call show on jQuery element', () => {
            const mockElement = { show: jest.fn().mockReturnThis() };
            $.mockReturnValue(mockElement);

            DomService.show('#test');

            expect($).toHaveBeenCalledWith('#test');
            expect(mockElement.show).toHaveBeenCalled();
        });
    });

    describe('html', () => {
        test('should set HTML content when content provided', () => {
            const mockElement = { html: jest.fn().mockReturnThis() };
            $.mockReturnValue(mockElement);

            DomService.html('#test', '<div>content</div>');

            expect(mockElement.html).toHaveBeenCalledWith('<div>content</div>');
        });

        test('should get HTML content when content not provided', () => {
            const mockElement = { html: jest.fn().mockReturnValue('<div>content</div>') };
            $.mockReturnValue(mockElement);

            const result = DomService.html('#test');

            expect(mockElement.html).toHaveBeenCalled();
            expect(result).toBe('<div>content</div>');
        });
    });

    describe('text', () => {
        test('should return text content', () => {
            const mockElement = { text: jest.fn().mockReturnValue('test text') };
            $.mockReturnValue(mockElement);

            const result = DomService.text('#test');

            expect(mockElement.text).toHaveBeenCalled();
            expect(result).toBe('test text');
        });
    });

    describe('val', () => {
        test('should return value', () => {
            const mockElement = { val: jest.fn().mockReturnValue('test value') };
            $.mockReturnValue(mockElement);

            const result = DomService.val('#test');

            expect(mockElement.val).toHaveBeenCalled();
            expect(result).toBe('test value');
        });
    });

    describe('attr', () => {
        test('should set attribute when value provided', () => {
            const mockElement = { attr: jest.fn().mockReturnThis() };
            $.mockReturnValue(mockElement);

            DomService.attr('#test', 'data-id', '123');

            expect(mockElement.attr).toHaveBeenCalledWith('data-id', '123');
        });

        test('should get attribute when value not provided', () => {
            const mockElement = { attr: jest.fn().mockReturnValue('123') };
            $.mockReturnValue(mockElement);

            const result = DomService.attr('#test', 'data-id');

            expect(mockElement.attr).toHaveBeenCalledWith('data-id');
            expect(result).toBe('123');
        });
    });
});

