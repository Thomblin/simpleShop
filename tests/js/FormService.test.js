/**
 * Unit tests for FormService
 */

const SimpleShop = require('../../js/main.js');

describe('FormService', () => {
    let FormService;

    beforeEach(() => {
        FormService = SimpleShop.FormService;
        expect(FormService).toBeDefined();
        SimpleShop.clearBasket();
        jest.clearAllMocks();
    });

    describe('generateBasketFields', () => {
        test('should remove existing basket fields', () => {
            const mockRemove = jest.fn();
            $.mockReturnValueOnce({ remove: mockRemove });

            FormService.generateBasketFields();

            expect($).toHaveBeenCalledWith('.basket-field');
            expect(mockRemove).toHaveBeenCalled();
        });

        test('should create hidden input fields for each basket item', () => {
            // Add items to basket
            SimpleShop.BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle 1', '', '', 2, 10);
            SimpleShop.BasketLogic.addItem('item2', 'Item 2', 'bundle2', 'Bundle 2', 'option1', 'Option 1', 1, 15);

            // Mock jQuery to return chainable objects
            const mockAppendTo = jest.fn();
            const mockElement = {
                attr: jest.fn().mockReturnThis(),
                appendTo: mockAppendTo
            };
            
            $.mockReturnValueOnce({ remove: jest.fn() }); // For removing old fields
            $.mockReturnValue(mockElement); // For creating input elements

            FormService.generateBasketFields();

            // Should create input for each basket item (2 items)
            expect($).toHaveBeenCalled();
            expect(mockAppendTo).toHaveBeenCalledWith('#ajax_form');
        });

        test('should build correct field names without bundleOptionId', () => {
            SimpleShop.BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle 1', '', '', 1, 10);

            const mockAppendTo = jest.fn();
            const mockAttr = jest.fn().mockReturnValue({ appendTo: mockAppendTo });
            $.mockReturnValueOnce({ remove: jest.fn() });
            $.mockImplementationOnce(() => ({ attr: mockAttr }));

            FormService.generateBasketFields();

            expect(mockAttr).toHaveBeenCalledWith(
                expect.objectContaining({
                    name: 'item1[bundle1]',
                    value: 1
                })
            );
        });

        test('should build correct field names with bundleOptionId', () => {
            SimpleShop.BasketLogic.addItem('item1', 'Item 1', 'bundle1', 'Bundle 1', 'option1', 'Option 1', 2, 10);

            const mockAppendTo = jest.fn();
            const mockAttr = jest.fn().mockReturnValue({ appendTo: mockAppendTo });
            $.mockReturnValueOnce({ remove: jest.fn() });
            $.mockImplementationOnce(() => ({ attr: mockAttr }));

            FormService.generateBasketFields();

            expect(mockAttr).toHaveBeenCalledWith(
                expect.objectContaining({
                    name: 'item1[bundle1][option1]',
                    value: 2
                })
            );
        });

        test('should not create fields when basket is empty', () => {
            const mockAppendTo = jest.fn();
            $.mockReturnValueOnce({ remove: jest.fn() });

            FormService.generateBasketFields();

            // Should not call appendTo when basket is empty
            expect(mockAppendTo).not.toHaveBeenCalled();
        });
    });
});

