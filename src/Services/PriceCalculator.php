<?php
/**
 * Calculates order totals, shipping costs, and formats prices with currency symbols.
 */

/**
 * Service for calculating prices and shipping costs
 */
class PriceCalculator
{
    /**
     * @var string
     */
    private $currency;

    /**
     * PriceCalculator constructor.
     *
     * @param string $currency Currency symbol (default: '€')
     */
    public function __construct(string $currency = '€')
    {
        $this->currency = $currency;
    }

    /**
     * Calculate shipping cost based on items
     *
     * @param array $items Array of items with min_porto values
     * @param bool $collectionByCustomer Whether customer will collect
     * @return float Shipping cost
     */
    public function calculatePorto(array $items, bool $collectionByCustomer = false): float
    {
        if ($collectionByCustomer) {
            return 0.0;
        }

        $porto = 0.0;

        foreach ($items as $item) {
            if (isset($item['min_porto'])) {
                $porto = max($porto, (float) $item['min_porto']);
            }
        }

        return $porto;
    }

    /**
     * Calculate total price from order items
     *
     * @param OrderItem[] $items
     * @param float $porto Shipping cost
     * @return PriceResult
     */
    public function calculateTotal(array $items, float $porto): PriceResult
    {
        $subtotal = 0.0;

        foreach ($items as $item) {
            $subtotal += $item->totalPrice;
        }

        return new PriceResult($subtotal, $porto, $this->currency);
    }

    /**
     * Format a price value
     *
     * @param float $price
     * @return string Formatted price with currency
     */
    public function formatPrice(float $price): string
    {
        return number_format($price, 2, ',', '.') . ' ' . $this->currency;
    }
}
