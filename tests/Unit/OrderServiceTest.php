<?php

use PHPUnit\Framework\TestCase;

class OrderServiceTest extends TestCase
{
    private $dbHelper;
    private $db;
    private $items;
    private $priceCalculator;
    private $orderService;

    protected function setUp(): void
    {
        $this->dbHelper = new TestDatabaseHelper();
        
        // Set up schema first (only once, but safe to call multiple times)
        $this->dbHelper->setupSchema();
        
        // Clean up before each test
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
            // Don't close connection here - let PHP handle it
            // Closing causes issues with subsequent tests
        }
    }

    public function testProcessOrderReturnsOrderObject()
    {
        $postData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'street' => '123 Main St',
            'zipcode_location' => '12345 City'
        ];

        $shopItems = [];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertInstanceOf(Order::class, $result);
    }

    public function testProcessOrderExtractsCustomerInfo()
    {
        $postData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'street' => '123 Main St',
            'zipcode_location' => '12345 City',
            'comment' => 'Test comment'
        ];

        $shopItems = [];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertEquals('John Doe', $result->customer['name']);
        $this->assertEquals('john@example.com', $result->customer['email']);
        $this->assertEquals('123 Main St', $result->customer['street']);
        $this->assertEquals('12345 City', $result->customer['zipcode_location']);
        $this->assertEquals('Test comment', $result->customer['comment']);
    }

    public function testProcessOrderWithCollectionByCustomer()
    {
        $postData = [
            'name' => 'John Doe',
            'collectionByTheCustomer' => '1'
        ];

        $shopItems = [];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertTrue($result->collectionByCustomer);
        $this->assertEquals(0.0, $result->porto);
    }

    public function testProcessOrderWithSimpleBundleAmount()
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
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 5.0,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Test Bundle'
                    ]
                ],
                'option_groups' => []
            ]
        ];

        $postData = [
            1 => [
                10 => 3  // item_id => bundle_id => amount
            ]
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertCount(1, $result->items);
        $this->assertEquals(3, $result->items[0]->amount);
        $this->assertEquals(100.0, $result->items[0]->price);
        $this->assertEquals(5.0, $result->porto);
    }

    public function testProcessOrderWithNestedBundleOptions()
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
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 5.0,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Test Bundle'
                    ]
                ],
                'option_groups' => []
            ]
        ];

        $postData = [
            1 => [
                10 => [
                    100 => 2  // item_id => bundle_id => bundle_option_id => amount
                ]
            ]
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertCount(1, $result->items);
        $this->assertEquals(100, $result->items[0]->bundleOptionId);
    }

    public function testValidateCustomerDataWithRequiredFields()
    {
        $customerData = [
            'name' => 'John',
            'email' => 'john@example.com',
            'street' => '123 Main',
            'zipcode_location' => '12345'
        ];

        $requiredFields = [
            'name' => Config::REQUIRED,
            'email' => Config::REQUIRED,
            'street' => Config::REQUIRED,
            'zipcode_location' => Config::REQUIRED
        ];

        $errors = $this->orderService->validateCustomerData($customerData, $requiredFields);

        $this->assertEmpty($errors);
    }

    public function testValidateCustomerDataWithMissingRequiredField()
    {
        $customerData = [
            'name' => '',
            'email' => 'john@example.com'
        ];

        $requiredFields = [
            'name' => Config::REQUIRED,
            'email' => Config::REQUIRED
        ];

        $errors = $this->orderService->validateCustomerData($customerData, $requiredFields);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('req', $errors);
    }

    public function testProcessOrderAppliesMinMaxCount()
    {
        // Set up database
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
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 100.0, 'min_count' => 2, 'max_count' => 5, 'inventory' => 20]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 0,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Test Bundle'
                    ]
                ],
                'option_groups' => []
            ]
        ];

        // Try to order 1 (below min)
        $postData = [1 => [10 => 1]];
        $result = $this->orderService->processOrder($postData, $shopItems);
        $this->assertEquals(2, $result->items[0]->amount); // Should be clamped to min

        // Try to order 10 (above max)
        $postData = [1 => [10 => 10]];
        $result = $this->orderService->processOrder($postData, $shopItems);
        $this->assertEquals(5, $result->items[0]->amount); // Should be clamped to max
    }

    public function testProcessOrderMarksOutOfStockItems()
    {
        // Set up database
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
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 5]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 0,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Test Bundle'
                    ]
                ],
                'option_groups' => []
            ]
        ];

        $postData = [
            1 => [10 => 10]  // Try to order 10
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertTrue($result->items[0]->outOfStock);
        $this->assertEquals(5, $result->items[0]->amount); // Clamped to inventory
    }

    public function testProcessOrderSkipsItemsNotInPostData()
    {
        $shopItems = [
            ['item_id' => 1, 'bundles' => []],
            ['item_id' => 2, 'bundles' => []]
        ];

        $postData = [
            'name' => 'John'
            // No item data
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertCount(0, $result->items);
    }

    public function testProcessOrderWithOptionGroups()
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
                ['option_group_id' => 100, 'name' => 'Size', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 200, 'option_group_id' => 100, 'name' => 'Small', 'display_order' => 0, 'description' => 'Small size']
            ],
            'bundle_options' => [
                ['bundle_option_id' => 300, 'bundle_id' => 10, 'option_id' => 200, 'price' => 90.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 15]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 5.0,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Test Bundle'
                    ]
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
                                'price' => 90.0,
                                'min_count' => 1,
                                'max_count' => 10,
                                'inventory' => 15
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $postData = [
            1 => [
                10 => 3  // Simple amount
            ],
            'item_1_option_100' => 200  // Selected option
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertCount(1, $result->items);
        // Should use option price (90.0) instead of bundle price (100.0)
        $this->assertEquals(90.0, $result->items[0]->price);
    }

    public function testProcessOrderWithNestedBundleOptionsAndSelectedOption()
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
                ['option_group_id' => 100, 'name' => 'Size', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 200, 'option_group_id' => 100, 'name' => 'Small', 'display_order' => 0, 'description' => 'Small size']
            ],
            'bundle_options' => [
                ['bundle_option_id' => 300, 'bundle_id' => 10, 'option_id' => 200, 'price' => 90.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 15]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 5.0,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Test Bundle'
                    ]
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
                                'price' => 90.0,
                                'min_count' => 1,
                                'max_count' => 10,
                                'inventory' => 15
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $postData = [
            1 => [
                10 => [
                    300 => 2  // Nested structure with bundle_option_id
                ]
            ],
            'item_1_option_100' => 200  // Selected option
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertCount(1, $result->items);
        $this->assertEquals(300, $result->items[0]->bundleOptionId);
        $this->assertEquals(90.0, $result->items[0]->price);
    }

    public function testExtractCustomerInfoWithAllFields()
    {
        $postData = [
            'name' => 'John Doe',
            'email' => 'john@example.com',
            'street' => '123 Main St',
            'zipcode_location' => '12345 City',
            'comment' => 'Test comment'
        ];

        $shopItems = [];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertEquals('John Doe', $result->customer['name']);
        $this->assertEquals('john@example.com', $result->customer['email']);
        $this->assertEquals('123 Main St', $result->customer['street']);
        $this->assertEquals('12345 City', $result->customer['zipcode_location']);
        $this->assertEquals('Test comment', $result->customer['comment']);
    }

    public function testExtractCustomerInfoWithMissingFields()
    {
        $postData = [
            'name' => 'John Doe'
            // Missing other fields
        ];

        $shopItems = [];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertEquals('John Doe', $result->customer['name']);
        $this->assertEquals('', $result->customer['email']);
        $this->assertEquals('', $result->customer['street']);
        $this->assertEquals('', $result->customer['zipcode_location']);
        $this->assertEquals('', $result->customer['comment']);
    }

    public function testProcessOrderWithMultipleItemsAndBundles()
    {
        // Set up database
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Item 1', 'picture' => null, 'description' => null, 'min_porto' => 5.0],
                ['item_id' => 2, 'name' => 'Item 2', 'picture' => null, 'description' => null, 'min_porto' => 10.0]
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
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 100.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20],
                ['bundle_option_id' => 200, 'bundle_id' => 20, 'option_id' => 1, 'price' => 200.0, 'min_count' => 1, 'max_count' => 5, 'inventory' => 15]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Item 1',
                'min_porto' => 5.0,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Bundle 1'
                    ]
                ],
                'option_groups' => []
            ],
            [
                'item_id' => 2,
                'name' => 'Item 2',
                'min_porto' => 10.0,
                'bundles' => [
                    [
                        'bundle_id' => 20,
                        'name' => 'Bundle 2'
                    ]
                ],
                'option_groups' => []
            ]
        ];

        $postData = [
            1 => [10 => 3],
            2 => [20 => 2]
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertCount(2, $result->items);
        $this->assertEquals(10.0, $result->porto); // Should be max of min_porto values
    }

    public function testProcessOrderWithNestedBundleOptionsMultipleOptions()
    {
        // Test processNestedBundleOptions with multiple bundle_option_ids
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'picture' => null, 'description' => null, 'min_porto' => 5.0]
            ],
            'bundles' => [
                ['bundle_id' => 10, 'item_id' => 1, 'name' => 'Test Bundle']
            ],
            'option_groups' => [
                ['option_group_id' => 1, 'name' => 'Size', 'display_order' => 0]
            ],
            'options' => [
                ['option_id' => 1, 'option_group_id' => 1, 'name' => 'Small', 'display_order' => 0, 'description' => 'Small size'],
                ['option_id' => 2, 'option_group_id' => 1, 'name' => 'Large', 'display_order' => 1, 'description' => 'Large size']
            ],
            'bundle_options' => [
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20],
                ['bundle_option_id' => 200, 'bundle_id' => 10, 'option_id' => 2, 'price' => 20.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 15]
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
                        'group_id' => 1,
                        'group_name' => 'Size',
                        'options' => [
                            [
                                'option_id' => 1,
                                'option_name' => 'Small',
                                'option_description' => 'Small size',
                                'bundle_id' => 10,
                                'bundle_name' => 'Test Bundle',
                                'bundle_option_id' => 100,
                                'price' => 10.0,
                                'min_count' => 1,
                                'max_count' => 10,
                                'inventory' => 20
                            ],
                            [
                                'option_id' => 2,
                                'option_name' => 'Large',
                                'option_description' => 'Large size',
                                'bundle_id' => 10,
                                'bundle_name' => 'Test Bundle',
                                'bundle_option_id' => 200,
                                'price' => 20.0,
                                'min_count' => 1,
                                'max_count' => 10,
                                'inventory' => 15
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $postData = [
            1 => [
                10 => [
                    100 => 2,  // 2 of Small
                    200 => 3   // 3 of Large
                ]
            ]
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertCount(2, $result->items);
        $this->assertEquals(100, $result->items[0]->bundleOptionId);
        $this->assertEquals(200, $result->items[1]->bundleOptionId);
        $this->assertEquals(2, $result->items[0]->amount);
        $this->assertEquals(3, $result->items[1]->amount);
    }

    public function testProcessOrderWithZeroAmount()
    {
        // Test that zero amounts are not added to order
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
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 0,
                'bundles' => [
                    ['bundle_id' => 10, 'name' => 'Test Bundle']
                ],
                'option_groups' => []
            ]
        ];

        $postData = [
            1 => [10 => 0]  // Zero amount
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        // Should have 1 item because min_count is 1, so 0 gets clamped to 1
        $this->assertCount(1, $result->items);
        $this->assertEquals(1, $result->items[0]->amount); // Clamped to min_count
    }

    public function testValidateCustomerDataWithMultipleRequiredFields()
    {
        $customerData = [
            'name' => 'John',
            'email' => '',
            'street' => ''
        ];

        $requiredFields = [
            'name' => Config::REQUIRED,
            'email' => Config::REQUIRED,
            'street' => Config::REQUIRED
        ];

        $errors = $this->orderService->validateCustomerData($customerData, $requiredFields);

        $this->assertNotEmpty($errors);
        $this->assertArrayHasKey('req', $errors);
    }

    public function testValidateCustomerDataWithNonRequiredFields()
    {
        $customerData = [
            'name' => 'John',
            'comment' => ''  // Empty but not required
        ];

        $requiredFields = [
            'name' => Config::REQUIRED,
            'comment' => ''  // Not required
        ];

        $errors = $this->orderService->validateCustomerData($customerData, $requiredFields);

        $this->assertEmpty($errors);
    }

    public function testProcessOrderWithEmptyShopItems()
    {
        $postData = [
            'name' => 'John Doe',
            'email' => 'john@example.com'
        ];

        $shopItems = [];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertInstanceOf(Order::class, $result);
        $this->assertCount(0, $result->items);
        $this->assertEquals('John Doe', $result->customer['name']);
    }

    public function testProcessOrderWithNegativeAmount()
    {
        // Test that negative amounts are handled
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
                ['bundle_option_id' => 100, 'bundle_id' => 10, 'option_id' => 1, 'price' => 10.0, 'min_count' => 1, 'max_count' => 10, 'inventory' => 20]
            ]
        ]);

        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 0,
                'bundles' => [
                    ['bundle_id' => 10, 'name' => 'Test Bundle']
                ],
                'option_groups' => []
            ]
        ];

        $postData = [
            1 => [10 => -5]  // Negative amount
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        // Negative should be clamped to min_count (1)
        $this->assertCount(1, $result->items);
        $this->assertEquals(1, $result->items[0]->amount);
    }

    public function testProcessOrderWithOptionDescription()
    {
        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 0,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Test Bundle',
                        'price' => 100.0,
                        'min_count' => 1,
                        'max_count' => 10,
                        'inventory' => 20
                    ]
                ],
                'option_groups' => [
                    [
                        'group_id' => 100,
                        'group_name' => 'Size',
                        'options' => [
                            [
                                'option_id' => 200,
                                'option_name' => 'Small',
                                'option_description' => 'Small size description',
                                'bundle_id' => 10,
                                'bundle_name' => 'Test Bundle',
                                'bundle_option_id' => 300,
                                'price' => 90.0,
                                'min_count' => 1,
                                'max_count' => 10,
                                'inventory' => 15
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $postData = [
            1 => [10 => 2],
            'item_1_option_100' => 200
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertCount(1, $result->items);
        // Bundle name should include option description
        $this->assertStringContainsString('Small size description', $result->items[0]->bundleName);
    }

    public function testProcessOrderWithOptionNameWhenDescriptionMissing()
    {
        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 0,
                'bundles' => [
                    [
                        'bundle_id' => 10,
                        'name' => 'Test Bundle',
                        'price' => 100.0,
                        'min_count' => 1,
                        'max_count' => 10,
                        'inventory' => 20
                    ]
                ],
                'option_groups' => [
                    [
                        'group_id' => 100,
                        'group_name' => 'Size',
                        'options' => [
                            [
                                'option_id' => 200,
                                'option_name' => 'Small',
                                'option_description' => '',  // Empty description
                                'bundle_id' => 10,
                                'bundle_name' => 'Test Bundle',
                                'bundle_option_id' => 300,
                                'price' => 90.0,
                                'min_count' => 1,
                                'max_count' => 10,
                                'inventory' => 15
                            ]
                        ]
                    ]
                ]
            ]
        ];

        $postData = [
            1 => [10 => 2],
            'item_1_option_100' => 200
        ];

        $result = $this->orderService->processOrder($postData, $shopItems);

        $this->assertCount(1, $result->items);
        // Should use option_name when description is empty
        $this->assertStringContainsString('Small', $result->items[0]->bundleName);
    }
}
