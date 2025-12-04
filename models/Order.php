<?php

/**
 * Value object representing a complete order
 */
class Order
{
    /**
     * @var OrderItem[]
     */
    public $items;

    /**
     * @var array Customer information
     */
    public $customer;

    /**
     * @var float
     */
    public $subtotal;

    /**
     * @var float
     */
    public $porto;

    /**
     * @var float
     */
    public $total;

    /**
     * @var bool
     */
    public $collectionByCustomer;

    /**
     * @var bool
     */
    public $hasOutOfStock;

    /**
     * Order constructor.
     *
     * @param OrderItem[] $items
     * @param array $customer
     * @param float $porto
     * @param bool $collectionByCustomer
     */
    public function __construct(array $items, array $customer, $porto, $collectionByCustomer = false)
    {
        $this->items = $items;
        $this->customer = $customer;
        $this->porto = (float)$porto;
        $this->collectionByCustomer = (bool)$collectionByCustomer;

        // Calculate subtotal and check for out of stock items
        $this->subtotal = 0.0;
        $this->hasOutOfStock = false;

        foreach ($items as $item) {
            $this->subtotal += $item->totalPrice;
            if ($item->outOfStock) {
                $this->hasOutOfStock = true;
            }
        }

        $this->total = $this->subtotal + $this->porto;
    }

    /**
     * Get order items for database persistence
     *
     * @return array Array of order data for Items::orderItem()
     */
    public function getOrdersForPersistence()
    {
        $orders = [];
        foreach ($this->items as $item) {
            $orders[] = [
                'bundle_id' => $item->bundleId,
                'bundle_option_id' => $item->bundleOptionId,
                'amount' => $item->amount
            ];
        }
        return $orders;
    }

    /**
     * Get items as array for template rendering
     *
     * @return array
     */
    public function getItemsArray()
    {
        return array_map(function ($item) {
            return $item->toArray();
        }, $this->items);
    }
}
