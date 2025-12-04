<?php

use PHPUnit\Framework\TestCase;

/**
 * Integration test for the complete order flow
 */
class OrderFlowTest extends TestCase
{
    private $dbHelper;
    private $db;
    private $items;
    private $priceCalculator;
    private $orderService;

    protected function setUp(): void
    {
        $this->dbHelper = new TestDatabaseHelper(
            'test_mysql', // Docker service name
            'testuser',
            'testpass',
            'test_shop'
        );
        $this->dbHelper->cleanup(); // Clean before each test
        $this->db = new Db($this->dbHelper->getConnection());
        $this->items = new Items($this->db);
        $this->priceCalculator = new PriceCalculator('€');
        $this->orderService = new OrderService($this->items, $this->priceCalculator);
    }

    protected function tearDown(): void
    {
        if ($this->dbHelper) {
            $this->dbHelper->cleanup();
            // Don't close connection here - let PHP handle it
            // Closing causes issues with subsequent tests
        }
    }

    public function testCompleteOrderFlowWithSufficientInventory()
    {
        // Set up database with test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Product', 'picture' => null, 'description' => null, 'min_porto' => 5.50]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Small Package']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 1, 'bundle_id' => 10, 'option_id' => 1, 'price' => 29.99, 'min_count' => 1, 'max_count' => 5, 'inventory' => 100]
            ]
        ]);

        // Set up shop data (price, min_count, max_count, inventory come from bundle_options via database)
        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Product',
                'min_porto' => 5.50,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Small Package'
                    ]
                ],
                'option_groups' => []
            ]
        ];

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
        $this->assertArrayHasKey('bundle_option_id', $ordersForDb[0]);
        $this->assertEquals(1, $ordersForDb[0]['bundle_option_id']); // Should be the bundle_option_id we created
        $this->assertEquals(2, $ordersForDb[0]['amount']);

        // Persist the order
        $success = $this->items->orderItem($ordersForDb);
        $this->assertTrue($success);

        // Verify inventory was updated (transaction committed)
        $bundleOption = $this->dbHelper->getData('bundle_options', 'bundle_id = 10')[0];
        $this->assertEquals(98, $bundleOption['inventory']); // 100 - 2 = 98
    }

    public function testCompleteOrderFlowWithInsufficientInventory()
    {
        // Set up database with low inventory
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Limited Product', 'picture' => null, 'description' => null, 'min_porto' => 3.00]
            ],
            'bundles' => [
                ['bundle_id' => 20, 'item_id' => 1, 'name' => 'Last Items']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 1, 'bundle_id' => 20, 'option_id' => 1, 'price' => 99.99, 'min_count' => 1, 'max_count' => 10, 'inventory' => 3]
            ]
        ]);

        // Set up shop data
        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Limited Product',
                'min_porto' => 3.00,
                'bundles' => [
                    [
                        'bundle_id' => 20,
                        'name' => 'Last Items'
                    ]
                ],
                'option_groups' => []
            ]
        ];

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

        // Try to persist - should fail because we tried to order 5 (but only 3 available)
        $ordersForDb = $order->getOrdersForPersistence();
        // The order already has the correct amount (3), but let's try with 5 to test validation
        $ordersForDb[0]['amount'] = 5; // Try to order 5

        $success = $this->items->orderItem($ordersForDb);
        $this->assertFalse($success);

        // Verify rollback occurred - inventory should still be 3
        $bundleOption = $this->dbHelper->getData('bundle_options', 'bundle_id = 20')[0];
        $this->assertEquals(3, $bundleOption['inventory']); // Should still be 3 (transaction rolled back)
    }

    public function testOrderFlowWithCollectionByCustomer()
    {
        // Set up database
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Product', 'picture' => null, 'description' => null, 'min_porto' => 10.00]
            ],
            'bundles' => [
                ['bundle_id' => 30, 'item_id' => 1, 'name' => 'Standard']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 1, 'bundle_id' => 30, 'option_id' => 1, 'price' => 50.00, 'min_count' => 1, 'max_count' => 10, 'inventory' => 50]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Product',
                'min_porto' => 10.00,
                'bundles' => [
                    [
                        'bundle_id' => 30,
                        'name' => 'Standard'
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
        // Set up database with multiple items
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Product A', 'picture' => null, 'description' => null, 'min_porto' => 5.00],
                ['item_id' => 2, 'name' => 'Product B', 'picture' => null, 'description' => null, 'min_porto' => 7.50]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Bundle A'],
                ['bundle_id' => 20, 'item_id' => 2, 'name' => 'Bundle B']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 1, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.00, 'min_count' => 1, 'max_count' => 10, 'inventory' => 50],
                ['bundle_option_id' => 2, 'bundle_id' => 20, 'option_id' => 1, 'price' => 20.00, 'min_count' => 1, 'max_count' => 10, 'inventory' => 30]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Product A',
                'min_porto' => 5.00,
                'bundles' => [
                    ['bundle_id' => 10, 'name' => 'Bundle A']
                ],
                'option_groups' => []
            ],
            [
                'item_id' => 2,
                'name' => 'Product B',
                'min_porto' => 7.50,  // Higher shipping cost
                'bundles' => [
                    ['bundle_id' => 20, 'name' => 'Bundle B']
                ],
                'option_groups' => []
            ]
        ];

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

        // Verify inventory was updated for both bundles
        $bundleOption10 = $this->dbHelper->getData('bundle_options', 'bundle_id = 10')[0];
        $bundleOption20 = $this->dbHelper->getData('bundle_options', 'bundle_id = 20')[0];
        $this->assertEquals(48, $bundleOption10['inventory']); // 50 - 2 = 48
        $this->assertEquals(27, $bundleOption20['inventory']); // 30 - 3 = 27
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
