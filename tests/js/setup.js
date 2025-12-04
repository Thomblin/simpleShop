/**
 * Jest setup file for JavaScript tests
 * This file runs before each test file
 */

// Mock jQuery if not available
if (typeof $ === 'undefined') {
    const createMockElement = () => {
        const mockElement = {
            hide: jest.fn().mockReturnThis(),
            show: jest.fn().mockReturnThis(),
            html: jest.fn(function(content) {
                if (content !== undefined) {
                    return this;
                }
                return '';
            }),
            text: jest.fn(function(value) {
                if (value !== undefined) {
                    return this;
                }
                return '';
            }),
            val: jest.fn(function(value) {
                if (value !== undefined) {
                    return this;
                }
                return '';
            }),
            attr: jest.fn(function(attr, value) {
                if (value !== undefined) {
                    return this;
                }
                return '';
            }),
            find: jest.fn().mockReturnThis(),
            empty: jest.fn().mockReturnThis(),
            append: jest.fn().mockReturnThis(),
            remove: jest.fn().mockReturnThis(),
            appendTo: jest.fn().mockReturnThis(),
            stop: jest.fn().mockReturnThis(),
            css: jest.fn().mockReturnThis(),
            animate: jest.fn().mockReturnThis(),
            fadeIn: jest.fn().mockReturnThis(),
            fadeOut: jest.fn().mockReturnThis(),
            serialize: jest.fn().mockReturnValue(''),
            delegate: jest.fn().mockReturnThis(),
            each: jest.fn(function(callback) {
                // Mock each to call callback with a mock element
                if (typeof callback === 'function') {
                    callback.call(createMockElement());
                }
                return this;
            }),
            first: jest.fn().mockReturnThis(),
            length: 1
        };
        // Ensure attr().appendTo() works (chaining)
        return mockElement;
    };

    const mockJQuery = jest.fn((selector) => {
        // Special handling for document
        if (selector === document || selector === global.document) {
            return {
                ready: jest.fn((callback) => {
                    // Don't execute in test environment, but don't throw
                    // This allows the code to load without errors
                })
            };
        }
        // Special handling for creating elements (e.g., $('<input>'))
        if (typeof selector === 'string' && selector.startsWith('<')) {
            return createMockElement();
        }
        // Default: return a mock element
        return createMockElement();
    });

    // Add post method
    mockJQuery.post = jest.fn((url, data, callback, dataType) => {
        // Default mock implementation
        if (typeof callback === 'function') {
            callback({});
        }
    });

    global.$ = mockJQuery;
}

// Mock shopConfig if not defined
if (typeof shopConfig === 'undefined') {
    global.shopConfig = {
        showInventory: false,
        currency: '€',
        translations: {
            times: 'times',
            remove: 'Remove',
            errorFillRequired: 'Please fill all required fields'
        }
    };
}
