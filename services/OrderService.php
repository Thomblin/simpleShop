<?php

/**
 * Service for processing orders
 */
class OrderService
{
    /**
     * @var Items
     */
    private $items;

    /**
     * @var PriceCalculator
     */
    private $priceCalculator;

    /**
     * OrderService constructor.
     *
     * @param Items $items
     * @param PriceCalculator $priceCalculator
     */
    public function __construct(Items $items, PriceCalculator $priceCalculator)
    {
        $this->items = $items;
        $this->priceCalculator = $priceCalculator;
    }

    /**
     * Process order from POST data
     *
     * @param array $postData POST request data
     * @param array $shopItems Shop items from Items::getItems()
     * @return Order
     */
    public function processOrder(array $postData, array $shopItems): Order
    {
        $orderItems = [];
        $orders = [];
        $anyOutOfStock = false;
        $porto = 0.0;

        foreach ($shopItems as $item) {
            if (!isset($postData[$item['item_id']])) {
                continue;
            }

            // Determine selected option per group for this item
            $selectedOptionByGroup = [];
            if (!empty($item['option_groups'])) {
                foreach ($item['option_groups'] as $group) {
                    $fieldName = 'item_' . $item['item_id'] . '_option_' . $group['group_id'];
                    if (isset($postData[$fieldName]) && $postData[$fieldName] !== '') {
                        $selectedOptionByGroup[$group['group_id']] = (int)$postData[$fieldName];
                    }
                }
            }

            foreach ($item['bundles'] as $bundle) {
                if (!isset($postData[$item['item_id']][$bundle['bundle_id']])) {
                    continue;
                }

                $postValue = $postData[$item['item_id']][$bundle['bundle_id']];

                // Handle new nested structure (bundle_option_id => amount)
                if (is_array($postValue)) {
                    $this->processNestedBundleOptions(
                        $postValue,
                        $item,
                        $bundle,
                        $selectedOptionByGroup,
                        $orderItems,
                        $orders,
                        $anyOutOfStock,
                        $porto
                    );
                } else {
                    // Handle old structure (simple amount)
                    $this->processSimpleBundleAmount(
                        $postValue,
                        $item,
                        $bundle,
                        $selectedOptionByGroup,
                        $orderItems,
                        $orders,
                        $anyOutOfStock,
                        $porto
                    );
                }
            }
        }

        $customerInfo = $this->extractCustomerInfo($postData);
        $collectionByCustomer = isset($postData['collectionByTheCustomer']);

        if ($collectionByCustomer) {
            $porto = 0.0;
        }

        return new Order($orderItems, $customerInfo, $porto, $collectionByCustomer);
    }

    /**
     * Process nested bundle options structure
     *
     * @param array $postValue
     * @param array $item
     * @param array $bundle
     * @param array $selectedOptionByGroup
     * @param array &$orderItems
     * @param array &$orders
     * @param bool &$anyOutOfStock
     * @param float &$porto
     */
    private function processNestedBundleOptions(
        array $postValue,
        array $item,
        array $bundle,
        array $selectedOptionByGroup,
        array &$orderItems,
        array &$orders,
        bool &$anyOutOfStock,
        float &$porto
    ): void {
        foreach ($postValue as $boId => $rawAmount) {
            $amount = (int)$rawAmount;

            $effective = $this->determineEffectiveOptions($bundle, $item, $boId, $selectedOptionByGroup);

            // Apply limits and stock checks
            $amount = max($amount, $effective['min_count']);
            $amount = min($amount, $effective['max_count']);

            $outOfStock = $effective['inventory'] < $amount;
            if ($outOfStock) {
                $anyOutOfStock = true;
            }

            if ($amount > 0) {
                $orderItem = new OrderItem(
                    $item['item_id'],
                    $item['name'],
                    $bundle['bundle_id'],
                    $bundle['name'] . (!empty($effective['option_description']) ? ' - ' . $effective['option_description'] : ''),
                    min($amount, $effective['inventory']),
                    $effective['price'],
                    $effective['bundle_option_id'],
                    $selectedOptionByGroup,
                    $outOfStock
                );

                $orderItems[] = $orderItem;

                $porto = max($porto, (float)$item['min_porto']);

                $orders[] = [
                    'amount' => $amount,
                    'bundle_option_id' => (int)$boId
                ];
            }
        }
    }

