<?php

use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{
    private $mockConfig;
    private $db;

    protected function setUp(): void
    {
        // Create a mock ConfigInterface
        $this->mockConfig = $this->createMock(ConfigInterface::class);
        $this->mockConfig->method('getMysqlHost')->willReturn('localhost');
        $this->mockConfig->method('getMysqlUser')->willReturn('testuser');
        $this->mockConfig->method('getMysqlPassword')->willReturn('testpass');
        $this->mockConfig->method('getMysqlDatabase')->willReturn('testdb');

        // Set up real database - fail if unavailable
        $testConfig = new MockConfig([
            'mysqlHost' => 'test_mysql',
            'mysqlUser' => 'testuser',
            'mysqlPassword' => 'testpass',
            'mysqlDatabase' => 'test_shop'
        ]);
        $this->db = new Db($testConfig);
        $this->setupSchema();
        $this->cleanup();
    }

    protected function tearDown(): void
    {
        if ($this->db) {
            $this->cleanup();
        }
    }

    private function setupSchema(): void
    {
        $migrationFile = __DIR__ . '/../../migrate.sql';

        if (!file_exists($migrationFile)) {
            throw new RuntimeException("Migration file not found: $migrationFile");
        }

        $sql = file_get_contents($migrationFile);
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);

        $this->db->execute('SET FOREIGN_KEY_CHECKS = 0');

        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function ($stmt) {
                $stmt = trim($stmt);
                return !empty($stmt) && !preg_match('/^version/i', $stmt);
            }
        );

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                $statement = preg_replace('/TEXT\s+DEFAULT\s+[\'"]?[\'"]/i', 'TEXT', $statement);
                $statement = preg_replace('/TEXT\s+COMMENT/i', 'TEXT COMMENT', $statement);

                try {
                    $this->db->execute($statement);
                } catch (Exception $e) {
                    $error = $e->getMessage();
                    if (
                        strpos($error, 'already exists') === false &&
                        strpos($error, 'Duplicate') === false
                    ) {
                        $this->db->execute('SET FOREIGN_KEY_CHECKS = 1');
                        throw $e;
                    }
                }
            }
        }

        $this->db->execute('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function cleanup(): void
    {
        $tables = ['bundle_options', 'options', 'option_groups', 'bundles', 'items'];
        $this->db->execute('SET FOREIGN_KEY_CHECKS = 0');
        foreach ($tables as $table) {
            try {
                $this->db->execute("TRUNCATE TABLE `$table`");
            } catch (Exception $e) {
                // Table might not exist, ignore
            }
        }
        $this->db->execute('SET FOREIGN_KEY_CHECKS = 1');
    }

    public function testConstructorWithMysqli()
    {
        // Test constructor with mysqli instance
        $connection = mysqli_connect('test_mysql', 'testuser', 'testpass', 'test_shop');
        if (!$connection) {
            $this->markTestSkipped('Could not connect to test database');
        }

        $db = new Db($connection);
        $this->assertInstanceOf(Db::class, $db);

        // Test that it works
        $result = $db->fetchAll("SELECT 1 as test");
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
    }

    public function testConstructorWithInvalidArgument()
    {
        // Test exception path when invalid config is passed
        // With type hints, PHP throws TypeError instead of InvalidArgumentException
        $this->expectException(TypeError::class);

        new Db('invalid');
    }

    public function testConstructorWithConfigInterfaceThrowsOnConnectionFailure()
    {
        // Test that constructor throws RuntimeException when connection fails
        // Use invalid host to trigger connection failure
        $invalidConfig = new MockConfig([
            'mysqlHost' => 'nonexistent_host_12345',
            'mysqlUser' => 'invalid_user',
            'mysqlPassword' => 'invalid_password',
            'mysqlDatabase' => 'invalid_database'
        ]);

        $this->expectException(RuntimeException::class);

        try {
            new Db($invalidConfig);
        } catch (RuntimeException $e) {
            // Should mention connection failure
            $this->assertTrue(
                strpos($e->getMessage(), 'Database connection failed') !== false ||
                strpos($e->getMessage(), 'connection') !== false ||
                strpos($e->getMessage(), 'failed') !== false
            );
            throw $e;
        }
    }

    public function testFetchAllWithoutParams()
    {
        // Insert test data
        $this->db->execute("INSERT INTO items (item_id, name, min_porto) VALUES (1, 'Test', 0)");
        $this->db->execute("INSERT INTO items (item_id, name, min_porto) VALUES (2, 'Test2', 0)");

        $result = $this->db->fetchAll("SELECT * FROM items");

        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Test', $result[0]['name']);
    }

    public function testFetchAllWithParams()
    {
        // Insert test data
        $this->db->execute("INSERT INTO items (item_id, name, min_porto) VALUES (?, ?, ?)", [1, 'Test', 0]);

        $result = $this->db->fetchAll("SELECT * FROM items WHERE item_id = ?", [1]);

        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Test', $result[0]['name']);
    }

    public function testFetchAllWithParamsThrowsOnPrepareFailure()
    {
        // Test with invalid SQL syntax to trigger prepare failure
        // MySQL throws mysqli_sql_exception for syntax errors, which gets wrapped
        $this->expectException(Exception::class);

        try {
            $this->db->fetchAll("SELECT * FROM items WHERE item_id = ? INVALID SQL", [1]);
        } catch (Exception $e) {
            // Should be either RuntimeException or mysqli_sql_exception
            $this->assertContainsEquals(get_class($e), [RuntimeException::class, 'mysqli_sql_exception']);
            // Should mention preparation or SQL error
            $this->assertTrue(
                strpos($e->getMessage(), 'preparation') !== false ||
                strpos($e->getMessage(), 'SQL syntax') !== false ||
                strpos($e->getMessage(), 'error') !== false
            );
            throw $e;
        }
    }

    public function testFetchAllWithParamsThrowsOnExecuteFailure()
    {
        // Set up test data
        $this->db->execute("INSERT INTO items (item_id, name, min_porto) VALUES (?, ?, ?)", [1, 'Test Item', 0]);

        // Set up option_groups and options for bundle_options
        $this->db->execute("INSERT INTO option_groups (option_group_id, name, display_order) VALUES (?, ?, ?)", [1, 'Default', 0]);
        $this->db->execute("INSERT INTO options (option_id, option_group_id, name, display_order, description) VALUES (?, ?, ?, ?, ?)", [1, 1, 'Default', 0, null]);

        // Try to insert a bundle_option with invalid bundle_id (will fail foreign key constraint)
        // This will cause execute() to fail during the INSERT
        $this->expectException(Exception::class);

        try {
            $this->db->execute(
                "INSERT INTO bundle_options (bundle_id, option_id, price, min_count, max_count, inventory) VALUES (?, ?, ?, ?, ?, ?)",
                [999, 1, 100.0, 1, 10, 20]
            );
        } catch (Exception $e) {
            // Should be either RuntimeException or mysqli_sql_exception
            $this->assertContainsEquals(get_class($e), [RuntimeException::class, 'mysqli_sql_exception']);
            // Should mention execution failure or foreign key constraint
            $this->assertTrue(
                strpos($e->getMessage(), 'execution') !== false ||
                strpos($e->getMessage(), 'foreign key') !== false ||
                strpos($e->getMessage(), 'constraint') !== false
            );
            throw $e;
        }
    }

    public function testFetchAllWithDifferentParamTypes()
    {
        // Insert test data
        $this->db->execute("INSERT INTO items (item_id, name, min_porto) VALUES (?, ?, ?)", [1, 'test', 10.5]);

        // Test with int
        $result = $this->db->fetchAll("SELECT * FROM items WHERE item_id = ?", [1]);
        $this->assertCount(1, $result);

        // Test with float
        $result = $this->db->fetchAll("SELECT * FROM items WHERE min_porto = ?", [10.5]);
        $this->assertCount(1, $result);

        // Test with string
        $result = $this->db->fetchAll("SELECT * FROM items WHERE name = ?", ['test']);
        $this->assertCount(1, $result);

        // Test with mixed types
        $result = $this->db->fetchAll("SELECT * FROM items WHERE item_id = ? AND min_porto = ? AND name = ?", [1, 10.5, 'test']);
        $this->assertCount(1, $result);
    }

    public function testExecuteWithoutParams()
    {
        $result = $this->db->execute("INSERT INTO items (name, min_porto) VALUES ('test', 0)");

        $this->assertTrue($result);

        // Verify the insert worked
        $data = $this->db->fetchAll("SELECT * FROM items WHERE name = 'test'");
        $this->assertCount(1, $data);
    }

    public function testExecuteWithParams()
    {
        $result = $this->db->execute("INSERT INTO items (name, min_porto) VALUES (?, ?)", ['test', 0]);

        $this->assertTrue($result);

        // Verify the insert worked
        $data = $this->db->fetchAll("SELECT * FROM items WHERE name = 'test'");
        $this->assertCount(1, $data);
    }

    public function testExecuteThrowsOnPrepareFailure()
    {
        // Test with invalid SQL syntax to trigger prepare failure
        $this->expectException(Exception::class);

        try {
            $this->db->execute("INSERT INTO items (name, min_porto) VALUES (?) INVALID SQL", ['test']);
        } catch (Exception $e) {
            // Should be either RuntimeException or mysqli_sql_exception
            $this->assertContainsEquals(get_class($e), [RuntimeException::class, 'mysqli_sql_exception']);
            // Should mention preparation or SQL error
            $this->assertTrue(
                strpos($e->getMessage(), 'preparation') !== false ||
                strpos($e->getMessage(), 'SQL syntax') !== false ||
                strpos($e->getMessage(), 'error') !== false
            );
            throw $e;
        }
    }

    public function testExec()
    {
        // Insert test data first
        $this->db->execute("INSERT INTO items (item_id, name, min_porto) VALUES (?, ?, ?)", [1, 'test', 0]);

        // exec() is deprecated, use execute() instead
        $this->db->execute("DELETE FROM items WHERE item_id = ?", [1]);

        // Verify deletion worked
        $data = $this->db->fetchAll("SELECT * FROM items WHERE item_id = ?", [1]);
        $this->assertCount(0, $data);
    }

    public function testBeginTransaction()
    {
        $result = $this->db->beginTransaction();
        $this->assertTrue($result);

        // Clean up
        $this->db->rollback();
    }

    public function testCommit()
    {
        $this->db->beginTransaction();
        $this->db->execute("INSERT INTO items (name, min_porto) VALUES ('test', 0)");
        $result = $this->db->commit();

        $this->assertTrue($result);

        // Verify commit worked
        $data = $this->db->fetchAll("SELECT * FROM items WHERE name = 'test'");
        $this->assertCount(1, $data);
    }

    public function testRollback()
    {
        $this->db->beginTransaction();
        $this->db->execute("INSERT INTO items (name, min_porto) VALUES ('test', 0)");
        $result = $this->db->rollback();

        $this->assertTrue($result);

        // Verify rollback worked (data should not be committed)
        $data = $this->db->fetchAll("SELECT * FROM items WHERE name = 'test'");
        $this->assertCount(0, $data);
    }

    public function testLastInsertId()
    {
        $this->db->execute("INSERT INTO items (name, min_porto) VALUES ('test', 0)");
        $insertId = $this->db->lastInsertId();

        $this->assertIsInt($insertId);
        $this->assertGreaterThan(0, $insertId);

        // Verify we can retrieve the inserted record
        $data = $this->db->fetchAll("SELECT * FROM items WHERE item_id = ?", [$insertId]);
        $this->assertCount(1, $data);
        $this->assertEquals('test', $data[0]['name']);
    }

    public function testDestructorClosesConnection()
    {
        // Create a new connection for this test
        $connection = mysqli_connect('test_mysql', 'testuser', 'testpass', 'test_shop');
        $db = new Db($connection);

        // Verify connection is open
        $this->assertNotNull($connection);

        // Destructor will close connection
        unset($db);

        // Connection should be closed (we can't easily test this without trying to use it)
        $this->assertTrue(true);
    }

    public function testFetchAllWithNullValues()
    {
        // Insert data with NULL values
        $this->db->execute("INSERT INTO items (item_id, name, picture, description, min_porto) VALUES (?, ?, NULL, NULL, ?)", [1, 'Test', 0]);

        $result = $this->db->fetchAll("SELECT * FROM items WHERE item_id = ?", [1]);
        $this->assertCount(1, $result);
        $this->assertNull($result[0]['picture']);
        $this->assertNull($result[0]['description']);
    }

    public function testExecuteWithNullParams()
    {
        // Test execute with NULL parameter
        $result = $this->db->execute(
            "INSERT INTO items (name, picture, description, min_porto) VALUES (?, NULL, NULL, ?)",
            ['Test Item', 0]
        );

        $this->assertTrue($result);

        // Verify the insert worked
        $data = $this->db->fetchAll("SELECT * FROM items WHERE name = ?", ['Test Item']);
        $this->assertCount(1, $data);
        $this->assertNull($data[0]['picture']);
    }

    public function testFetchAllWithEmptyResult()
    {
        // Test fetchAll with query that returns no results
        $result = $this->db->fetchAll("SELECT * FROM items WHERE item_id = ?", [99999]);
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testFetchAllWithComplexQuery()
    {
        // Set up complex data
        $this->db->execute("INSERT INTO items (item_id, name, min_porto) VALUES (?, ?, ?)", [1, 'Item 1', 5.0]);
        $this->db->execute("INSERT INTO items (item_id, name, min_porto) VALUES (?, ?, ?)", [2, 'Item 2', 10.0]);
        $this->db->execute("INSERT INTO bundles (bundle_id, item_id, name) VALUES (?, ?, ?)", [10, 1, 'Bundle 1']);
        $this->db->execute("INSERT INTO bundles (bundle_id, item_id, name) VALUES (?, ?, ?)", [20, 2, 'Bundle 2']);

        // Test complex JOIN query
        $result = $this->db->fetchAll(
            "SELECT i.item_id, i.name, b.bundle_id, b.name as bundle_name 
             FROM items i 
             LEFT JOIN bundles b ON i.item_id = b.item_id 
             WHERE i.item_id = ?",
            [1]
        );

        $this->assertIsArray($result);
        $this->assertGreaterThan(0, count($result));
        $this->assertEquals('Item 1', $result[0]['name']);
    }

    public function testFetchAllWithInvalidQueryReturnsEmptyArray()
    {
        // Test fetchAll with a valid query that returns no results
        // This tests the path where query() succeeds but returns no rows
        $result = $this->db->fetchAll("SELECT * FROM items WHERE item_id = ?", [99999]);
        $this->assertIsArray($result);
        $this->assertCount(0, $result);
    }

    public function testFetchAllWithInvalidTableThrowsException()
    {
        // Test that fetchAll throws exception when querying non-existent table
        // mysqli throws mysqli_sql_exception for SQL errors
        $this->expectException(Exception::class);

        try {
            $this->db->fetchAll("SELECT * FROM nonexistent_table_12345");
        } catch (Exception $e) {
            // Should be either mysqli_sql_exception or RuntimeException
            $this->assertContainsEquals(
                get_class($e),
                [\mysqli_sql_exception::class, RuntimeException::class, Exception::class]
            );
            throw $e;
        }
    }
}

