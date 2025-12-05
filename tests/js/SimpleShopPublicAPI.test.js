/**
 * Unit tests for SimpleShop Public API functions
 * Tests for preview, back, send, handleOptionChange, updateInventoryDisplay, 
 * calculateTotalWithPorto, showSuccessMessage, and updateBasketDisplay
 */

const SimpleShop = require('../../js/main.js');

describe('SimpleShop Public API - Additional Functions', () => {
    let SimpleShop;
    let mockPostCallback;

    beforeEach(() => {
        SimpleShop = require('../../js/main.js');
        SimpleShop.clearBasket();
        jest.clearAllMocks();

        // Set up shopConfig
        if (typeof shopConfig !== 'undefined') {
            shopConfig.translations.times = 'times';
            shopConfig.translations.remove = 'Remove';
            shopConfig.translations.errorFillRequired = 'Please fill required fields';
            shopConfig.translations.addedToBasket = 'Added!';
            shopConfig.translations.updatedToBasket = 'Updated!';
            shopConfig.currency = '€';
            shopConfig.showInventory = false;
        }

        // Mock DomService methods
        SimpleShop.DomService.html = jest.fn();
        SimpleShop.DomService.hide = jest.fn();
        SimpleShop.DomService.show = jest.fn();
        SimpleShop.DomService.get = jest.fn();

        // Mock ApiService.post to capture callbacks
        mockPostCallback = jest.fn();
        SimpleShop.ApiService.post = jest.fn((url, data, callback, dataType) => {
            if (typeof callback === 'function') {
                mockPostCallback.mockImplementation(callback);
            }
        });
    });

    describe('preview', () => {
        test('should call ApiService.post with correct parameters', () => {
            SimpleShop.preview();

            expect(SimpleShop.ApiService.post).toHaveBeenCalledWith(
                'ajax.php',
                expect.any(String),
                expect.any(Function),
                'json'
            );
        });

        test('should handle error response', () => {
            const alertSpy = jest.spyOn(window, 'alert').mockImplementation(() => { });

            SimpleShop.preview();

            // Simulate error response
            const callback = SimpleShop.ApiService.post.mock.calls[0][2];
            callback({ error: 'Test error' });

            expect(alertSpy).toHaveBeenCalledWith('Test error');
            alertSpy.mockRestore();
        });

        test('should display mail preview on success', () => {
            SimpleShop.preview();

            const callback = SimpleShop.ApiService.post.mock.calls[0][2];
            callback({ mail: 'Test mail content', order: false });

            expect(SimpleShop.DomService.html).toHaveBeenCalledWith('#mail_text', '<pre>Test mail content</pre>');
            expect(SimpleShop.DomService.hide).toHaveBeenCalledWith('#main');
            expect(SimpleShop.DomService.show).toHaveBeenCalledWith('#mail');
        });

        test('should show order button when order is true', () => {
            SimpleShop.preview();

            const callback = SimpleShop.ApiService.post.mock.calls[0][2];
            callback({ mail: 'Test mail', order: true });

            expect(SimpleShop.DomService.hide).toHaveBeenCalledWith('#order_hint');
            expect(SimpleShop.DomService.show).toHaveBeenCalledWith('#order');
        });

        test('should show order hint when order is false', () => {
            SimpleShop.preview();

            const callback = SimpleShop.ApiService.post.mock.calls[0][2];
            callback({ mail: 'Test mail', order: false });

            expect(SimpleShop.DomService.hide).toHaveBeenCalledWith('#order');
            expect(SimpleShop.DomService.show).toHaveBeenCalledWith('#order_hint');
        });
    });

    describe('back', () => {
        test('should hide mail and show main', () => {
            SimpleShop.back();

            expect(SimpleShop.DomService.hide).toHaveBeenCalledWith('#mail');
            expect(SimpleShop.DomService.show).toHaveBeenCalledWith('#main');
        });
    });

    describe('send', () => {
        test('should call ApiService.post with mail parameter', () => {
            SimpleShop.send();

            expect(SimpleShop.ApiService.post).toHaveBeenCalledWith(
                'ajax.php?mail=1',
                expect.any(String),
                expect.any(Function),
                'json'
            );
        });

        test('should handle error response', () => {
            const alertSpy = jest.spyOn(window, 'alert').mockImplementation(() => { });

            SimpleShop.send();

            const callback = SimpleShop.ApiService.post.mock.calls[0][2];
            callback({ error: 'Send error' });

            expect(alertSpy).toHaveBeenCalledWith('Send error');
            alertSpy.mockRestore();
        });

        test('should hide mail and show confirm on success', () => {
            SimpleShop.send();

            const callback = SimpleShop.ApiService.post.mock.calls[0][2];
            callback({ success: true });

            expect(SimpleShop.DomService.hide).toHaveBeenCalledWith('#mail');
            expect(SimpleShop.DomService.show).toHaveBeenCalledWith('#confirm');
        });
    });

    describe('handleOptionChange', () => {
        test('should call OptionHandler.handleChange', () => {
            const handleChangeSpy = jest.spyOn(SimpleShop.OptionHandler, 'handleChange');
            const mockSelect = {
                attr: jest.fn().mockReturnValue('item1'),
                find: jest.fn().mockReturnValue({
                    val: jest.fn().mockReturnValue('option1'),
                    attr: jest.fn().mockReturnValue('bundle1')
                })
            };

            SimpleShop.handleOptionChange(mockSelect);

            expect(handleChangeSpy).toHaveBeenCalledWith(mockSelect);
            handleChangeSpy.mockRestore();
        });
    });

    describe('updateInventoryDisplay', () => {
        test('should update inventory display', () => {
            SimpleShop.updateInventoryDisplay('item1', 5);

            expect(SimpleShop.DomService.html).toHaveBeenCalledWith('#inventory_display_item1', 'Inventory: 5');
        });
    });

    describe('calculateTotalWithPorto', () => {
        test('should call ApiService.post with price_only parameter', () => {
            SimpleShop.calculateTotalWithPorto();

            expect(SimpleShop.ApiService.post).toHaveBeenCalledWith(
                'ajax.php?price_only=1',
                expect.any(String),
                expect.any(Function),
                'json'
            );
        });

        test('should update porto and total on response', () => {
            SimpleShop.calculateTotalWithPorto();

            const callback = SimpleShop.ApiService.post.mock.calls[0][2];
            callback({ porto: '5,00 €', price: '100,00 €' });

            expect(SimpleShop.DomService.html).toHaveBeenCalledWith('#porto', '5,00 €');
            expect(SimpleShop.DomService.html).toHaveBeenCalledWith('#total', '100,00 €');
        });
    });

    describe('showSuccessMessage', () => {

        test('should fade in and fade out success message for new item', () => {
            const mockElement = {
                fadeIn: jest.fn(),
                fadeOut: jest.fn(),
                html: jest.fn()
            };
            SimpleShop.DomService.get = jest.fn().mockReturnValue(mockElement);

            jest.useFakeTimers();
            SimpleShop.showSuccessMessage('item1', false);

            expect(SimpleShop.DomService.get).toHaveBeenCalledWith('#success_item1');
            expect(mockElement.html).toHaveBeenCalledWith('✓ <span class="success-text">Added!</span>');
            expect(mockElement.fadeIn).toHaveBeenCalledWith(200);

            jest.advanceTimersByTime(1000);
            expect(mockElement.fadeOut).toHaveBeenCalledWith(1000);

            jest.useRealTimers();
        });

        test('should show blue message for updated item', () => {
            const mockElement = {
                fadeIn: jest.fn(),
                fadeOut: jest.fn(),
                html: jest.fn()
            };
            SimpleShop.DomService.get = jest.fn().mockReturnValue(mockElement);

            jest.useFakeTimers();
            SimpleShop.showSuccessMessage('item1', true);

            expect(mockElement.html).toHaveBeenCalledWith('✓ <span class="success-text">Updated!</span>');

            jest.useRealTimers();
        });

        test('should not fail if element does not have fade methods', () => {
            const mockElement = {};
            SimpleShop.DomService.get = jest.fn().mockReturnValue(mockElement);

            expect(() => {
                SimpleShop.showSuccessMessage('item1', false);
            }).not.toThrow();
        });

        test('should not fail if element is null', () => {
            SimpleShop.DomService.get = jest.fn().mockReturnValue(null);

            expect(() => {
                SimpleShop.showSuccessMessage('item1');
            }).not.toThrow();
        });

        test('should handle element without find method', () => {
            const mockElement = {
                fadeIn: jest.fn(),
                fadeOut: jest.fn(),
                html: jest.fn()
            };
            SimpleShop.DomService.get = jest.fn().mockReturnValue(mockElement);

            jest.useFakeTimers();
            SimpleShop.showSuccessMessage('item1', false);

            expect(mockElement.html).toHaveBeenCalledWith('✓ <span class="success-text">Added!</span>');

            jest.useRealTimers();
        });

        test('should update existing success-text span when available', () => {
            const mockSuccessText = {
                length: 1,
                text: jest.fn()
            };
            const mockElement = {
                fadeIn: jest.fn(),
                fadeOut: jest.fn(),
                html: jest.fn(),
                find: jest.fn().mockReturnValue(mockSuccessText)
            };
            SimpleShop.DomService.get = jest.fn().mockReturnValue(mockElement);

            jest.useFakeTimers();
            SimpleShop.showSuccessMessage('item1', true);

            expect(mockElement.find).toHaveBeenCalledWith('.success-text');
            expect(mockSuccessText.text).toHaveBeenCalledWith('Updated!');
            expect(mockElement.html).not.toHaveBeenCalled();

            jest.useRealTimers();
        });

        test('should create success-text span when find returns empty', () => {
            const mockSuccessText = {
                length: 0
            };
            const mockElement = {
                fadeIn: jest.fn(),
                fadeOut: jest.fn(),
                html: jest.fn(),
                find: jest.fn().mockReturnValue(mockSuccessText)
            };
            SimpleShop.DomService.get = jest.fn().mockReturnValue(mockElement);

            jest.useFakeTimers();
            SimpleShop.showSuccessMessage('item1', false);

            expect(mockElement.html).toHaveBeenCalledWith('✓ <span class="success-text">Added!</span>');

            jest.useRealTimers();
        });
    });

    describe('updateBasketDisplay', () => {
        test('should call BasketDisplay.render and FormService.generateBasketFields', () => {
            // Mock jQuery for FormService - need to handle both $('.basket-field') and $('<input>')
            const mockAppendTo = jest.fn();
            const mockInputElement = {
                attr: jest.fn().mockReturnThis(),
                appendTo: mockAppendTo
            };
            const mockRemoveElement = {
                remove: jest.fn()
            };

            let callCount = 0;
            $.mockImplementation((selector) => {
                callCount++;
                if (selector === '.basket-field') {
                    return mockRemoveElement;
                }
                if (typeof selector === 'string' && selector.startsWith('<')) {
                    return mockInputElement;
                }
                return mockInputElement;
            });

            // Spy on FormService.generateBasketFields
            const generateBasketFieldsSpy = jest.spyOn(SimpleShop.FormService, 'generateBasketFields');

            SimpleShop.addToBasket('item1', 'Item 1', 'bundle1', 'Bundle 1', '', '', 1, 10);
            jest.clearAllMocks();

            SimpleShop.updateBasketDisplay();

            // BasketDisplay.render is called internally, verify FormService is called
            expect(generateBasketFieldsSpy).toHaveBeenCalled();

            generateBasketFieldsSpy.mockRestore();
        });
    });

    describe('updateBasketTotal', () => {
        beforeEach(() => {
            // Set up shopConfig currency
            if (typeof shopConfig !== 'undefined') {
                shopConfig.currency = '€';
            }
        });

        test('should update basket total display and call calculateTotalWithPorto', () => {
            SimpleShop.addToBasket('item1', 'Item 1', 'bundle1', 'Bundle 1', '', '', 2, 10);
            SimpleShop.addToBasket('item2', 'Item 2', 'bundle2', 'Bundle 2', '', '', 3, 15);

            // Spy on calculateTotalWithPorto
            const calculateTotalWithPortoSpy = jest.spyOn(SimpleShop, 'calculateTotalWithPorto');

            SimpleShop.updateBasketTotal();

            // Should update basket total display
            expect(SimpleShop.DomService.html).toHaveBeenCalledWith('#basket_total', expect.stringContaining('65'));
            // Should call calculateTotalWithPorto
            expect(calculateTotalWithPortoSpy).toHaveBeenCalled();

            calculateTotalWithPortoSpy.mockRestore();
        });

        test('should display 0 for empty basket', () => {
            SimpleShop.clearBasket();

            SimpleShop.updateBasketTotal();

            expect(SimpleShop.DomService.html).toHaveBeenCalledWith('#basket_total', expect.stringContaining('0'));
        });

        test('should format currency correctly', () => {
            // Ensure currency is set
            if (typeof shopConfig !== 'undefined') {
                shopConfig.currency = '€';
            }

            SimpleShop.addToBasket('item1', 'Item 1', 'bundle1', 'Bundle 1', '', '', 1, 10.50);

            SimpleShop.updateBasketTotal();

            // Should format with currency symbol (formatCurrency adds space + currency)
            const htmlCall = SimpleShop.DomService.html.mock.calls.find(call => call[0] === '#basket_total');
            expect(htmlCall).toBeDefined();
            // Currency is added as ' ' + currency, so check for the formatted price
            expect(htmlCall[1]).toMatch(/10,50/);
        });
    });
});

