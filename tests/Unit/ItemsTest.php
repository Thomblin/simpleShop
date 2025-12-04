<?php

use PHPUnit\Framework\TestCase;

class ItemsTest extends TestCase
{
    private $mockDb;
    private $items;

    protected function setUp(): void
    {
        $this->mockDb = new MockDb();
        $this->items = new Items($this->mockDb);
    }

    protected function tearDown(): void
    {
        $this->mockDb->reset();
    }

    public function testConstructorAcceptsDatabaseInterface()
    {
        $items = new Items($this->mockDb);
        $this->assertInstanceOf(Items::class, $items);
    }

    public function testGetItemsReturnsArrayWithBundles()
    {
        $this->mockDb->setData('items', [
            ['item_id' => 1, 'name' => 'Item 1', 'min_porto' => 5.0]
        ]);

        $this->mockDb->setData('bundles', [
            ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Bundle 1', 'price' => 100.0]
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
        $this->mockDb->setData('bundles', [
            ['bundle_id' => 1, 'inventory' => 10]
        ]);

        $this->mockDb->setData('bundle_options', [
            ['bundle_option_id' => 100, 'bundle_id' => 1, 'inventory' => null]
        ]);

        $orders = [
            ['bundle_id' => 1, 'amount' => 5]
        ];

        $result = $this->items->orderItem($orders);

        $this->assertTrue($result);
        $queries = $this->mockDb->getQueries();

        // Verify transaction was used
        $this->assertEquals('beginTransaction', $queries[0]['type']);
        $this->assertEquals('commit', $queries[count($queries) - 1]['type']);
    }

    public function testOrderItemWithInsufficientInventory()
    {
        // Set up test data
        $this->mockDb->setData('bundles', [
            ['bundle_id' => 1, 'inventory' => 5]
        ]);

        $this->mockDb->setData('bundle_options', []);

        $orders = [
            ['bundle_id' => 1, 'amount' => 10]
        ];

        $result = $this->items->orderItem($orders);

        $this->assertFalse($result);
        $queries = $this->mockDb->getQueries();

        // Verify transaction was rolled back
        $hasRollback = false;
        foreach ($queries as $query) {
            if ($query['type'] === 'rollback') {
                $hasRollback = true;
                break;
            }
        }
        $this->assertTrue($hasRollback);
    }

    public function testOrderItemUsesPreparedStatements()
    {
        $this->mockDb->setData('bundles', [
            ['bundle_id' => 1, 'inventory' => 10]
        ]);

        $this->mockDb->setData('bundle_options', []);

        $orders = [
            ['bundle_id' => 1, 'amount' => 5]
        ];

        $this->items->orderItem($orders);

        $queries = $this->mockDb->getQueries();

        // Find UPDATE queries
        $updateQueries = array_filter($queries, function($q) {
            return $q['type'] === 'execute' && stripos($q['query'], 'UPDATE') === 0;
        });

        foreach ($updateQueries as $query) {
            // Verify parameterized query (contains ?)
            $this->assertStringContainsString('?', $query['query']);
            // Verify parameters were passed
            $this->assertNotEmpty($query['params']);
        }
    }

    public function testOrderItemWithBundleOption()
    {
        $this->mockDb->setData('bundles', [
            ['bundle_id' => 1, 'inventory' => 10]
        ]);

        $this->mockDb->setData('bundle_options', [
            ['bundle_option_id' => 100, 'bundle_id' => 1, 'inventory' => 20]
        ]);

        $orders = [
            ['bundle_option_id' => 100, 'amount' => 5]
        ];

        $result = $this->items->orderItem($orders);

        $this->assertTrue($result);

        $queries = $this->mockDb->getQueries();

        // Find bundle_options UPDATE
        $bundleOptionUpdate = array_filter($queries, function($q) {
            return $q['type'] === 'execute' &&
                   strpos($q['query'], 'bundle_options') !== false;
        });

        $this->assertNotEmpty($bundleOptionUpdate);
    }

    public function testOrderItemRollsBackOnException()
    {
        $this->mockDb->setData('bundles', [
            ['bundle_id' => 1, 'inventory' => 10]
        ]);

        $this->mockDb->setData('bundle_options', []);

        // Make DB fail during execution
        $this->mockDb->setShouldFail(true);

        $orders = [
            ['bundle_id' => 1, 'amount' => 5]
        ];

        $this->expectException(RuntimeException::class);

        try {
            $this->items->orderItem($orders);
        } catch (Exception $e) {
            $queries = $this->mockDb->getQueries();

            // Verify rollback was called
            $hasRollback = false;
            foreach ($queries as $query) {
                if ($query['type'] === 'rollback') {
                    $hasRollback = true;
                    break;
                }
            }
            $this->assertTrue($hasRollback);

            throw $e;
        }
    }

    public function testOrderItemWithMultipleOrders()
    {
        $this->mockDb->setData('bundles', [
            ['bundle_id' => 1, 'inventory' => 10],
            ['bundle_id' => 2, 'inventory' => 20]
        ]);

        $this->mockDb->setData('bundle_options', []);

        $orders = [
            ['bundle_id' => 1, 'amount' => 5],
            ['bundle_id' => 2, 'amount' => 10]
        ];

        $result = $this->items->orderItem($orders);

        $this->assertTrue($result);

        $queries = $this->mockDb->getQueries();

        // Count UPDATE queries
        $updateCount = count(array_filter($queries, function($q) {
            return $q['type'] === 'execute' && stripos($q['query'], 'UPDATE') === 0;
        }));

        $this->assertEquals(2, $updateCount);
    }

    public function testOrderItemValidatesAllOrdersBeforeUpdating()
    {
        $this->mockDb->setData('bundles', [
            ['bundle_id' => 1, 'inventory' => 10],
            ['bundle_id' => 2, 'inventory' => 5]
        ]);

        $this->mockDb->setData('bundle_options', []);

        $orders = [
            ['bundle_id' => 1, 'amount' => 5],  // Valid
            ['bundle_id' => 2, 'amount' => 10]  // Invalid - exceeds inventory
        ];

        $result = $this->items->orderItem($orders);

        $this->assertFalse($result);

        $queries = $this->mockDb->getQueries();

        // Verify NO updates were performed (transaction rolled back)
        $updateCount = count(array_filter($queries, function($q) {
            return $q['type'] === 'execute' && stripos($q['query'], 'UPDATE') === 0;
        }));

        $this->assertEquals(0, $updateCount);
    }
}
