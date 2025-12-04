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
            const mockEach = jest.fn();
            
            $.mockReturnValueOnce({ remove: mockRemove }); // For removing old fields
            $.mockReturnValueOnce({ each: mockEach, length: 0 }); // For option selects
            $.mockReturnValueOnce({ each: mockEach, length: 0 }); // For quantity selects

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
            const mockEach = jest.fn();
            
            $.mockReturnValueOnce({ remove: jest.fn() }); // For removing old fields
            $.mockReturnValueOnce({ each: mockEach, length: 0 }); // For option selects
            $.mockReturnValueOnce({ each: mockEach, length: 0 }); // For quantity selects
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
            const mockEach = jest.fn();
            const mockRemoveAttr = jest.fn();
            const mockData = jest.fn().mockReturnValue(null);
            const mockAttrForData = jest.fn().mockReturnValue('some-name');
            
            // Mock for removing old basket fields
            $.mockReturnValueOnce({ remove: jest.fn() });
            // Mock for option selects (each call)
            $.mockReturnValueOnce({ 
                each: mockEach,
                length: 0  // No option selects in test
            });
            // Mock for quantity selects (each call)
            $.mockReturnValueOnce({ 
                each: mockEach,
                length: 0  // No quantity selects in test
            });
            // Mock for creating input element
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
            const mockEach = jest.fn();
            const mockRemoveAttr = jest.fn();
            const mockData = jest.fn().mockReturnValue(null);
            const mockAttrForData = jest.fn().mockReturnValue('some-name');
            
            // Mock for removing old basket fields
            $.mockReturnValueOnce({ remove: jest.fn() });
            // Mock for option selects (each call)
            $.mockReturnValueOnce({ 
                each: mockEach,
                length: 0  // No option selects in test
            });
            // Mock for quantity selects (each call)
            $.mockReturnValueOnce({ 
                each: mockEach,
                length: 0  // No quantity selects in test
            });
            // Mock for creating input element
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
            const mockEach = jest.fn();
            
            // Mock for removing old basket fields
            $.mockReturnValueOnce({ remove: jest.fn() });
            // Mock for option selects (each call)
            $.mockReturnValueOnce({ 
                each: mockEach,
                length: 0  // No option selects in test
            });
            // Mock for quantity selects (each call)
            $.mockReturnValueOnce({ 
                each: mockEach,
                length: 0  // No quantity selects in test
            });

            FormService.generateBasketFields();

            // Should not call appendTo when basket is empty
            expect(mockAppendTo).not.toHaveBeenCalled();
        });

        test('should save original-name for option selects when it does not exist', () => {
            const mockAttr = jest.fn().mockReturnValue('option-name');
            const mockData = jest.fn().mockReturnValue(null); // original-name doesn't exist
            const mockRemoveAttr = jest.fn();
            const mockSelect = {
                data: mockData,
                attr: mockAttr,
                removeAttr: mockRemoveAttr
            };
            const mockEach = jest.fn((callback) => {
                if (typeof callback === 'function') {
                    callback.call(mockSelect);
                }
            });
            
            const mockOptionSelects = {
                each: mockEach,
                length: 1
            };
            const mockQuantitySelects = {
                each: jest.fn(),
                length: 0
            };
            
            $.mockImplementation((selector) => {
                if (selector === '.basket-field') {
                    return { remove: jest.fn() };
                }
                if (selector === '.option-select') {
                    return mockOptionSelects;
                }
                if (selector === '.quantity-select') {
                    return mockQuantitySelects;
                }
                if (selector === mockSelect) {
                    return mockSelect;
                }
                return { each: jest.fn(), length: 0 };
            });

            FormService.generateBasketFields();

            // Should save original-name when it doesn't exist
            expect(mockData).toHaveBeenCalledWith('original-name');
            expect(mockAttr).toHaveBeenCalledWith('name');
            expect(mockData).toHaveBeenCalledWith('original-name', 'option-name');
            expect(mockRemoveAttr).toHaveBeenCalledWith('name');
        });

        test('should save original-name for quantity selects when it does not exist', () => {
            const mockAttr = jest.fn().mockReturnValue('quantity-name');
            const mockData = jest.fn().mockReturnValue(null); // original-name doesn't exist
            const mockRemoveAttr = jest.fn();
            const mockSelect = {
                data: mockData,
                attr: mockAttr,
                removeAttr: mockRemoveAttr
            };
            const mockEach = jest.fn((callback) => {
                if (typeof callback === 'function') {
                    callback.call(mockSelect);
                }
            });
            
            const mockOptionSelects = {
                each: jest.fn(),
                length: 0
            };
            const mockQuantitySelects = {
                each: mockEach,
                length: 1
            };
            
            $.mockImplementation((selector) => {
                if (selector === '.basket-field') {
                    return { remove: jest.fn() };
                }
                if (selector === '.option-select') {
                    return mockOptionSelects;
                }
                if (selector === '.quantity-select') {
                    return mockQuantitySelects;
                }
                if (selector === mockSelect) {
                    return mockSelect;
                }
                return { each: jest.fn(), length: 0 };
            });

            FormService.generateBasketFields();

            // Should save original-name when it doesn't exist
            expect(mockData).toHaveBeenCalledWith('original-name');
            expect(mockAttr).toHaveBeenCalledWith('name');
            expect(mockData).toHaveBeenCalledWith('original-name', 'quantity-name');
            expect(mockRemoveAttr).toHaveBeenCalledWith('name');
        });
    });

    describe('restoreFormFields', () => {
        test('should restore original name for option selects when originalName exists', () => {
            const mockAttr = jest.fn();
            const mockData = jest.fn().mockReturnValue('original-option-name');
            const mockSelect = {
                data: mockData,
                attr: mockAttr
            };
            const mockEach = jest.fn((callback) => {
                if (typeof callback === 'function') {
                    // When callback is called, $(this) should return mockSelect
                    callback.call(mockSelect);
                }
            });
            
            const mockOptionSelects = {
                each: mockEach,
                length: 1
            };
            const mockQuantitySelects = {
                each: jest.fn(),
                length: 0
            };
            
            // Clear previous mocks
            jest.clearAllMocks();
            
            let callCount = 0;
            $.mockImplementation((selector) => {
                callCount++;
                if (selector === '.option-select') {
                    return mockOptionSelects;
                }
                if (selector === '.quantity-select') {
                    return mockQuantitySelects;
                }
                // When $(this) is called inside the callback, return mockSelect
                if (selector === mockSelect) {
                    return mockSelect;
                }
                return { each: jest.fn(), length: 0 };
            });

            FormService.restoreFormFields();

            expect(mockEach).toHaveBeenCalled();
            expect(mockData).toHaveBeenCalledWith('original-name');
            expect(mockAttr).toHaveBeenCalledWith('name', 'original-option-name');
        });

        test('should restore original name for quantity selects when originalName exists', () => {
            const mockAttr = jest.fn();
            const mockData = jest.fn().mockReturnValue('original-quantity-name');
            const mockSelect = {
                data: mockData,
                attr: mockAttr
            };
            const mockEach = jest.fn((callback) => {
                if (typeof callback === 'function') {
                    callback.call(mockSelect);
                }
            });
            
            const mockOptionSelects = {
                each: jest.fn(),
                length: 0
            };
            const mockQuantitySelects = {
                each: mockEach,
                length: 1
            };
            
            $.mockImplementation((selector) => {
                if (selector === '.option-select') {
                    return mockOptionSelects;
                }
                if (selector === '.quantity-select') {
                    return mockQuantitySelects;
                }
                // When $(this) is called inside the callback, return mockSelect
                if (selector === mockSelect) {
                    return mockSelect;
                }
                return { each: jest.fn(), length: 0 };
            });

            FormService.restoreFormFields();

            expect(mockData).toHaveBeenCalledWith('original-name');
            expect(mockAttr).toHaveBeenCalledWith('name', 'original-quantity-name');
        });

        test('should not restore name when originalName does not exist', () => {
            const mockAttr = jest.fn();
            const mockData = jest.fn().mockReturnValue(null);
            const mockSelect = {
                data: mockData,
                attr: mockAttr
            };
            const mockEach = jest.fn((callback) => {
                if (typeof callback === 'function') {
                    callback.call(mockSelect);
                }
            });
            
            const mockOptionSelects = {
                each: mockEach,
                length: 1
            };
            const mockQuantitySelects = {
                each: jest.fn(),
                length: 0
            };
            
            $.mockImplementation((selector) => {
                if (selector === '.option-select') {
                    return mockOptionSelects;
                }
                if (selector === '.quantity-select') {
                    return mockQuantitySelects;
                }
                // When $(this) is called inside the callback, return mockSelect
                if (selector === mockSelect) {
                    return mockSelect;
                }
                return { each: jest.fn(), length: 0 };
            });

            FormService.restoreFormFields();

            expect(mockData).toHaveBeenCalledWith('original-name');
            expect(mockAttr).not.toHaveBeenCalled();
        });
    });
});

