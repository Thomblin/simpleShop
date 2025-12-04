<?php

use PHPUnit\Framework\TestCase;

class PriceResultTest extends TestCase
{
    public function testConstructorCalculatesTotal()
    {
        $result = new PriceResult(100.0, 10.0);

        $this->assertEquals(100.0, $result->subtotal);
        $this->assertEquals(10.0, $result->porto);
        $this->assertEquals(110.0, $result->total);
    }

    public function testFormattedPricesWithDefaultCurrency()
    {
        $result = new PriceResult(100.50, 10.25);

        $this->assertEquals('100,50 €', $result->subtotalFormatted);
        $this->assertEquals('10,25 €', $result->portoFormatted);
        $this->assertEquals('110,75 €', $result->totalFormatted);
    }

    public function testFormattedPricesWithCustomCurrency()
    {
        $result = new PriceResult(100.50, 10.25, '$');

        $this->assertEquals('100,50 $', $result->subtotalFormatted);
        $this->assertEquals('10,25 $', $result->portoFormatted);
        $this->assertEquals('110,75 $', $result->totalFormatted);
    }

    public function testFormattedPricesRoundCorrectly()
    {
        $result = new PriceResult(99.999, 10.001);

        $this->assertEquals('100,00 €', $result->subtotalFormatted);
        $this->assertEquals('10,00 €', $result->portoFormatted);
        $this->assertEquals('110,00 €', $result->totalFormatted);
    }

    public function testTypeCasting()
    {
        $result = new PriceResult('100.50', '10.25');

        $this->assertIsFloat($result->subtotal);
        $this->assertIsFloat($result->porto);
        $this->assertIsFloat($result->total);
    }

    public function testZeroValues()
    {
        $result = new PriceResult(0, 0);

        $this->assertEquals(0.0, $result->subtotal);
        $this->assertEquals(0.0, $result->porto);
        $this->assertEquals(0.0, $result->total);
        $this->assertEquals('0,00 €', $result->subtotalFormatted);
        $this->assertEquals('0,00 €', $result->portoFormatted);
        $this->assertEquals('0,00 €', $result->totalFormatted);
    }

    public function testToArray()
    {
        $result = new PriceResult(100.50, 10.25, '€');

        $array = $result->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(100.50, $array['subtotal']);
        $this->assertEquals(10.25, $array['porto']);
        $this->assertEquals(110.75, $array['total']);
        $this->assertEquals('100,50 €', $array['subtotal_formatted']);
        $this->assertEquals('10,25 €', $array['porto_formatted']);
        $this->assertEquals('110,75 €', $array['total_formatted']);
    }

    public function testLargeNumbers()
    {
        $result = new PriceResult(1234567.89, 123.45);

        $this->assertEquals('1.234.567,89 €', $result->subtotalFormatted);
        $this->assertEquals('123,45 €', $result->portoFormatted);
        $this->assertEquals('1.234.691,34 €', $result->totalFormatted);
    }
}
