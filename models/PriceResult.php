<?php

/**
 * Value object representing price calculation result
 */
class PriceResult
{
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
     * @var string Formatted subtotal
     */
    public $subtotalFormatted;

    /**
     * @var string Formatted porto
     */
    public $portoFormatted;

    /**
     * @var string Formatted total
     */
    public $totalFormatted;

    /**
     * PriceResult constructor.
     *
     * @param float $subtotal
     * @param float $porto
     * @param string $currency
     */
    public function __construct(float $subtotal, float $porto, string $currency = '€')
    {
        $this->subtotal = (float)$subtotal;
        $this->porto = (float)$porto;
        $this->total = $this->subtotal + $this->porto;

        $this->subtotalFormatted = $this->formatPrice($this->subtotal, $currency);
        $this->portoFormatted = $this->formatPrice($this->porto, $currency);
        $this->totalFormatted = $this->formatPrice($this->total, $currency);
    }

    /**
     * Format a price value
     *
     * @param float $price
     * @param string $currency
     * @return string
     */
    private function formatPrice(float $price, string $currency): string
    {
        return number_format($price, 2, ',', '.') . ' ' . $currency;
    }

    /**
     * Convert to array for JSON response
     *
     * @return array
     */
    public function toArray(): array
    {
        return [
            'subtotal' => $this->subtotal,
            'porto' => $this->porto,
            'total' => $this->total,
            'subtotal_formatted' => $this->subtotalFormatted,
            'porto_formatted' => $this->portoFormatted,
            'total_formatted' => $this->totalFormatted
        ];
    }
}
