/**
 * SimpleShop JavaScript Module
 * Refactored for unit testing - exposes functions and state for testing
 */

// Configuration object - populated by inline script in index.php before this file loads
// If shopConfig is not defined, create it with defaults (for testing/standalone use)
if (typeof shopConfig === 'undefined') {
    var shopConfig = {
        showInventory: false,
        currency: '',
        translations: {
            times: '',
            remove: '',
            errorFillRequired: ''
        }
    };
}

// Main module namespace
var SimpleShop = (function () {
    'use strict';

    // ============================================================================
    // State Management
    // ============================================================================
    var basket = [];
    var basketCounter = 0;

    // ============================================================================
    // API Service (abstracted for testing)
    // ============================================================================
    var ApiService = {
        post: function (url, data, callback, dataType) {
            if (typeof $ !== 'undefined' && $.post) {
                $.post(url, data, callback, dataType);
            } else {
                // Fallback for testing without jQuery
                throw new Error('jQuery not available');
            }
        },

        serializeForm: function (formSelector) {
            if (typeof $ !== 'undefined' && $(formSelector).serialize) {
                return $(formSelector).serialize();
            }
            return '';
        }
    };

    // ============================================================================
    // DOM Service (abstracted for testing)
    // ============================================================================
    var DomService = {
        get: function (selector) {
            if (typeof $ !== 'undefined') {
                return $(selector);
            }
            return null;
        },

        hide: function (selector) {
            var $el = this.get(selector);
            if ($el && $el.hide) {
                $el.hide();
            }
        },

        show: function (selector) {
            var $el = this.get(selector);
            if ($el && $el.show) {
                $el.show();
            }
        },

        html: function (selector, content) {
            var $el = this.get(selector);
            if ($el && $el.html) {
                if (content !== undefined) {
                    $el.html(content);
                } else {
                    return $el.html();
                }
            }
        },

        text: function (selector) {
            var $el = this.get(selector);
            if ($el && $el.text) {
                return $el.text();
            }
            return '';
        },

        val: function (selector) {
            var $el = this.get(selector);
            if ($el && $el.val) {
                return $el.val();
            }
            return '';
        },

        attr: function (selector, attribute, value) {
            var $el = this.get(selector);
            if ($el && $el.attr) {
                if (value !== undefined) {
                    $el.attr(attribute, value);
                } else {
                    return $el.attr(attribute);
                }
            }
        }
    };

    // ============================================================================
    // Utility Functions
    // ============================================================================
    var Utils = {
        formatPrice: function (price) {
            return price.toFixed(2).replace('.', ',');
        },

        formatCurrency: function (amount) {
            return this.formatPrice(amount) + ' ' + shopConfig.currency;
        },

        buildFieldName: function (itemId, bundleId, bundleOptionId) {
            if (bundleOptionId && bundleOptionId !== '') {
                return itemId + '[' + bundleId + '][' + bundleOptionId + ']';
            }
            return itemId + '[' + bundleId + ']';
        },

        findBasketItem: function (itemId, bundleId, bundleOptionId) {
            for (var i = 0; i < basket.length; i++) {
                if (basket[i].itemId === itemId &&
                    basket[i].bundleId === bundleId &&
                    basket[i].bundleOptionId === bundleOptionId) {
                    return basket[i];
                }
            }
            return null;
        }
    };

    // ============================================================================
    // Basket Business Logic (pure functions)
    // ============================================================================
    var BasketLogic = {
        createBasketItem: function (itemId, itemName, bundleId, bundleName, bundleOptionId, optionLabel, quantity, price) {
            return {
                id: basketCounter++,
                itemId: itemId,
                itemName: itemName,
                bundleId: bundleId,
                bundleName: bundleName,
                bundleOptionId: bundleOptionId,
                optionLabel: optionLabel,
                quantity: quantity,
                price: price,
                totalPrice: quantity * price
            };
        },

        addItem: function (itemId, itemName, bundleId, bundleName, bundleOptionId, optionLabel, quantity, price) {
            var existingItem = Utils.findBasketItem(itemId, bundleId, bundleOptionId);

            if (existingItem) {
                existingItem.quantity += quantity;
                existingItem.totalPrice = existingItem.quantity * existingItem.price;
            } else {
                var basketItem = this.createBasketItem(
                    itemId, itemName, bundleId, bundleName, bundleOptionId, optionLabel, quantity, price
                );
                basket.push(basketItem);
            }

            return basket;
        },

        removeItem: function (basketItemId) {
            basket = basket.filter(function (item) {
                return item.id !== basketItemId;
            });
            return basket;
        },

        calculateTotal: function () {
            var total = 0;
            basket.forEach(function (item) {
                total += item.totalPrice;
            });
            return total;
        },

        getBasket: function () {
            return basket.slice(); // Return copy to prevent external mutation
        },

        clearBasket: function () {
            basket = [];
            basketCounter = 0;
        }
    };

    // ============================================================================
    // Basket Display Logic
    // ============================================================================
    var BasketDisplay = {
        generateItemHtml: function (item) {
            var itemTotal = Utils.formatPrice(item.totalPrice);
            var html = '<div style="padding: 5px; border-bottom: 1px solid #ccc;">';

            var label = '<strong>' + item.itemName + '</strong> - ' + item.bundleName;
            if (item.optionLabel) {
                label += ' - ' + item.optionLabel;
            }
            html += label;

            html += '<br/>' + item.quantity + ' ' + shopConfig.translations.times + ' × ' +
                Utils.formatPrice(item.price) + ' ' + shopConfig.currency;
            html += ' = <strong>' + itemTotal + ' ' + shopConfig.currency + '</strong>';
            html += ' <a href="javascript:void(0);" class="remove-basket-item" data-basket-id="' + item.id +
                '" style="margin-left: 10px; color: #880000;">[' + shopConfig.translations.remove + ']</a>';
            html += '</div>';

            return html;
        },

        render: function () {
            if (basket.length === 0) {
                DomService.hide('#basket_display');
                return;
            }

            var basketHtml = '';
            basket.forEach(function (item) {
                basketHtml += BasketDisplay.generateItemHtml(item);
            });

            DomService.html('#basket_items', basketHtml);
            DomService.show('#basket_display');

            // Highlight basket briefly
            var $basket = DomService.get('#basket_display');
            if ($basket && $basket.stop && $basket.css && $basket.animate) {
                $basket.stop().css('background-color', '#e8f5e9').animate({
                    backgroundColor: '#ddd'
                }, 800);
            }
        }
    };

    // ============================================================================
    // Form Management
    // ============================================================================
    var FormService = {
        generateBasketFields: function () {
            // Remove old basket fields
            if (typeof $ !== 'undefined') {
                $('.basket-field').remove();
            }

            // Disable visible option selects and quantity selects to prevent interference
            // Only basket fields should be submitted, not the visible form controls
            // We disable them (instead of clearing) to preserve UI state if user goes back
            if (typeof $ !== 'undefined') {
                // Temporarily remove names from option selects so they're not serialized
                var $optionSelects = $('.option-select');
                if ($optionSelects && typeof $optionSelects.each === 'function') {
                    $optionSelects.each(function() {
                        var $select = $(this);
                        if ($select && typeof $select.data === 'function' && typeof $select.attr === 'function') {
                            if (!$select.data('original-name')) {
                                $select.data('original-name', $select.attr('name'));
                            }
                            if (typeof $select.removeAttr === 'function') {
                                $select.removeAttr('name');
                            }
                        }
                    });
                }
                // Temporarily remove names from quantity selects so they're not serialized
                var $quantitySelects = $('.quantity-select');
                if ($quantitySelects && typeof $quantitySelects.each === 'function') {
                    $quantitySelects.each(function() {
                        var $select = $(this);
                        if ($select && typeof $select.data === 'function' && typeof $select.attr === 'function') {
                            if (!$select.data('original-name')) {
                                $select.data('original-name', $select.attr('name'));
                            }
                            if (typeof $select.removeAttr === 'function') {
                                $select.removeAttr('name');
                            }
                        }
                    });
                }
            }

            // Add new fields for each basket item
            if (typeof $ !== 'undefined') {
                basket.forEach(function (item) {
                    var fieldName = Utils.buildFieldName(item.itemId, item.bundleId, item.bundleOptionId);
                    $('<input>').attr({
                        type: 'hidden',
                        name: fieldName,
                        value: item.quantity,
                        class: 'basket-field price'
                    }).appendTo('#ajax_form');
                });
            }
        },
        
        restoreFormFields: function () {
            // Restore names to option and quantity selects after form submission
            // This allows the form to work normally if user goes back
            if (typeof $ !== 'undefined') {
                var $optionSelects = $('.option-select');
                if ($optionSelects && typeof $optionSelects.each === 'function') {
                    $optionSelects.each(function() {
                        var $select = $(this);
                        if ($select && typeof $select.data === 'function' && typeof $select.attr === 'function') {
                            var originalName = $select.data('original-name');
                            if (originalName) {
                                $select.attr('name', originalName);
                            }
                        }
                    });
                }
                var $quantitySelects = $('.quantity-select');
                if ($quantitySelects && typeof $quantitySelects.each === 'function') {
                    $quantitySelects.each(function() {
                        var $select = $(this);
                        if ($select && typeof $select.data === 'function' && typeof $select.attr === 'function') {
                            var originalName = $select.data('original-name');
                            if (originalName) {
                                $select.attr('name', originalName);
                            }
                        }
                    });
                }
            }
        }
    };

    // ============================================================================
    // Option Selection Logic
    // ============================================================================
    var OptionHandler = {
        parseOptionData: function ($selectElement) {
            var selectedOption = $selectElement.find('option:selected');

            if (selectedOption.val() === '') {
                return null;
            }

            return {
                bundleId: selectedOption.attr('data-bundle-id'),
                bundleOptionId: selectedOption.attr('data-bundle-option-id'),
                price: parseFloat(selectedOption.attr('data-price')),
                minCount: parseInt(selectedOption.attr('data-min-count')),
                maxCount: parseInt(selectedOption.attr('data-max-count')),
                inventory: parseInt(selectedOption.attr('data-inventory'))
            };
        },

        buildQuantityOptions: function (minCount, maxQuantity, price) {
            var options = [];
            options.push('<option value="0">0 ' + shopConfig.translations.times + '</option>');

            for (var i = minCount; i <= maxQuantity; i++) {
                var optionPrice = Utils.formatPrice(i * price);
                var selectedAttr = (i === minCount) ? ' selected="selected"' : '';
                options.push(
                    '<option value="' + i + '"' + selectedAttr + '>' + i + ' ' +
                    shopConfig.translations.times + ' (' + optionPrice + ' ' + shopConfig.currency + ' )</option>'
                );
            }

            return options.join('');
        },

        handleChange: function ($selectElement) {
            var itemId = $selectElement.attr('data-item-id');
            var optionData = this.parseOptionData($selectElement);

            if (!optionData) {
                DomService.hide('#quantity_' + itemId);
                if (shopConfig.showInventory) {
                    DomService.html('#inventory_display_' + itemId, '');
                }
                return;
            }

            var quantitySelect = DomService.get('#quantity_' + itemId + ' select.quantity-select');
            if (!quantitySelect || !quantitySelect.length) {
                return;
            }

            var maxQuantity = Math.min(optionData.maxCount, optionData.inventory);
            var optionsHtml = this.buildQuantityOptions(optionData.minCount, maxQuantity, optionData.price);

            quantitySelect.empty();
            quantitySelect.append(optionsHtml);

            var fieldName = Utils.buildFieldName(itemId, optionData.bundleId, optionData.bundleOptionId);
            quantitySelect.attr('name', fieldName);

            DomService.show('#quantity_' + itemId);

            if (shopConfig.showInventory) {
                SimpleShop.updateInventoryDisplay(itemId, optionData.inventory);
            }
        }
    };

    // ============================================================================
    // Public API Functions
    // ============================================================================
    return {
        // Configuration
        getConfig: function () {
            return shopConfig;
        },

        // Basket operations
        addToBasket: function (itemId, itemName, bundleId, bundleName, bundleOptionId, optionLabel, quantity, price) {
            BasketLogic.addItem(itemId, itemName, bundleId, bundleName, bundleOptionId, optionLabel, quantity, price);
            BasketDisplay.render();
            this.updateBasketTotal();
        },

        removeFromBasket: function (basketItemId) {
            BasketLogic.removeItem(basketItemId);
            BasketDisplay.render();
            this.updateBasketTotal();
        },

        getBasket: function () {
            return BasketLogic.getBasket();
        },

        clearBasket: function () {
            BasketLogic.clearBasket();
        },

        calculateBasketTotal: function () {
            return BasketLogic.calculateTotal();
        },

        updateBasketDisplay: function () {
            BasketDisplay.render();
            FormService.generateBasketFields();
        },

        updateBasketTotal: function () {
            var total = BasketLogic.calculateTotal();
            DomService.html('#basket_total', Utils.formatCurrency(total));
            this.calculateTotalWithPorto();
        },

        // Preview and mail functions
        preview: function () {
            // Ensure basket fields are generated before preview
            // This also clears visible option/quantity selects to prevent interference
            FormService.generateBasketFields();
            
            var formData = ApiService.serializeForm('#ajax_form');
            ApiService.post('ajax.php', formData, function (response) {
                if (response.error) {
                    alert(response.error);
                } else {
                    DomService.html('#mail_text', '<pre>' + response.mail + '</pre>');
                    DomService.hide('#main');
                    DomService.show('#mail');

                    if (response.order) {
                        DomService.hide('#order_hint');
                        DomService.show('#order');
                    } else {
                        DomService.hide('#order');
                        DomService.show('#order_hint');
                    }
                }
            }, 'json');
        },

        back: function () {
            // Restore form field names when going back
            FormService.restoreFormFields();
            DomService.hide('#mail');
            DomService.show('#main');
        },

        send: function () {
            // Ensure basket fields are generated before sending order
            FormService.generateBasketFields();
            
            var formData = ApiService.serializeForm('#ajax_form');
            ApiService.post('ajax.php?mail=1', formData, function (response) {
                if (response.error) {
                    alert(response.error);
                } else {
                    DomService.hide('#mail');
                    DomService.show('#confirm');
                }
            }, 'json');
        },

        // Option handling
        handleOptionChange: function ($selectElement) {
            OptionHandler.handleChange($selectElement);
        },

        updateInventoryDisplay: function (itemId, inventory) {
            DomService.html('#inventory_display_' + itemId, 'Inventory: ' + inventory);
        },

        // Price calculation
        calculateTotalWithPorto: function () {
            var formData = ApiService.serializeForm('#ajax_form');
            ApiService.post('ajax.php?price_only=1', formData, function (response) {
                DomService.html('#porto', response.porto);
                DomService.html('#total', response.price);
            }, 'json');
        },

        // Success message
        showSuccessMessage: function (itemId) {
            var $successMsg = DomService.get('#success_' + itemId);
            if ($successMsg && $successMsg.fadeIn && $successMsg.fadeOut) {
                $successMsg.fadeIn(200);
                setTimeout(function () {
                    $successMsg.fadeOut(1000);
                }, 1000);
            }
        },

        // Utility functions (exposed for testing)
        Utils: Utils,
        BasketLogic: BasketLogic,
        BasketDisplay: BasketDisplay,
        OptionHandler: OptionHandler,
        FormService: FormService,

        // Services (exposed for testing/mocking)
        ApiService: ApiService,
        DomService: DomService
    };
})();

