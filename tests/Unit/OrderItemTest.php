<?php

use PHPUnit\Framework\TestCase;

class OrderItemTest extends TestCase
{
    public function testConstructorSetsAllProperties()
    {
        $item = new OrderItem(
            1,
            'Test Item',
            2,
            'Test Bundle',
            3,
            10.50,
            4,
            ['option1' => 1],
            false
        );

        $this->assertEquals(1, $item->itemId);
        $this->assertEquals('Test Item', $item->itemName);
        $this->assertEquals(2, $item->bundleId);
        $this->assertEquals('Test Bundle', $item->bundleName);
        $this->assertEquals(3, $item->amount);
        $this->assertEquals(10.50, $item->price);
        $this->assertEquals(4, $item->bundleOptionId);
        $this->assertEquals(['option1' => 1], $item->selectedOptions);
        $this->assertFalse($item->outOfStock);
    }

    public function testTotalPriceIsCalculated()
    {
        $item = new OrderItem(1, 'Test', 1, 'Bundle', 5, 10.0);
        $this->assertEquals(50.0, $item->totalPrice);

        $item = new OrderItem(1, 'Test', 1, 'Bundle', 3, 15.50);
        $this->assertEquals(46.50, $item->totalPrice);
    }

    public function testConstructorCastsTypesCorrectly()
    {
        $item = new OrderItem(
            '1',
            'Test',
            '2',
            'Bundle',
            '3',
            '10.50',
            '4',
            [],
            '1'
        );

        $this->assertIsInt($item->itemId);
        $this->assertIsInt($item->bundleId);
        $this->assertIsInt($item->amount);
        $this->assertIsFloat($item->price);
        $this->assertIsInt($item->bundleOptionId);
        $this->assertIsBool($item->outOfStock);
    }

    public function testNullBundleOptionId()
    {
        $item = new OrderItem(1, 'Test', 1, 'Bundle', 1, 10.0, null);
        $this->assertNull($item->bundleOptionId);
    }

    public function testDefaultOptionalParameters()
    {
        $item = new OrderItem(1, 'Test', 1, 'Bundle', 1, 10.0);

        $this->assertNull($item->bundleOptionId);
        $this->assertEquals([], $item->selectedOptions);
        $this->assertFalse($item->outOfStock);
    }

    public function testToArrayReturnsCorrectStructure()
    {
        $item = new OrderItem(
            1,
            'Test Item',
            2,
            'Test Bundle',
            3,
            10.0,
            4,
            ['opt' => 1],
            true
        );

        $array = $item->toArray();

        $this->assertIsArray($array);
        $this->assertEquals(1, $array['item_id']);
        $this->assertEquals('Test Item', $array['name']);
        $this->assertEquals(2, $array['bundle_id']);
        $this->assertEquals('Test Bundle', $array['bundle']);
        $this->assertEquals(4, $array['bundle_option_id']);
        $this->assertEquals(3, $array['amount']);
        $this->assertEquals(30.0, $array['price']); // totalPrice
        $this->assertTrue($array['out_of_stock']);
        $this->assertEquals(['opt' => 1], $array['selected_options']);
    }

    public function testOutOfStockFlag()
    {
        $item = new OrderItem(1, 'Test', 1, 'Bundle', 1, 10.0, null, [], true);
        $this->assertTrue($item->outOfStock);

        $item = new OrderItem(1, 'Test', 1, 'Bundle', 1, 10.0, null, [], false);
        $this->assertFalse($item->outOfStock);
    }
}
