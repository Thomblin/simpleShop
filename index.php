<?php
include('autoload.php');

$config = new Config();
$db = new Db($config);
$items = new Items($db);

Translation::init($config);

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <title><?php echo t('site.title') ?></title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8"/>
    <script src="http://code.jquery.com/jquery-1.4.2.min.js"></script>
    <script type="text/javascript">

        function preview() {
            $.post('ajax.php', $('#ajax_form').serialize(), function (response) {
                if (response.error) {
                    alert(response.error);
                }
                else {
                    $('#mail_text').html('<pre>' + response.mail + '</pre>');
                    $('#main').hide();
                    $('#mail').show();

                    if(response.order) {
                        $('#order_hint').hide();
                        $('#order').show();
                    } else {
                        $('#order').hide();
                        $('#order_hint').show();
                    }
                }
            }, 'json');
        }

        function back() {
            $('#mail').hide();
            $('#main').show();
        }

        function send() {
            $.post('ajax.php?mail=1', $('#ajax_form').serialize(), function (response) {
                if (response.error) {
                    alert(response.error);
                }
                else {
                    $('#mail').hide();
                    $('#mail').hide();
                    $('#confirm').show();
                }
            }, 'json');
        }

        // Handle option selection
        function handleOptionChange() {
            var itemId = $(this).attr('data-item-id');
            var selectedOption = $(this).find('option:selected');

            // Only show quantity selector if an option is actually selected
            if (selectedOption.val() === '') {
                $('#quantity_' + itemId).hide();
                return;
            }

            var bundleId = selectedOption.attr('data-bundle-id');
            var price = parseFloat(selectedOption.attr('data-price'));
            var minCount = parseInt(selectedOption.attr('data-min-count'));
            var maxCount = parseInt(selectedOption.attr('data-max-count'));
            var inventory = parseInt(selectedOption.attr('data-inventory'));

            // Update quantity selector
            var quantitySelect = $('#quantity_' + itemId + ' select.quantity-select');
            var maxQuantity = Math.min(maxCount, inventory);

            // Clear and rebuild options
            quantitySelect.empty();
            quantitySelect.append('<option value="0">0 <?php echo t('times') ?></option>');

            for (var i = minCount; i <= maxQuantity; i++) {
                var optionPrice = (i * price).toFixed(2).replace('.', ',');
                var selectedAttr = (i === minCount) ? ' selected="selected"' : '';
                quantitySelect.append(
                    '<option value="' + i + '"' + selectedAttr + '>' + i + ' <?php echo t('times') ?> (' +
                    optionPrice + ' <?php echo Config::CURRENCY ?> )</option>'
                );
            }

            // Update the name attribute to match the bundle
            quantitySelect.attr('name', itemId + '[' + bundleId + ']');

            // Show the quantity selector
            $('#quantity_' + itemId).show();

            // Update price display
            updatePriceDisplay(itemId);

            // Trigger price calculation
            quantitySelect.trigger('change');
        }

        function updatePriceDisplay(itemId) {
            var quantitySelect = $('#quantity_' + itemId + ' select.quantity-select');
            var quantity = parseInt(quantitySelect.val());

            if (quantity > 0) {
                var optionText = quantitySelect.find('option:selected').text();
                $('#price_display_' + itemId).html(optionText);
            } else {
                $('#price_display_' + itemId).html('');
            }
        }

        // Basket management
        var basket = [];
        var basketCounter = 0;

        function addToBasket(itemId, itemName, bundleId, bundleName, quantity, price) {
            // Check if this bundle already exists in the basket
            var existingItem = null;
            for (var i = 0; i < basket.length; i++) {
                if (basket[i].itemId === itemId && basket[i].bundleId === bundleId) {
                    existingItem = basket[i];
                    break;
                }
            }

            if (existingItem) {
                // Update existing item quantity
                existingItem.quantity += quantity;
                existingItem.totalPrice = existingItem.quantity * existingItem.price;
            } else {
                // Add new item to basket
                var basketItem = {
                    id: basketCounter++,
                    itemId: itemId,
                    itemName: itemName,
                    bundleId: bundleId,
                    bundleName: bundleName,
                    quantity: quantity,
                    price: price,
                    totalPrice: quantity * price
                };
                basket.push(basketItem);
            }

            updateBasketDisplay();
            updateBasketTotal();
        }

        function removeFromBasket(basketItemId) {
            basket = basket.filter(function(item) {
                return item.id !== basketItemId;
            });
            updateBasketDisplay();
            updateBasketTotal();
        }

        function updateBasketDisplay() {
            var basketHtml = '';

            if (basket.length === 0) {
                $('#basket_display').hide();
                return;
            }

            basket.forEach(function(item) {
                var itemTotal = item.totalPrice.toFixed(2).replace('.', ',');
                basketHtml += '<div style="padding: 5px; border-bottom: 1px solid #ccc;">';
                basketHtml += '<strong>' + item.itemName + '</strong> - ' + item.bundleName;
                basketHtml += '<br/>' + item.quantity + ' <?php echo t('times') ?> × ' + item.price.toFixed(2).replace('.', ',') + ' <?php echo Config::CURRENCY ?>';
                basketHtml += ' = <strong>' + itemTotal + ' <?php echo Config::CURRENCY ?></strong>';
                basketHtml += ' <a href="javascript:void(0);" class="remove-basket-item" data-basket-id="' + item.id + '" style="margin-left: 10px; color: #880000;">[<?php echo t('remove') ?>]</a>';
                basketHtml += '</div>';
            });

            $('#basket_items').html(basketHtml);
            $('#basket_display').show();

            // Generate hidden form fields
            generateBasketFormFields();
        }

        function updateBasketTotal() {
            var total = 0;
            basket.forEach(function(item) {
                total += item.totalPrice;
            });
            $('#basket_total').html(total.toFixed(2).replace('.', ',') + ' <?php echo Config::CURRENCY ?>');

            // Trigger price recalculation
            calculateTotalWithPorto();
        }

        function generateBasketFormFields() {
            // Remove old basket fields
            $('.basket-field').remove();

            // Add new fields for each basket item
            basket.forEach(function(item) {
                var fieldName = item.itemId + '[' + item.bundleId + ']';
                $('<input>').attr({
                    type: 'hidden',
                    name: fieldName,
                    value: item.quantity,
                    class: 'basket-field price'
                }).appendTo('#ajax_form');
            });
        }

        function calculateTotalWithPorto() {
            $.post('ajax.php?price_only=1', $('#ajax_form').serialize(), function (response) {
                $('#porto').html(response.porto);
                $('#total').html(response.price);
            }, 'json');
        }

        $(document).ready(function() {
            // Set up event handlers first
            $("body").delegate(".option-select", "change", handleOptionChange);

            $("body").delegate(".quantity-select", "change", function () {
                var itemId = $(this).attr('data-item-id');
                updatePriceDisplay(itemId);
            });

            $("body").delegate(".price", "change", calculateTotalWithPorto);

            // Handle add to basket button
            $("body").delegate(".add-to-basket-btn", "click", function() {
                var itemId = $(this).attr('data-item-id');
                var itemName = $('.item[data-item-id="' + itemId + '"] .head').text();

                // Get selected option
                var selectedOption = $('.item[data-item-id="' + itemId + '"] .option-select option:selected');
                var bundleId = selectedOption.attr('data-bundle-id');
                var bundleName = selectedOption.text();
                var price = parseFloat(selectedOption.attr('data-price'));

                // Get quantity
                var quantity = parseInt($('#quantity_' + itemId + ' .quantity-select').val());

                if (!bundleId || quantity <= 0) {
                    alert('<?php echo t('error.fill_required') ?>');
                    return;
                }

                addToBasket(itemId, itemName, bundleId, bundleName, quantity, price);
            });

            // Handle remove from basket
            $("body").delegate(".remove-basket-item", "click", function() {
                var basketId = parseInt($(this).attr('data-basket-id'));
                removeFromBasket(basketId);
            });

            // Now auto-select first option for each item
            $('.option-select').each(function() {
                var firstOption = $(this).find('option[value!=""]').first();
                if (firstOption.length) {
                    $(this).val(firstOption.val());
                    handleOptionChange.call(this);
                }
            });
        });

    </script>
    <style type="text/css">
        body {
            background-color: #fff;
            background-image: url();
            background-repeat: repeat-y;
            font-family: georgia, times new roman, times, serif;
        }

        div.main {
            width: 600px;
            margin-left: 200px;
            margin-top: 75px;
            margin-bottom: 75px;
        }

        span.head {
            font-weight: bold;
        }

        div.item {
            border: 2px #880000 solid;
            margin: 5px 5px;
            padding: 5px 5px;
            background: url() #ddd;
        }

        span.description {
            font-style: italic;
        }

        div.picture {
            float: right;
        }

        div.footer, div.footer a, div.footer a:hover, div.footer a:visited {
            position: relative;
            bottom: 0px;
            border-top: 1px solid #ccc;
            font-size: 0.9em;
            font-style: italic;
            color: #ccc;
        }

        div.intro {
            position: relative;
            bottom: 10px;
            font-size: 1.2em;
            color: #ccc;
        }
    </style>
