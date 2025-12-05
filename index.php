<?php
/**
 * Main entry point that renders the shop interface with product listings, basket functionality, and order form.
 */
include('autoload.php');

$config = new Config();
$db = new Db($config);
$items = new Items($db);

// Initialize translation singleton for t() function
$translationLoader = new TranslationLoader();
$translation = new Translation($translationLoader, $config->getLanguage());
Translation::setInstance($translation);

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <title><?php echo t('site.title') ?></title>
    <meta http-equiv="content-type" content="text/html; charset=UTF-8" />
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link rel="icon" type="image/svg+xml" href="favicon.svg" />
    <link rel="stylesheet" href="css/style.css" />
    <script src="http://code.jquery.com/jquery-1.4.2.min.js"></script>
    <script type="text/javascript">
        // Configuration passed from PHP - must be defined before main.js loads
        var shopConfig = {
            showInventory: <?php echo $config->showInventory ? 'true' : 'false'; ?>,
            currency: '<?php echo Config::CURRENCY ?>',
            translations: {
                times: '<?php echo t('times') ?>',
                remove: '<?php echo t('remove') ?>',
                errorFillRequired: '<?php echo t('error.fill_required') ?>',
                errorSpecifyQuantity: '<?php echo t('error.specify_quantity') ?>',
                addedToBasket: '<?php echo t('added_to_basket') ?>',
                updatedToBasket: '<?php echo t('updated_to_basket') ?>'
            }
        };
    </script>
    <script src="js/main.js"></script>
</head>

