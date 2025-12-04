<?php

use PHPUnit\Framework\TestCase;

class OrderServiceTest extends TestCase
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
        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 5.0,
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
        $shopItems = [
            [
                'item_id' => 1,
                'name' => 'Test Item',
                'min_porto' => 5.0,
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
                        'min_count' => 2,
                        'max_count' => 5,
                        'inventory' => 20
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
                        'inventory' => 5  // Only 5 in stock
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
}
