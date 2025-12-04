<?php

use PHPUnit\Framework\TestCase;

/**
 * Tests for OrderService nested bundle options processing
 */
class OrderServiceNestedTest extends TestCase
{
    private $dbHelper;
    private $db;
    private $items;
    private $priceCalculator;
    private $orderService;

    protected function setUp(): void
    {
        $this->dbHelper = new TestDatabaseHelper();
        $this->dbHelper->setupSchema();
        $this->dbHelper->cleanup();
        
        $this->db = $this->dbHelper->getDb();
        $this->items = new Items($this->db);
        $this->priceCalculator = new PriceCalculator('€');
        $this->orderService = new OrderService($this->items, $this->priceCalculator);
    }

    protected function tearDown(): void
    {
        if ($this->dbHelper) {
            $this->dbHelper->cleanup();
        }
    }

    public function testProcessOrderWithNestedBundleOptionsStructure()
    {
        // Set up database with option groups
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'picture' => null, 'description' => null, 'min_porto' => 5.0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 100, 'name' => 'Size', 'display_order' => 1]
            ],
            'options' => [
                ['option_id' => 200, 'option_group_id' => 100, 'name' => 'Small', 'display_order' => 1, 'description' => 'Small size']
            ],
            'bundle_options' => [
                ['bundle_option_id' => 300, 'bundle_id' => 10, 'option_id' => 200, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 50],
                ['bundle_option_id' => 301, 'bundle_id' => 10, 'option_id' => 200, 'price' => 15.0, 'min_count' => 1, 'max_count' => 5, 'inventory' => 30]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 5.0,
                'bundles' => [
                    ['bundle_id' => 10, 'name' => 'Test Bundle']
                ],
                'option_groups' => [
                    [
                        'group_id' => 100,
                        'group_name' => 'Size',
                        'options' => [
                            [
                                'option_id' => 200,
                                'option_name' => 'Small',
                                'option_description' => 'Small size',
                                'bundle_id' => 10,
                                'bundle_name' => 'Test Bundle',
                                'bundle_option_id' => 300,
                                'price' => 10.0,
                                'min_count' => 1,
                                'max_count' => 10,
                                'inventory' => 50
                            ],
                            [
                                'option_id' => 200,
                                'option_name' => 'Small',
                                'option_description' => 'Small size',
                                'bundle_id' => 10,
                                'bundle_name' => 'Test Bundle',
                                'bundle_option_id' => 301,
                                'price' => 15.0,
                                'min_count' => 1,
                                'max_count' => 5,
                                'inventory' => 30
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Use nested structure: item_id => bundle_id => bundle_option_id => amount
        $postData = [
            1 => [
                10 => [
                    300 => 3,  // Order 3 of bundle_option 300
                    301 => 2   // Order 2 of bundle_option 301
                ]
            ]
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertCount(2, $result->items);
        
        // Verify first order item
        $this->assertEquals(300, $result->items[0]->bundleOptionId);
        $this->assertEquals(3, $result->items[0]->amount);
        $this->assertEquals(10.0, $result->items[0]->price);
        
        // Verify second order item
        $this->assertEquals(301, $result->items[1]->bundleOptionId);
        $this->assertEquals(2, $result->items[1]->amount);
        $this->assertEquals(15.0, $result->items[1]->price);
    }

    public function testProcessOrderWithNestedStructureAndSelectedOption()
    {
        // Set up database
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'picture' => null, 'description' => null, 'min_porto' => 5.0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 100, 'name' => 'Size', 'display_order' => 1]
            ],
            'options' => [
                ['option_id' => 200, 'option_group_id' => 100, 'name' => 'Small', 'display_order' => 1, 'description' => 'Small size']
            ],
            'bundle_options' => [
                ['bundle_option_id' => 300, 'bundle_id' => 10, 'option_id' => 200, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 50]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 5.0,
                'bundles' => [
                    ['bundle_id' => 10, 'name' => 'Test Bundle']
                ],
                'option_groups' => [
                    [
                        'group_id' => 100,
                        'group_name' => 'Size',
                        'options' => [
                            [
                                'option_id' => 200,
                                'option_name' => 'Small',
                                'option_description' => 'Small size',
                                'bundle_id' => 10,
                                'bundle_name' => 'Test Bundle',
                                'bundle_option_id' => 300,
                                'price' => 10.0,
                                'min_count' => 1,
                                'max_count' => 10,
                                'inventory' => 50
                            ]
                        ]
                    ]
                ]
            ]
        ];

        // Use nested structure with selected option
        $postData = [
            1 => [
                10 => [
                    300 => 5
                ]
            ],
            'item_1_option_100' => 200  // Selected option
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertCount(1, $result->items);
        $this->assertEquals(300, $result->items[0]->bundleOptionId);
    }

    public function testProcessOrderWithNestedStructureRespectsMinMaxCount()
    {
        // Set up database
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'picture' => null, 'description' => null, 'min_porto' => 5.0]
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
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.0, 'min_count' => 2, 'max_count' => 5, 'inventory' => 50]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 5.0,
                'bundles' => [
                    ['bundle_id' => 10, 'name' => 'Test Bundle']
                ],
                'option_groups' => []
            ]
        ];

        // Try to order 1 (below min) - should be clamped to 2
        $postData = [
            1 => [
                10 => [
                    100 => 1
                ]
            ]
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);
        $this->assertEquals(2, $result->items[0]->amount);

        // Try to order 10 (above max) - should be clamped to 5
        $postData = [
            1 => [
                10 => [
                    100 => 10
                ]
            ]
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);
        $this->assertEquals(5, $result->items[0]->amount);
    }

    public function testProcessOrderWithNestedStructureHandlesOutOfStock()
    {
        // Set up database with low inventory
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'picture' => null, 'description' => null, 'min_porto' => 5.0]
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
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 3]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 5.0,
                'bundles' => [
                    ['bundle_id' => 10, 'name' => 'Test Bundle']
                ],
                'option_groups' => []
            ]
        ];

        // Try to order 10 (only 3 in stock)
        $postData = [
            1 => [
                10 => [
                    100 => 10
                ]
            ]
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);
        
        $this->assertTrue($result->items[0]->outOfStock);
        $this->assertEquals(3, $result->items[0]->amount); // Clamped to inventory
    }
}



