<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test for the complete order flow
 */
class OrderFlowTest extends TestCase
{
    private $mockDb;
    private $items;
    private $priceCalculator;
    private $orderService;

    protected function setUp(): void
    {
        $this->mockDb = new MockDb();
        $this->items = new Items($this->mockDb);
        $this->priceCalculator = new PriceCalculator('€');
        $this->orderService = new OrderService($this->items, $this->priceCalculator);
    }

    protected function tearDown(): void
    {
        $this->mockDb->reset();
    }

    public function testCompleteOrderFlowWithSufficientInventory()
    {
        // Set up shop data
        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Product',
                'min_porto' => 5.50,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Small Package',
                        'price' => 29.99,
                        'min_count' => 1,
                        'max_count' => 5,
                        'inventory' => 100
                    ]
                ],
                'option_groups' => []
            ]
        ];

        // Set up database mock
        $this->mockDb->setData('bundles', [
            ['bundle_id' => 10, 'inventory' => 100]
        ]);
        $this->mockDb->setData('bundle_options', []);

        // Simulate customer order
        $postData = [
            1 => [10 => 2],  // Order 2 of item 1, bundle 10
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'street' => '123 Main Street',
            'zipcode_location' => '12345 Springfield'
        ];

        // Process the order
        $order = $this->orderService->processOrder($postData, $shopItems);

        // Verify order structure
        $this->assertInstanceOf(Order::class, $order);
        $this->assertCount(1, $order->items);
        $this->assertEquals('John Doe', $order->customer['name']);

        // Verify pricing (use delta for float comparison)
        $this->assertEqualsWithDelta(59.98, $order->subtotal, 0.01); // 2 * 29.99
        $this->assertEqualsWithDelta(5.50, $order->porto, 0.01);
        $this->assertEqualsWithDelta(65.48, $order->total, 0.01);

        // Get orders for persistence
        $ordersForDb = $order->getOrdersForPersistence();
        $this->assertCount(1, $ordersForDb);
        $this->assertEquals(10, $ordersForDb[0]['bundle_id']);
        $this->assertEquals(2, $ordersForDb[0]['amount']);

        // Persist the order
        $success = $this->items->orderItem($ordersForDb);
        $this->assertTrue($success);

        // Verify transaction was used
        $queries = $this->mockDb->getQueries();
        $transactionStarted = false;
        $transactionCommitted = false;

        foreach ($queries as $query) {
            if ($query['type'] === 'beginTransaction') $transactionStarted = true;
            if ($query['type'] === 'commit') $transactionCommitted = true;
        }

        $this->assertTrue($transactionStarted);
        $this->assertTrue($transactionCommitted);
    }

    public function testCompleteOrderFlowWithInsufficientInventory()
    {
        // Set up shop data
        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Limited Product',
                'min_porto' => 3.00,
                'bundles' => [
                    [
                        'bundle_id' => 20,
                        'name' => 'Last Items',
                        'price' => 99.99,
                        'min_count' => 1,
                        'max_count' => 10,
                        'inventory' => 3  // Only 3 left
                    ]
                ],
                'option_groups' => []
            ]
        ];

        // Set up database mock with low inventory
        $this->mockDb->setData('bundles', [
            ['bundle_id' => 20, 'inventory' => 3]
        ]);
        $this->mockDb->setData('bundle_options', []);

        // Customer tries to order 5 (more than available)
        $postData = [
            1 => [20 => 5],
            'name' => 'Jane Smith',
            'email' => 'jane@example.com'
        ];

        // Process order
        $order = $this->orderService->processOrder($postData, $shopItems);

        // Order should be created but marked as out of stock
        $this->assertTrue($order->hasOutOfStock);
        $this->assertEquals(3, $order->items[0]->amount); // Clamped to available
        $this->assertTrue($order->items[0]->outOfStock);

        // Try to persist - should fail because we tried to order 5
        $ordersForDb = $order->getOrdersForPersistence();
        $ordersForDb[0]['amount'] = 5; // Restore original amount

        $success = $this->items->orderItem($ordersForDb);
        $this->assertFalse($success);

        // Verify rollback occurred
        $queries = $this->mockDb->getQueries();
        $hasRollback = false;
        foreach ($queries as $query) {
            if ($query['type'] === 'rollback') {
                $hasRollback = true;
                break;
            }
        }
        $this->assertTrue($hasRollback);
    }

    public function testOrderFlowWithCollectionByCustomer()
    {
        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Product',
                'min_porto' => 10.00,
                'bundles' => [
                    [
                        'bundle_id' => 30,
                        'name' => 'Standard',
                        'price' => 50.00,
                        'min_count' => 1,
                        'max_count' => 10,
                        'inventory' => 50
                    ]
                ],
                'option_groups' => []
            ]
        ];

        $postData = [
            1 => [30 => 1],
            'name' => 'Local Customer',
            'email' => 'local@example.com',
            'collectionByTheCustomer' => '1'  // Will collect in person
        ];

        $order = $this->orderService->processOrder($postData, $shopItems);

        // Porto should be 0 when customer collects
        $this->assertEqualsWithDelta(0.0, $order->porto, 0.01);
        $this->assertEqualsWithDelta(50.00, $order->total, 0.01);  // No shipping cost
        $this->assertTrue($order->collectionByCustomer);
    }

    public function testOrderFlowWithMultipleItems()
    {
        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Product A',
                'min_porto' => 5.00,
                'bundles' => [
                    ['bundle_id' => 10, 'name' => 'Bundle A', 'price' => 10.00, 'min_count' => 1, 'max_count' => 10, 'inventory' => 50]
                ],
                'option_groups' => []
            ],
            [
                'item_id' => 2,
                'name' => 'Product B',
                'min_porto' => 7.50,  // Higher shipping cost
                'bundles' => [
                    ['bundle_id' => 20, 'name' => 'Bundle B', 'price' => 20.00, 'min_count' => 1, 'max_count' => 10, 'inventory' => 30]
                ],
                'option_groups' => []
            ]
        ];

        $this->mockDb->setData('bundles', [
            ['bundle_id' => 10, 'inventory' => 50],
            ['bundle_id' => 20, 'inventory' => 30]
        ]);
        $this->mockDb->setData('bundle_options', []);

        $postData = [
            1 => [10 => 2],  // 2 of Product A
            2 => [20 => 3],  // 3 of Product B
            'name' => 'Multi Buyer',
            'email' => 'multi@example.com'
        ];

        $order = $this->orderService->processOrder($postData, $shopItems);

        // Verify both items in order
        $this->assertCount(2, $order->items);

        // Verify pricing
        $this->assertEqualsWithDelta(80.00, $order->subtotal, 0.01); // (2*10) + (3*20)
        $this->assertEqualsWithDelta(7.50, $order->porto, 0.01);  // Max of 5.00 and 7.50
        $this->assertEqualsWithDelta(87.50, $order->total, 0.01);

        // Verify persistence
        $ordersForDb = $order->getOrdersForPersistence();
        $this->assertCount(2, $ordersForDb);

        $success = $this->items->orderItem($ordersForDb);
        $this->assertTrue($success);
    }

    public function testPriceCalculation()
    {
        $items = [
            new OrderItem(1, 'Item 1', 1, 'Bundle 1', 2, 19.99),
            new OrderItem(2, 'Item 2', 2, 'Bundle 2', 1, 49.99),
        ];

        $priceResult = $this->priceCalculator->calculateTotal($items, 5.90);

        $this->assertEqualsWithDelta(89.97, $priceResult->subtotal, 0.01); // (2*19.99) + 49.99
        $this->assertEqualsWithDelta(5.90, $priceResult->porto, 0.01);
        $this->assertEqualsWithDelta(95.87, $priceResult->total, 0.01);

        // Verify formatting
        $this->assertEquals('89,97 €', $priceResult->subtotalFormatted);
        $this->assertEquals('5,90 €', $priceResult->portoFormatted);
        $this->assertEquals('95,87 €', $priceResult->totalFormatted);
    }
}