// ============================================================================
// Global Functions (for backward compatibility with HTML onclick handlers)
// ============================================================================
function preview() {
    SimpleShop.preview();
}

function back() {
    SimpleShop.back();
}

function send() {
    SimpleShop.send();
}

// ============================================================================
// Event Handlers Setup
// ============================================================================
$(document).ready(function () {
    // Set up event handlers
    $("body").delegate(".option-select", "change", function () {
        SimpleShop.handleOptionChange($(this));
    });

    $("body").delegate(".price", "change", function () {
        SimpleShop.calculateTotalWithPorto();
    });

    // Handle add to basket button
    $("body").delegate(".add-to-basket-btn", "click", function () {
        var $button = $(this);
        var itemId = $button.attr('data-item-id');
        var itemName = $('.item[data-item-id="' + itemId + '"] .head').text();

        // Get selected option
        var selectedOption = $('.item[data-item-id="' + itemId + '"] .option-select option:selected');
        var bundleId = selectedOption.attr('data-bundle-id');
        var bundleOptionId = selectedOption.attr('data-bundle-option-id');
        var optionLabel = selectedOption.text();
        var bundleName = optionLabel;
        var price = parseFloat(selectedOption.attr('data-price'));

        // Get quantity
        var quantity = parseInt($('#quantity_' + itemId + ' .quantity-select').val());

        if (!bundleId || quantity <= 0) {
            alert(shopConfig.translations.errorFillRequired);
            return;
        }

        SimpleShop.addToBasket(itemId, itemName, bundleId, bundleName, bundleOptionId, optionLabel, quantity, price);
        SimpleShop.showSuccessMessage(itemId);
    });

    // Handle remove from basket
    $("body").delegate(".remove-basket-item", "click", function () {
        var basketId = parseInt($(this).attr('data-basket-id'));
        SimpleShop.removeFromBasket(basketId);
    });

    // Auto-select first option for each item
    $('.option-select').each(function () {
        var $select = $(this);
        var firstOption = $select.find('option[value!=""]').first();
        if (firstOption.length) {
            $select.val(firstOption.val());
            SimpleShop.handleOptionChange($select);
        }
    });
});

// Make SimpleShop available in Node.js environment for testing
if (typeof module !== 'undefined' && module.exports) {
    module.exports = SimpleShop;
    global.SimpleShop = SimpleShop;
}