    /**
     * Process simple bundle amount structure
     *
     * @param mixed $postValue
     * @param array $item
     * @param array $bundle
     * @param array $selectedOptionByGroup
     * @param array &$orderItems
     * @param array &$orders
     * @param bool &$anyOutOfStock
     * @param float &$porto
     */
    private function processSimpleBundleAmount(
        mixed $postValue,
        array $item,
        array $bundle,
        array $selectedOptionByGroup,
        array &$orderItems,
        array &$orders,
        bool &$anyOutOfStock,
        float &$porto
    ): void {
        $amount = (int)$postValue;

        $effective = $this->determineEffectiveOptions($bundle, $item, null, $selectedOptionByGroup);

        // Apply limits and stock checks
        $amount = max($amount, $effective['min_count']);
        $amount = min($amount, $effective['max_count']);

        $outOfStock = $effective['inventory'] < $amount;
        if ($outOfStock) {
            $anyOutOfStock = true;
        }

        if ($amount > 0) {
            $orderItem = new OrderItem(
                $item['item_id'],
                $item['name'],
                $bundle['bundle_id'],
                $bundle['name'] . (!empty($effective['option_description']) ? ' - ' . $effective['option_description'] : ''),
                min($amount, $effective['inventory']),
                $effective['price'],
                $effective['bundle_option_id'],
                $selectedOptionByGroup,
                $outOfStock
            );

            $orderItems[] = $orderItem;

            $porto = max($porto, (float)$item['min_porto']);

            $order = ['amount' => $amount];
            // bundle_option_id is now required - every bundle must have at least one bundle_option
            if (empty($effective['bundle_option_id'])) {
                throw new RuntimeException("Bundle {$bundle['bundle_id']} has no bundle_option_id. Every bundle must have at least one bundle_option.");
            }
            $order['bundle_option_id'] = $effective['bundle_option_id'];
            $orders[] = $order;
        }
    }

    /**
     * Determine effective price, inventory, and options for a bundle
     *
     * @param array $bundle
     * @param array $item
     * @param int|null $bundleOptionId
     * @param array $selectedOptionByGroup
     * @return array
     */
    private function determineEffectiveOptions(array $bundle, array $item, ?int $bundleOptionId, array $selectedOptionByGroup): array
    {
        // All price, min_count, max_count, inventory come from bundle_options only
        // We must find the matching bundle_option from option_groups
        $effective = [
            'price' => 0.0,
            'min_count' => 0,
            'max_count' => 1,
            'inventory' => 0,
            'bundle_option_id' => $bundleOptionId,
            'option_description' => null
        ];

        if (!empty($item['option_groups'])) {
            foreach ($item['option_groups'] as $group) {
                foreach ($group['options'] as $opt) {
                    // Check if this option matches our criteria
                    $matchesBundle = $opt['bundle_id'] == $bundle['bundle_id'];
                    $matchesBundleOption = $bundleOptionId && isset($opt['bundle_option_id']) && (int)$opt['bundle_option_id'] === (int)$bundleOptionId;
                    $matchesSelectedOption = isset($selectedOptionByGroup[$group['group_id']]) && $opt['option_id'] == $selectedOptionByGroup[$group['group_id']];

                    if ($matchesBundle && ($matchesBundleOption || $matchesSelectedOption)) {
                        $effective['price'] = (float)$opt['price'];
                        $effective['min_count'] = (int)$opt['min_count'];
                        $effective['max_count'] = (int)$opt['max_count'];
                        $effective['inventory'] = (int)$opt['inventory'];
                        $effective['option_description'] = !empty($opt['option_description']) ? $opt['option_description'] : $opt['option_name'];

                        if (isset($opt['bundle_option_id'])) {
                            $effective['bundle_option_id'] = (int)$opt['bundle_option_id'];
                        }
                        break 2;
                    }
                }
            }
        } else {
            // If no option_groups, try to get the first bundle_option for this bundle
            // This handles bundles that don't have option groups but still have bundle_options
            $bundleOptions = $this->items->getBundleOptionsForBundle($bundle['bundle_id']);
            if (empty($bundleOptions)) {
                throw new RuntimeException("Bundle {$bundle['bundle_id']} has no bundle_options. Every bundle must have at least one bundle_option.");
            }
            $firstOption = $bundleOptions[0];
            $effective['price'] = (float)$firstOption['price'];
            $effective['min_count'] = (int)$firstOption['min_count'];
            $effective['max_count'] = (int)$firstOption['max_count'];
            $effective['inventory'] = (int)$firstOption['inventory'];
            $effective['bundle_option_id'] = (int)$firstOption['bundle_option_id'];
        }

        return $effective;
    }

    /**
     * Extract customer information from POST data
     *
     * @param array $postData
     * @return array
     */
    private function extractCustomerInfo(array $postData): array
    {
        return [
            'name' => isset($postData['name']) ? $postData['name'] : '',
            'email' => isset($postData['email']) ? $postData['email'] : '',
            'street' => isset($postData['street']) ? $postData['street'] : '',
            'zipcode_location' => isset($postData['zipcode_location']) ? $postData['zipcode_location'] : '',
            'comment' => isset($postData['comment']) ? $postData['comment'] : ''
        ];
    }

    /**
     * Validate customer data
     *
     * @param array $customerData
     * @param array $requiredFields
     * @return array Array of validation errors
     */
    public function validateCustomerData(array $customerData, array $requiredFields): array
    {
        $errors = [];

        foreach ($requiredFields as $field => $required) {
            if ($required === Config::REQUIRED && empty($customerData[$field])) {
                $errors['req'] = t('error.fill_required');
                break;
            }
        }

        return $errors;
    }
}