<body>
    <form id="ajax_form">
        <div align="center"><a href="../index.php"><img src="" height="162" alt="<?php echo t('site.title') ?>" /></a>
        </div>
        <br>

        <div class="main" id="main">
            <div class="intro"><img src="" align="left"><strong><?php echo t('site.welcome') ?></strong>
            </div>
            <div class="item">
                <span class="head"><?php echo t('site.contact') ?></span><br />

                <label><?php echo t('name') ?>:<br /><input type="text" name="name" /></label>
                <br />
                <label><?php echo t('email') ?>*:<br /><input type="text" name="email" /></label>
                <br />
                <label><?php echo t('street') ?>*:<br /><input type="text" name="street" /></label>
                <br />
                <label><?php echo t('zip_city') ?>*:<br /><input type="text" name="zipcode_location" /></label>
            </div>

            <!-- Basket Display -->
            <div class="item" id="basket_display" style="display:none;">
                <span class="head"><?php echo t('basket') ?></span><br />
                <div id="basket_items"></div>
                <div style="margin-top: 10px;">
                    <strong><?php echo t('basket_total') ?>:</strong> <span id="basket_total">0
                        <?php echo Config::CURRENCY; ?></span>
                </div>
            </div>

            <?php foreach ($items->getItems() as $item): ?>
                <div class="item" data-item-id="<?php echo $item['item_id'] ?>">
                    <div>
                        <br />
                        <span class="head"><?php echo $item['name'] ?></span>
                    </div>

                    <div style="float:left">
                        <?php foreach ($item['option_groups'] as $optionGroup): ?>
                            <label><?php echo $optionGroup['group_name'] ?>:<br />
                                <select name="item_<?php echo $item['item_id'] ?>_option_<?php echo $optionGroup['group_id'] ?>"
                                    class="option-select" data-item-id="<?php echo $item['item_id'] ?>"
                                    data-group-id="<?php echo $optionGroup['group_id'] ?>">
                                    <option value="">-- <?php echo t('select') ?>         <?php echo $optionGroup['group_name'] ?> --
                                    </option>
                                    <?php foreach ($optionGroup['options'] as $option): ?>
                                        <option value="<?php echo $option['option_id'] ?>"
                                            data-bundle-id="<?php echo $option['bundle_id'] ?>"
                                            data-bundle-option-id="<?php echo isset($option['bundle_option_id']) ? $option['bundle_option_id'] : '' ?>"
                                            data-price="<?php echo $option['price'] ?>"
                                            data-min-count="<?php echo $option['min_count'] ?>"
                                            data-max-count="<?php echo $option['max_count'] ?>"
                                            data-inventory="<?php echo $option['inventory'] ?>">
                                            <?php echo !empty($option['option_description']) ? $option['option_description'] : $option['option_name'] ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </label>
                            <br />
                        <?php endforeach; ?>

                        <!-- Quantity selector (shown after option selection) -->
                        <div id="quantity_<?php echo $item['item_id'] ?>" style="display:none; margin-top: 10px;">
                            <label><?php echo t('quantity') ?>:<br />
                                <select name="quantity_placeholder" class="quantity-select"
                                    data-item-id="<?php echo $item['item_id'] ?>">
                                    <option value="0">0 <?php echo t('times') ?></option>
                                </select>
                            </label>
                            <br />
                            <?php if ($config->showInventory): ?>
                                <span id="inventory_display_<?php echo $item['item_id'] ?>"></span>
                                <br /><br />
                            <?php endif; ?>
                            <button type="button" class="add-to-basket-btn"
                                data-item-id="<?php echo $item['item_id'] ?>"><?php echo t('add_to_basket') ?></button>
                            <br />
                            <span class="basket-success-message" id="success_<?php echo $item['item_id'] ?>"
                                style="display:none; margin-left: 10px; color: #008800; font-weight: bold;">✓
                                <?php echo t('added_to_basket') ?></span>
                        </div>

                        <br />
                        <?php if ($item['min_porto'] > 0): ?>
                            <?php echo t('porto') ?> (<?php echo t('min') ?>
                            <?php echo number_format($item['min_porto'], 2, ',', '.') . ' ' . Config::CURRENCY ?> )
                        <?php endif; ?>
                    </div>

                    <?php if (!empty($item['picture'])): ?>
                        <div class="picture"><img src="<?php echo $item['picture'] ?>" alt="<?php echo $item['name'] ?>"
                                title="<?php echo $item['name'] ?>" /></div>
                    <?php endif; ?>
                    <div style="clear:both"></div>

                    <div>
                        <br />
                        <span class="description"><?php echo $item['description'] ?></span>
                    </div>

                </div>
            <?php endforeach; ?>
            <?php
            // Check if any item has porto > 0
            $hasPorto = false;
            foreach ($items->getItems() as $item) {
                if ($item['min_porto'] > 0) {
                    $hasPorto = true;
                    break;
                }
            }
            ?>
            <div class="item">
                <span class="head"><?php echo t('form.comments') ?></span><br />

                <textarea type="text" name="comment" /></textarea>
                <br />
                <?php if ($hasPorto): ?>
                    <input type="checkbox" name="collectionByTheCustomer" class="price" />
                    <?php echo t('form.will_collect_no_porto') ?>
                <?php endif; ?>
            </div>
            <div class="item">
                <span class="head"><?php echo t('total') ?></span><br />

                <?php if ($hasPorto): ?>
                    <?php echo t('porto') ?>: <span id="porto">0 <?php echo Config::CURRENCY; ?></span>
                <?php endif; ?>
                <?php echo t('sum') ?>: <span id="total">0 <?php echo Config::CURRENCY; ?></span>
                <br />
                <br />
                <a href="javascript:;" onclick="preview();"><?php echo t('preview') ?></a>
                <br />
            </div>
        </div>
        <div class="main" id="mail" style="display:none">
            <div class="item" id="mail_text"></div>

            <div class="item">
                <div>
                    <a href="javascript:;" onclick="back();"><?php echo t('back') ?></a>

                    <a href="javascript:;" onclick="send();" id="order"
                        style="margin-left: 50px;"><?php echo t('order') ?></a>
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
            <a href="" target="_top"><?php echo t('site.hints_imprint') ?></a> &middot;
            <?php echo t('form.developed_by') ?> <a href="http://sebastian-detert.de">Seeb</a> &middot;
            <?php echo t('form.designed_by') ?> Wozilla
        </div>
    </form>
</body>

</html>