</head>
<body>
<form id="ajax_form">
    <div align="center"><a href="../index.php"><img src="" height="162" alt="<?php echo t('site.title') ?>"/></a>
    </div>
    <br>

    <div class="main" id="main">
        <div class="intro"><img src="" align="left"><strong><?php echo t('site.welcome') ?></strong>
        </div>
        <div class="item">
            <span class="head"><?php echo t('site.contact') ?></span><br/>

            <label><?php echo t('name') ?>:<br/><input type="text" name="name"/></label>
            <br/>
            <label><?php echo t('email') ?>*:<br/><input type="text" name="email"/></label>
            <br/>
            <label><?php echo t('street') ?>*:<br/><input type="text" name="street"/></label>
            <br/>
            <label><?php echo t('zip_city') ?>*:<br/><input type="text" name="zipcode_location"/></label>
        </div>

        <!-- Basket Display -->
        <div class="item" id="basket_display" style="display:none;">
            <span class="head"><?php echo t('basket') ?></span><br/>
            <div id="basket_items"></div>
            <div style="margin-top: 10px;">
                <strong><?php echo t('basket_total') ?>:</strong> <span id="basket_total">0 <?php echo Config::CURRENCY; ?></span>
            </div>
        </div>

        <?php foreach ($items->getItems() as $item): ?>
        <div class="item" data-item-id="<?php echo $item['item_id'] ?>">
            <div>
                <br/>
                <span class="head"><?php echo $item['name'] ?></span>
            </div>

            <div style="float:left">
                <?php foreach ($item['option_groups'] as $optionGroup): ?>
                <label><?php echo $optionGroup['group_name'] ?>:<br/>
                    <select name="item_<?php echo $item['item_id'] ?>_option_<?php echo $optionGroup['group_id'] ?>"
                            class="option-select"
                            data-item-id="<?php echo $item['item_id'] ?>"
                            data-group-id="<?php echo $optionGroup['group_id'] ?>">
                        <option value="">-- <?php echo t('select') ?> <?php echo $optionGroup['group_name'] ?> --</option>
                        <?php foreach ($optionGroup['options'] as $option): ?>
                        <option value="<?php echo $option['option_id'] ?>"
                                data-bundle-id="<?php echo $option['bundle_id'] ?>"
                                data-price="<?php echo $option['price'] ?>"
                                data-min-count="<?php echo $option['min_count'] ?>"
                                data-max-count="<?php echo $option['max_count'] ?>"
                                data-inventory="<?php echo $option['inventory'] ?>">
                            <?php echo !empty($option['option_description']) ? $option['option_description'] : $option['option_name'] ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </label>
                <br/>
                <?php endforeach; ?>

                <!-- Quantity selector (shown after option selection) -->
                <div id="quantity_<?php echo $item['item_id'] ?>" style="display:none; margin-top: 10px;">
                    <label><?php echo t('quantity') ?>:<br/>
                        <select name="quantity_placeholder" class="quantity-select" data-item-id="<?php echo $item['item_id'] ?>">
                            <option value="0">0 <?php echo t('times') ?></option>
                        </select>
                    </label>
                    <br/>
                    <span id="price_display_<?php echo $item['item_id'] ?>" style="font-weight: bold;"></span>
                    <br/><br/>
                    <button type="button" class="add-to-basket-btn" data-item-id="<?php echo $item['item_id'] ?>"><?php echo t('add_to_basket') ?></button>
                </div>

                <br/>
                <?php echo t('porto') ?> (<?php echo t('min') ?> <?php echo number_format($item['min_porto'], 2, ',', '.') . ' ' . Config::CURRENCY ?> )
            </div>

            <?php if (!empty($item['picture'])): ?>
            <div class="picture"><img src="<?php echo $item['picture'] ?>" alt="<?php echo $item['name'] ?>"
                                      title="<?php echo $item['name'] ?>"/></div>
            <?php endif; ?>
            <div style="clear:both"></div>

            <div>
                <br/>
                <span class="description"><?php echo $item['description'] ?></span>
            </div>

        </div>
        <?php endforeach; ?>
        <div class="item">
            <span class="head"><?php echo t('form.comments') ?></span><br/>

            <textarea type="text" name="comment"/></textarea>
            <br/>
            <input type="checkbox" name="collectionByTheCustomer" class="price"/> <?php echo t('form.will_collect_no_porto') ?>
        </div>
        <div class="item">
            <span class="head"><?php echo t('total') ?></span><br/>

            <?php echo t('porto') ?>: <span id="porto">0 <?php echo Config::CURRENCY; ?></span>
            <?php echo t('sum') ?>: <span id="total">0 <?php echo Config::CURRENCY; ?></span>
            <br/>
            <br/>
            <a href="javascript:;" onclick="preview();"><?php echo t('preview') ?></a>
            <br/>
        </div>
    </div>
    <div class="main" id="mail" style="display:none">
        <div class="item" id="mail_text"></div>

        <div class="item">
            <div>
                <a href="javascript:;" onclick="back();"><?php echo t('back') ?></a>

                <a href="javascript:;" onclick="send();" id="order" style="margin-left: 50px;"><?php echo t('order') ?></a>
            </div>
            <div id="order_hint" style="padding-top: 5px;">
                <?php echo t('change_selection') ?>
            </div>
        </div>
    </div>
    <div class="main" id="confirm" style="display:none">
        <div class="item">
            <?php echo t('form.success_message') ?>

        </div>
    </div>
    <div class="footer">
        <a href="" target="_top"><?php echo t('site.hints_imprint') ?></a> &middot; <?php echo t('form.developed_by') ?> <a href="http://sebastian-detert.de">Seeb</a> &middot; <?php echo t('form.designed_by') ?> Wozilla
    </div>
</form>
</body>
</html>
