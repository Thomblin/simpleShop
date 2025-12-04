<?php

use PHPUnit\Framework\TestCase;

class PriceCalculatorTest extends TestCase
{
    public function testCalculatePortoReturnsZeroForCollectionByCustomer()
    {
        $calculator = new PriceCalculator();

        $items = [
            ['min_porto' => 5.0],
            ['min_porto' => 10.0]
        ];

        $porto = $calculator->calculatePorto($items, true);

        $this->assertEquals(0.0, $porto);
    }

    public function testCalculatePortoReturnsMaxPorto()
    {
        $calculator = new PriceCalculator();

        $items = [
            ['min_porto' => 5.0],
            ['min_porto' => 10.0],
            ['min_porto' => 7.5]
        ];

        $porto = $calculator->calculatePorto($items, false);

        $this->assertEquals(10.0, $porto);
    }

    public function testCalculatePortoWithNoItems()
    {
        $calculator = new PriceCalculator();

        $porto = $calculator->calculatePorto([], false);

        $this->assertEquals(0.0, $porto);
    }

    public function testCalculatePortoWithItemsWithoutMinPorto()
    {
        $calculator = new PriceCalculator();

        $items = [
            ['name' => 'Item 1'],
            ['name' => 'Item 2']
        ];

        $porto = $calculator->calculatePorto($items, false);

        $this->assertEquals(0.0, $porto);
    }

    public function testCalculateTotalReturnsPriceResult()
    {
        $calculator = new PriceCalculator('€');

        $items = [
            new OrderItem(1, 'Item 1', 1, 'Bundle 1', 2, 10.0),
            new OrderItem(2, 'Item 2', 2, 'Bundle 2', 3, 15.0)
        ];

        $result = $calculator->calculateTotal($items, 5.0);

        $this->assertInstanceOf(PriceResult::class, $result);
        $this->assertEquals(65.0, $result->subtotal); // 2*10 + 3*15
        $this->assertEquals(5.0, $result->porto);
        $this->assertEquals(70.0, $result->total);
    }

    public function testCalculateTotalWithEmptyItems()
    {
        $calculator = new PriceCalculator();

        $result = $calculator->calculateTotal([], 5.0);

        $this->assertEquals(0.0, $result->subtotal);
        $this->assertEquals(5.0, $result->porto);
        $this->assertEquals(5.0, $result->total);
    }

    public function testFormatPrice()
    {
        $calculator = new PriceCalculator('€');

        $this->assertEquals('100,50 €', $calculator->formatPrice(100.50));
        $this->assertEquals('0,00 €', $calculator->formatPrice(0));
        $this->assertEquals('1.234,56 €', $calculator->formatPrice(1234.56));
    }

    public function testFormatPriceWithCustomCurrency()
    {
        $calculator = new PriceCalculator('$');

        $this->assertEquals('100,50 $', $calculator->formatPrice(100.50));
    }

    public function testConstructorWithDefaultCurrency()
    {
        $calculator = new PriceCalculator();

        $formatted = $calculator->formatPrice(10.0);

        $this->assertStringContainsString('€', $formatted);
    }

    public function testConstructorWithCustomCurrency()
    {
        $calculator = new PriceCalculator('USD');

        $formatted = $calculator->formatPrice(10.0);

        $this->assertStringContainsString('USD', $formatted);
    }

    public function testCalculateTotalUsesCorrectCurrency()
    {
        $calculator = new PriceCalculator('$');

        $items = [
            new OrderItem(1, 'Item', 1, 'Bundle', 1, 10.0)
        ];

        $result = $calculator->calculateTotal($items, 0);

        $this->assertStringContainsString('$', $result->totalFormatted);
        $this->assertStringNotContainsString('€', $result->totalFormatted);
    }
}
