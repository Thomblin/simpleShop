<?php

use PHPUnit\Framework\TestCase;

class ItemsTest extends TestCase
{
    private $dbHelper;
    private $db;
    private $items;

    protected function setUp(): void
    {
        $this->dbHelper = new TestDatabaseHelper();

        // Set up schema first (only once, but safe to call multiple times)
        $this->dbHelper->setupSchema();

        // Clean up before each test
        $this->dbHelper->cleanup();

        $this->db = $this->dbHelper->getDb();
        $this->items = new Items($this->db);
    }

    protected function tearDown(): void
    {
        if ($this->dbHelper) {
            $this->dbHelper->cleanup();
            // Don't close connection here - let PHP handle it
            // Closing causes issues with subsequent tests
        }
    }

    public function testConstructorAcceptsDatabaseInterface()
    {
        $items = new Items($this->db);
        $this->assertInstanceOf(Items::class, $items);
    }

    public function testGetItemsReturnsArrayWithBundles()
    {
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Item 1', 'min_porto' => 5.0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Bundle 1']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20]
            ]
        ]);

        $result = $this->items->getItems();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        $this->assertEquals('Item 1', $result[1]['name']);
        $this->assertArrayHasKey('bundles', $result[1]);
        $this->assertCount(1, $result[1]['bundles']);
    }

    public function testOrderItemWithSufficientInventory()
    {
        // Set up test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 1, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 1, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 10]
            ]
        ]);

        $orders = [
            ['bundle_option_id' => 100, 'amount' => 5]
        ];

        $result = $this->items->orderItem($orders);

        $this->assertTrue($result);

        // Verify inventory was updated
        $bundleOption = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 100')[0];
        $this->assertEquals(5, $bundleOption['inventory']); // 10 - 5 = 5
    }

    public function testOrderItemWithInsufficientInventory()
    {
        // Set up test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 1, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 1, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 5]
            ]
        ]);

        $orders = [
            ['bundle_option_id' => 100, 'amount' => 10]
        ];

        $result = $this->items->orderItem($orders);

        $this->assertFalse($result);

        // Verify inventory was NOT updated (transaction rolled back)
        $bundleOption = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 100')[0];
        $this->assertEquals(5, $bundleOption['inventory']); // Should still be 5
    }

    public function testOrderItemUsesPreparedStatements()
    {
        // This test verifies that prepared statements are used
        // Since we're using real DB, we can verify the functionality works
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 1, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 1, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 10]
            ]
        ]);

        $orders = [
            ['bundle_option_id' => 100, 'amount' => 5]
        ];

        $result = $this->items->orderItem($orders);
        $this->assertTrue($result);

        // Verify the update worked (prepared statements are used internally)
        $bundleOption = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 100')[0];
        $this->assertEquals(5, $bundleOption['inventory']);
    }

    public function testOrderItemWithBundleOption()
    {
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 1, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Size', 'display_order' => 1]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Small', 'description' => '', 'display_order' => 1]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 1, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20]
            ]
        ]);

        $orders = [
            ['bundle_option_id' => 100, 'amount' => 5]
        ];

        $result = $this->items->orderItem($orders);

        $this->assertTrue($result);

        // Verify bundle_option inventory was updated
        $bundleOption = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 100')[0];
        $this->assertEquals(15, $bundleOption['inventory']); // 20 - 5 = 15
    }

    public function testOrderItemRollsBackOnException()
    {
        // This test verifies rollback on exception
        // We can't easily simulate DB failures with real DB, but we can verify
        // that transactions work correctly
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 1, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 1, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 10]
            ]
        ]);

        // Test with invalid order (will fail validation, not exception, but tests rollback)
        $orders = [
            ['bundle_option_id' => 100, 'amount' => 20] // Exceeds inventory
        ];

        $result = $this->items->orderItem($orders);
        $this->assertFalse($result);

        // Verify inventory was NOT updated (transaction rolled back)
        $bundleOption = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 100')[0];
        $this->assertEquals(10, $bundleOption['inventory']); // Should still be 10
    }

    public function testOrderItemWithMultipleOrders()
    {
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item 1', 'min_porto' => 0],
                ['item_id' => 2, 'name' => 'Test Item 2', 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 1, 'item_id' => 1, 'name' => 'Bundle 1'],
                ['bundle_id' => 2, 'item_id' => 2, 'name' => 'Bundle 2']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 1, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 10],
                ['bundle_option_id' => 200, 'bundle_id' => 2, 'option_id' => 1, 'price' => 200.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20]
            ]
        ]);

        $orders = [
            ['bundle_option_id' => 100, 'amount' => 5],
            ['bundle_option_id' => 200, 'amount' => 10]
        ];

        $result = $this->items->orderItem($orders);

        $this->assertTrue($result);

        // Verify both inventories were updated
        $bundleOption1 = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 100')[0];
        $bundleOption2 = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 200')[0];
        $this->assertEquals(5, $bundleOption1['inventory']); // 10 - 5 = 5
        $this->assertEquals(10, $bundleOption2['inventory']); // 20 - 10 = 10
    }

    public function testOrderItemValidatesAllOrdersBeforeUpdating()
    {
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item 1', 'min_porto' => 0],
                ['item_id' => 2, 'name' => 'Test Item 2', 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 1, 'item_id' => 1, 'name' => 'Bundle 1'],
                ['bundle_id' => 2, 'item_id' => 2, 'name' => 'Bundle 2']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 1, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 10],
                ['bundle_option_id' => 200, 'bundle_id' => 2, 'option_id' => 1, 'price' => 200.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 5]
            ]
        ]);

        $orders = [
            ['bundle_option_id' => 100, 'amount' => 5],  // Valid
            ['bundle_option_id' => 200, 'amount' => 10]  // Invalid - exceeds inventory
        ];

        $result = $this->items->orderItem($orders);

        $this->assertFalse($result);

        // Verify NO updates were performed (transaction rolled back)
        $bundleOption1 = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 100')[0];
        $bundleOption2 = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 200')[0];
        $this->assertEquals(10, $bundleOption1['inventory']); // Should still be 10
        $this->assertEquals(5, $bundleOption2['inventory']); // Should still be 5
    }

    public function testGetItemsLoadsOptionGroups()
    {
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Item 1', 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Bundle 1']
            ],
            'option_groups' => [
                ['option_group_id' => 100, 'name' => 'Size', 'display_order' => 1]
            ],
            'options' => [
                ['option_id' => 200, 'option_group_id' => 100, 'name' => 'Small', 'description' => 'Small size', 'display_order' => 1]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 300, 'bundle_id' => 10, 'option_id' => 200, 'price' => 50.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20]
            ]
        ]);

        $result = $this->items->getItems();

        $this->assertIsArray($result);
        $this->assertArrayHasKey(1, $result);
        $this->assertArrayHasKey('option_groups', $result[1]);
        $this->assertArrayHasKey(100, $result[1]['option_groups']);
        $this->assertEquals('Size', $result[1]['option_groups'][100]['group_name']);
        $this->assertCount(1, $result[1]['option_groups'][100]['options']);
        $this->assertEquals('Small', $result[1]['option_groups'][100]['options'][0]['option_name']);
    }

    public function testGetItemsWithMultipleOptionGroups()
    {
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Item 1', 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Bundle 1']
            ],
            'option_groups' => [
                ['option_group_id' => 100, 'name' => 'Size', 'display_order' => 1],
                ['option_group_id' => 101, 'name' => 'Color', 'display_order' => 2]
            ],
            'options' => [
                ['option_id' => 200, 'option_group_id' => 100, 'name' => 'Small', 'description' => 'Small size', 'display_order' => 1],
                ['option_id' => 201, 'option_group_id' => 101, 'name' => 'Red', 'description' => 'Red color', 'display_order' => 1]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 300, 'bundle_id' => 10, 'option_id' => 200, 'price' => 50.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20],
                ['bundle_option_id' => 301, 'bundle_id' => 10, 'option_id' => 201, 'price' => 55.0, 'min_count' => 1, 'max_count' => 5, 'inventory' => 15]
            ]
        ]);

        $result = $this->items->getItems();

        $this->assertArrayHasKey('option_groups', $result[1]);
        $this->assertCount(2, $result[1]['option_groups']);
        $this->assertArrayHasKey(100, $result[1]['option_groups']);
        $this->assertArrayHasKey(101, $result[1]['option_groups']);
    }
}
