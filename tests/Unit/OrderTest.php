<?php

use PHPUnit\Framework\TestCase;

class OrderTest extends TestCase
{
    public function testConstructorSetsAllProperties()
    {
        $items = [
            new OrderItem(1, 'Item 1', 1, 'Bundle 1', 2, 10.0),
            new OrderItem(2, 'Item 2', 2, 'Bundle 2', 1, 20.0)
        ];

        $customer = ['name' => 'John Doe', 'email' => 'john@example.com'];

        $order = new Order($items, $customer, 5.0, false);

        $this->assertEquals($items, $order->items);
        $this->assertEquals($customer, $order->customer);
        $this->assertEquals(5.0, $order->porto);
        $this->assertFalse($order->collectionByCustomer);
    }

    public function testSubtotalIsCalculatedFromItems()
    {
        $items = [
            new OrderItem(1, 'Item 1', 1, 'Bundle 1', 2, 10.0), // 20.0
            new OrderItem(2, 'Item 2', 2, 'Bundle 2', 3, 15.0)  // 45.0
        ];

        $order = new Order($items, [], 5.0);

        $this->assertEquals(65.0, $order->subtotal);
    }

    public function testTotalIncludesPorto()
    {
        $items = [
            new OrderItem(1, 'Item 1', 1, 'Bundle 1', 2, 10.0) // 20.0
        ];

        $order = new Order($items, [], 5.0);

        $this->assertEquals(20.0, $order->subtotal);
        $this->assertEquals(5.0, $order->porto);
        $this->assertEquals(25.0, $order->total);
    }

    public function testHasOutOfStockDetectsOutOfStockItems()
    {
        $items = [
            new OrderItem(1, 'Item 1', 1, 'Bundle 1', 1, 10.0, null, [], false),
            new OrderItem(2, 'Item 2', 2, 'Bundle 2', 1, 10.0, null, [], true)
        ];

        $order = new Order($items, [], 0);

        $this->assertTrue($order->hasOutOfStock);
    }

    public function testHasOutOfStockIsFalseWhenAllInStock()
    {
        $items = [
            new OrderItem(1, 'Item 1', 1, 'Bundle 1', 1, 10.0, null, [], false),
            new OrderItem(2, 'Item 2', 2, 'Bundle 2', 1, 10.0, null, [], false)
        ];

        $order = new Order($items, [], 0);

        $this->assertFalse($order->hasOutOfStock);
    }

    public function testCollectionByCustomerCastsToBool()
    {
        $order = new Order([], [], 0, true);
        $this->assertTrue($order->collectionByCustomer);

        $order = new Order([], [], 0, false);
        $this->assertFalse($order->collectionByCustomer);

        $order = new Order([], [], 0, 1);
        $this->assertTrue($order->collectionByCustomer);
    }

    public function testGetOrdersForPersistence()
    {
        $items = [
            new OrderItem(1, 'Item 1', 10, 'Bundle 1', 2, 10.0, 100),
            new OrderItem(2, 'Item 2', 20, 'Bundle 2', 3, 15.0, null)
        ];

        $order = new Order($items, [], 0);

        $orders = $order->getOrdersForPersistence();

        $this->assertCount(2, $orders);
        $this->assertEquals([
            [
                'bundle_id' => 10,
                'bundle_option_id' => 100,
                'amount' => 2
            ],
            [
                'bundle_id' => 20,
                'bundle_option_id' => null,
                'amount' => 3
            ]
        ], $orders);
    }

    public function testGetItemsArray()
    {
        $items = [
            new OrderItem(1, 'Item 1', 10, 'Bundle 1', 2, 10.0),
        ];

        $order = new Order($items, [], 0);

        $itemsArray = $order->getItemsArray();

        $this->assertIsArray($itemsArray);
        $this->assertCount(1, $itemsArray);
        $this->assertArrayHasKey('item_id', $itemsArray[0]);
        $this->assertArrayHasKey('name', $itemsArray[0]);
        $this->assertArrayHasKey('bundle', $itemsArray[0]);
    }

    public function testEmptyOrder()
    {
        $order = new Order([], [], 0);

        $this->assertEquals(0.0, $order->subtotal);
        $this->assertEquals(0.0, $order->total);
        $this->assertFalse($order->hasOutOfStock);
        $this->assertEmpty($order->getOrdersForPersistence());
        $this->assertEmpty($order->getItemsArray());
    }
}
