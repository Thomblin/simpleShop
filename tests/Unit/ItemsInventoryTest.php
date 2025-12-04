<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for Items inventory management methods
 */
class ItemsInventoryTest extends TestCase
{
    private $dbHelper;
    private $db;
    private $items;

    protected function setUp(): void
    {
        $this->dbHelper = new TestDatabaseHelper(
            'test_mysql',
            'testuser',
            'testpass',
            'test_shop'
        );
        $this->dbHelper->cleanup();
        $this->db = new Db($this->dbHelper->getConnection());
        $this->items = new Items($this->db);
    }

    protected function tearDown(): void
    {
        if ($this->dbHelper) {
            $this->dbHelper->cleanup();
        }
    }

    public function testGetBundleOptionsForBundleReturnsOptions()
    {
        // Set up test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'picture' => null, 'description' => null, 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 50]
            ]
        ]);

        $result = $this->items->getBundleOptionsForBundle(10);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(100, $result[0]['bundle_option_id']);
        $this->assertEquals(10, $result[0]['bundle_id']);
        $this->assertEquals(10.0, $result[0]['price']);
        $this->assertEquals(50, $result[0]['inventory']);
    }

    public function testGetBundleOptionsForBundleReturnsEmptyForNonExistentBundle()
    {
        $result = $this->items->getBundleOptionsForBundle(999);

        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testGetBundleOptionsForBundleReturnsFirstOption()
    {
        // Set up test data with multiple bundle_options
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'picture' => null, 'description' => null, 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 50],
                ['bundle_option_id' => 101, 'bundle_id' => 10, 'option_id' => 1, 'price' => 15.0, 'min_count' => 1, 'max_count' => 5, 'inventory' => 30]
            ]
        ]);

        $result = $this->items->getBundleOptionsForBundle(10);

        // Should return only the first one (LIMIT 1)
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals(100, $result[0]['bundle_option_id']); // First one by bundle_option_id
    }

    public function testOrderItemWithBundleOptionIdUpdatesInventory()
    {
        // Set up test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'picture' => null, 'description' => null, 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 50]
            ]
        ]);

        $orders = [
            ['bundle_option_id' => 100, 'amount' => 5]
        ];

        $result = $this->items->orderItem($orders);

        $this->assertTrue($result);

        // Verify inventory was updated
        $bundleOption = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 100')[0];
        $this->assertEquals(45, $bundleOption['inventory']); // 50 - 5 = 45
    }

    public function testOrderItemWithMultipleBundleOptions()
    {
        // Set up test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item 1', 'picture' => null, 'description' => null, 'min_porto' => 0],
                ['item_id' => 2, 'name' => 'Test Item 2', 'picture' => null, 'description' => null, 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Bundle 1'],
                ['bundle_id' => 20, 'item_id' => 2, 'name' => 'Bundle 2']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 50],
                ['bundle_option_id' => 200, 'bundle_id' => 20, 'option_id' => 1, 'price' => 20.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 30]
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
        $this->assertEquals(45, $bundleOption1['inventory']); // 50 - 5 = 45
        $this->assertEquals(20, $bundleOption2['inventory']); // 30 - 10 = 20
    }

    public function testOrderItemValidatesInventoryBeforeUpdating()
    {
        // Set up test data with low inventory
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'picture' => null, 'description' => null, 'min_porto' => 0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Default', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Default', 'display_order' => 0, 'description' => null]
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 5]
            ]
        ]);

        // Try to order more than available
        $orders = [
            ['bundle_option_id' => 100, 'amount' => 10]
        ];

        $result = $this->items->orderItem($orders);

        $this->assertFalse($result);

        // Verify inventory was NOT updated (transaction rolled back)
        $bundleOption = $this->dbHelper->getData('bundle_options', 'bundle_option_id = 100')[0];
        $this->assertEquals(5, $bundleOption['inventory']); // Should still be 5
    }
}

