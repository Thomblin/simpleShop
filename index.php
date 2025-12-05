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
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=5.0, user-scalable=yes" />
    <link rel="icon" type="image/x-icon" href="favicon.ico" />
    <link rel="icon" type="image/svg+xml" href="favicon.svg" />
    <link rel="apple-touch-icon" href="apple-touch-icon.svg" />
    <meta name="theme-color" content="#880000" />
    <link rel="stylesheet" href="css/style.css" />
    <script src="http://code.jquery.com/jquery-1.4.2.min.js"></script>
    <script type="text/javascript">
        // Configuration passed from PHP - must be defined before main.js loads
        var shopConfig = {
            showInventory: <?php echo $config->getShowInventory() ? 'true' : 'false'; ?>,
            currency: '<?php echo $config->getCurrency() ?>',
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
    <!-- Sticky Basket Icon for Mobile -->
    <div id="sticky_basket" class="sticky-basket" style="display:none;">
        <button type="button" class="basket-toggle" aria-label="Toggle basket">
            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                <circle cx="9" cy="21" r="1"></circle>
                <circle cx="20" cy="21" r="1"></circle>
                <path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"></path>
            </svg>
            <span class="basket-count" id="basket_count">0</span>
        </button>
    </div>

    <form id="ajax_form">
        <header class="site-header">
            <div class="container">
                <a href="../index.php" class="logo">
                    <img src="images/logo.svg" alt="<?php echo t('site.title') ?>" />
                    <h1><?php echo t('site.title') ?></h1>
                </a>
            </div>
        </header>

        <main class="main-content" id="main">
            <div class="container">
                <!-- Welcome Section -->
                <section class="welcome-section">
                    <div class="intro">
                        <strong><?php echo t('site.welcome') ?></strong>
                    </div>
                </section>

                <!-- Products Grid -->
                <section class="products-section">
                    <div class="products-grid">
                        <?php foreach ($items->getItems() as $item): ?>
                            <article class="product-card" data-item-id="<?php echo $item['item_id'] ?>">
                                <?php if (!empty($item['picture'])): ?>
                                    <div class="product-image">
                                        <img src="<?php echo $item['picture'] ?>" alt="<?php echo $item['name'] ?>" loading="lazy" />
                                    </div>
                                <?php endif; ?>
                                
                                <div class="product-content">
                                    <h3 class="product-title"><?php echo $item['name'] ?></h3>
                                    
                                    <?php if (!empty($item['description'])): ?>
                                        <p class="product-description"><?php echo $item['description'] ?></p>
                                    <?php endif; ?>

                                    <div class="product-options">
                                        <?php foreach ($item['option_groups'] as $optionGroup): ?>
                                            <div class="form-group">
                                                <label for="item_<?php echo $item['item_id'] ?>_option_<?php echo $optionGroup['group_id'] ?>">
                                                    <?php echo $optionGroup['group_name'] ?>:
                                                </label>
                                                <select 
                                                    id="item_<?php echo $item['item_id'] ?>_option_<?php echo $optionGroup['group_id'] ?>"
                                                    name="item_<?php echo $item['item_id'] ?>_option_<?php echo $optionGroup['group_id'] ?>"
                                                    class="option-select form-select" 
                                                    data-item-id="<?php echo $item['item_id'] ?>"
                                                    data-group-id="<?php echo $optionGroup['group_id'] ?>">
                                                    <option value="">-- <?php echo t('select') ?> <?php echo $optionGroup['group_name'] ?> --</option>
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
                                            </div>
                                        <?php endforeach; ?>

                                        <!-- Quantity selector -->
                                        <div id="quantity_<?php echo $item['item_id'] ?>" class="quantity-section" style="display:none;">
                                            <div class="form-group">
                                                <label for="quantity_select_<?php echo $item['item_id'] ?>"><?php echo t('quantity') ?>:</label>
                                                <select 
                                                    id="quantity_select_<?php echo $item['item_id'] ?>"
                                                    name="quantity_placeholder" 
                                                    class="quantity-select form-select"
                                                    data-item-id="<?php echo $item['item_id'] ?>">
                                                    <option value="0">0 <?php echo t('times') ?></option>
                                                </select>
                                            </div>
                                            
                                            <?php if ($config->getShowInventory()): ?>
                                                <div class="inventory-display" id="inventory_display_<?php echo $item['item_id'] ?>"></div>
                                            <?php endif; ?>
                                            
                                            <button type="button" class="btn btn-primary add-to-basket-btn"
                                                data-item-id="<?php echo $item['item_id'] ?>">
                                                <?php echo t('add_to_basket') ?>
                                            </button>
                                            
                                            <div class="success-message" id="success_<?php echo $item['item_id'] ?>" style="display:none;">
                                                ✓ <span class="success-text"></span>
                                            </div>
                                        </div>

                                        <?php if ($item['min_porto'] > 0): ?>
                                            <div class="shipping-info">
                                                <?php echo t('porto') ?> (<?php echo t('min') ?> <?php echo number_format($item['min_porto'], 2, ',', '.') . ' ' . $config->getCurrency() ?>)
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </section>

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

                <!-- Basket and Total Section -->
                <section class="total-section" style="display:none;">
                    <div class="card">
                        <!-- Basket Display -->
                        <div id="basket_display" style="display:none;">
                            <div id="basket_items" class="basket-items"></div>
                            <div class="basket-total">
                                <strong><?php echo t('basket_total') ?>:</strong>
                                <span id="basket_total" class="total-amount">0 <?php echo $config->getCurrency(); ?></span>
                            </div>
                            <hr class="section-divider" />
                        </div>

                        <!-- Total and Order -->
                        <?php if ($hasPorto): ?>
                            <div class="total-row">
                                <span><?php echo t('porto') ?>:</span>
                                <span id="porto" class="total-amount">0 <?php echo $config->getCurrency(); ?></span>
                            </div>
                        <?php endif; ?>
                    </div>
                </section>
                
                <!-- Contact Form Card -->
                <section class="contact-section">
                    <div class="card">
                        <h2 class="card-title"><?php echo t('site.contact') ?></h2>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="name"><?php echo t('name') ?>:</label>
                                <input type="text" id="name" name="name" class="form-input" />
                            </div>
                            <div class="form-group">
                                <label for="email"><?php echo t('email') ?>*:</label>
                                <input type="email" id="email" name="email" class="form-input" required />
                            </div>
                            <div class="form-group">
                                <label for="street"><?php echo t('street') ?>*:</label>
                                <input type="text" id="street" name="street" class="form-input" required />
                            </div>
                            <div class="form-group">
                                <label for="zipcode_location"><?php echo t('zip_city') ?>*:</label>
                                <input type="text" id="zipcode_location" name="zipcode_location" class="form-input" required />
                            </div>
                        </div>
                        <h2 class="card-title"><?php echo t('form.comments') ?></h2>
                        <div class="form-group">
                            <textarea name="comment" class="form-input" rows="4"></textarea>
                        </div>
                        <?php if ($hasPorto): ?>
                            <div class="form-group checkbox-group">
                                <label>
                                    <input type="checkbox" name="collectionByTheCustomer" class="price" />
                                    <?php echo t('form.will_collect_no_porto') ?>
                                </label>
                            </div>
                        <?php endif; ?>
                        <button type="button" class="btn btn-primary btn-large" onclick="preview();">
                            <?php echo t('preview') ?>
                        </button>
                    </div>
                </section>
            </div>
        </main>

        <!-- Mail Preview Screen -->
        <div class="main-content" id="mail" style="display:none">
            <div class="container">
                <section class="mail-preview-section">
                    <div class="card">
                        <div id="mail_text" class="mail-content"></div>
                    </div>
                    <div class="card action-buttons">
                        <button type="button" class="btn btn-secondary" onclick="back();">
                            <?php echo t('back') ?>
                        </button>
                        <button type="button" class="btn btn-primary" id="order" onclick="send();">
                            <?php echo t('order') ?>
                        </button>
                        <div id="order_hint" class="order-hint">
                            <?php echo t('change_selection') ?>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <!-- Confirmation Screen -->
        <div class="main-content" id="confirm" style="display:none">
            <div class="container">
                <section class="confirmation-section">
                    <div class="card success-card">
                        <div class="success-icon">✓</div>
                        <p><?php echo t('form.success_message') ?></p>
                    </div>
                </section>
            </div>
        </div>

        <!-- Footer -->
        <footer class="site-footer">
            <div class="container">
                <p>
                    <a href="" target="_top"><?php echo t('site.hints_imprint') ?></a> &middot;
                    <?php echo t('form.developed_by') ?> <a href="http://sebastian-detert.de">Seeb</a> &middot;
                    <?php echo t('form.designed_by') ?> Wozilla
                </p>
            </div>
        </footer>
    </form>
</body>

</html>