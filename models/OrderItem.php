<?php

/**
 * Value object representing a single item in an order
 */
class OrderItem
{
    /**
     * @var int
     */
    public $itemId;

    /**
     * @var string
     */
    public $itemName;

    /**
     * @var int
     */
    public $bundleId;

    /**
     * @var string
     */
    public $bundleName;

    /**
     * @var int|null
     */
    public $bundleOptionId;

    /**
     * @var int
     */
    public $amount;

    /**
     * @var float
     */
    public $price;

    /**
     * @var float
     */
    public $totalPrice;

    /**
     * @var bool
     */
    public $outOfStock;

    /**
     * @var array
     */
    public $selectedOptions;

    /**
     * OrderItem constructor.
     *
     * @param int $itemId
     * @param string $itemName
     * @param int $bundleId
     * @param string $bundleName
     * @param int $amount
     * @param float $price
     * @param int|null $bundleOptionId
     * @param array $selectedOptions
     * @param bool $outOfStock
     */
    public function __construct(
        $itemId,
        $itemName,
        $bundleId,
        $bundleName,
        $amount,
        $price,
        $bundleOptionId = null,
        $selectedOptions = [],
        $outOfStock = false
    ) {
        $this->itemId = (int)$itemId;
        $this->itemName = $itemName;
        $this->bundleId = (int)$bundleId;
        $this->bundleName = $bundleName;
        $this->amount = (int)$amount;
        $this->price = (float)$price;
        $this->bundleOptionId = $bundleOptionId ? (int)$bundleOptionId : null;
        $this->selectedOptions = $selectedOptions;
        $this->outOfStock = (bool)$outOfStock;
        $this->totalPrice = $this->amount * $this->price;
    }

    /**
     * Convert to array for legacy compatibility
     *
     * @return array
     */
    public function toArray()
    {
        return [
            'item_id' => $this->itemId,
            'name' => $this->itemName,
            'bundle_id' => $this->bundleId,
            'bundle' => $this->bundleName,
            'bundle_option_id' => $this->bundleOptionId,
            'amount' => $this->amount,
            'price' => $this->totalPrice,
            'out_of_stock' => $this->outOfStock,
            'selected_options' => $this->selectedOptions
        ];
    }
}
