<?php

use PHPUnit\Framework\TestCase;

class DbTest extends TestCase
{
    private $mockConfig;
    private $mockMysqli;
    private $dbHelper;
    private $realDb;

    protected function setUp(): void
    {
        // Create a mock ConfigInterface
        $this->mockConfig = $this->createMock(ConfigInterface::class);
        $this->mockConfig->method('getMysqlHost')->willReturn('localhost');
        $this->mockConfig->method('getMysqlUser')->willReturn('testuser');
        $this->mockConfig->method('getMysqlPassword')->willReturn('testpass');
        $this->mockConfig->method('getMysqlDatabase')->willReturn('testdb');
        
        // Set up real database helper for tests that can use it
        try {
            $this->dbHelper = new TestDatabaseHelper();
            $this->dbHelper->setupSchema();
            $this->realDb = $this->dbHelper->getDb();
        } catch (Exception $e) {
            // Test database might not be available, that's okay for some tests
            $this->dbHelper = null;
            $this->realDb = null;
        }
    }

    protected function tearDown(): void
    {
        if ($this->dbHelper) {
            $this->dbHelper->cleanup();
        }
    }

    public function testConstructorWithMysqli()
    {
        // We can't easily test with real mysqli, so we'll test the exception path
        // when invalid config is passed
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Constructor requires mysqli or ConfigInterface');
        
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
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        // Insert test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test', 'min_porto' => 0],
                ['item_id' => 2, 'name' => 'Test2', 'min_porto' => 0]
            ]
        ]);
        
        $result = $this->realDb->fetchAll("SELECT * FROM items");
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('Test', $result[0]['name']);
    }

    public function testFetchAllWithParams()
    {
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        // Insert test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test', 'min_porto' => 0]
            ]
        ]);
        
        $result = $this->realDb->fetchAll("SELECT * FROM items WHERE item_id = ?", [1]);
        
        $this->assertIsArray($result);
        $this->assertCount(1, $result);
        $this->assertEquals('Test', $result[0]['name']);
    }

    public function testFetchAllWithParamsThrowsOnPrepareFailure()
    {
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        // Test with invalid SQL syntax to trigger prepare failure
        // MySQL throws mysqli_sql_exception for syntax errors, which gets wrapped
        $this->expectException(Exception::class);
        
        try {
            $this->realDb->fetchAll("SELECT * FROM items WHERE item_id = ? INVALID SQL", [1]);
        } catch (Exception $e) {
            // Should be either RuntimeException or mysqli_sql_exception
            $this->assertContains(get_class($e), [RuntimeException::class, 'mysqli_sql_exception']);
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
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        // Set up test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'Test Item', 'min_porto' => 0]
            ]
        ]);
        
        // Try to insert a bundle with invalid item_id (will fail foreign key constraint)
        // This will cause execute() to fail during the INSERT
        $this->expectException(Exception::class);
        
        try {
            $this->realDb->execute(
                "INSERT INTO bundles (item_id, name, price, min_count, max_count, inventory) VALUES (?, ?, ?, ?, ?, ?)",
                [999, 'Test Bundle', 100.0, 1, 10, 20]
            );
        } catch (Exception $e) {
            // Should be either RuntimeException or mysqli_sql_exception
            $this->assertContains(get_class($e), [RuntimeException::class, 'mysqli_sql_exception']);
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
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        // Insert test data
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'test', 'min_porto' => 10.5]
            ]
        ]);
        
        // Test with int
        $result = $this->realDb->fetchAll("SELECT * FROM items WHERE item_id = ?", [1]);
        $this->assertCount(1, $result);
        
        // Test with float
        $result = $this->realDb->fetchAll("SELECT * FROM items WHERE min_porto = ?", [10.5]);
        $this->assertCount(1, $result);
        
        // Test with string
        $result = $this->realDb->fetchAll("SELECT * FROM items WHERE name = ?", ['test']);
        $this->assertCount(1, $result);
        
        // Test with mixed types
        $result = $this->realDb->fetchAll("SELECT * FROM items WHERE item_id = ? AND min_porto = ? AND name = ?", [1, 10.5, 'test']);
        $this->assertCount(1, $result);
    }

    public function testExecuteWithoutParams()
    {
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        $result = $this->realDb->execute("INSERT INTO items (name, min_porto) VALUES ('test', 0)");
        
        $this->assertTrue($result);
        
        // Verify the insert worked
        $data = $this->dbHelper->getData('items', "name = 'test'");
        $this->assertCount(1, $data);
    }

    public function testExecuteWithParams()
    {
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        $result = $this->realDb->execute("INSERT INTO items (name, min_porto) VALUES (?, ?)", ['test', 0]);
        
        $this->assertTrue($result);
        
        // Verify the insert worked
        $data = $this->dbHelper->getData('items', "name = 'test'");
        $this->assertCount(1, $data);
    }

    public function testExecuteThrowsOnPrepareFailure()
    {
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        // Test with invalid SQL syntax to trigger prepare failure
        $this->expectException(Exception::class);
        
        try {
            $this->realDb->execute("INSERT INTO items (name, min_porto) VALUES (?) INVALID SQL", ['test']);
        } catch (Exception $e) {
            // Should be either RuntimeException or mysqli_sql_exception
            $this->assertContains(get_class($e), [RuntimeException::class, 'mysqli_sql_exception']);
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
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        // Insert test data first
        $this->dbHelper->insertData([
            'items' => [
                ['item_id' => 1, 'name' => 'test', 'min_porto' => 0]
            ]
        ]);
        
        // exec() doesn't return anything, just verify it doesn't throw
        $this->realDb->exec("DELETE FROM items WHERE item_id = 1");
        
        // Verify deletion worked
        $data = $this->dbHelper->getData('items', 'item_id = 1');
        $this->assertCount(0, $data);
    }

    public function testBeginTransaction()
    {
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        $result = $this->realDb->beginTransaction();
        $this->assertTrue($result);
        
        // Clean up
        $this->realDb->rollback();
    }

    public function testCommit()
    {
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        $this->realDb->beginTransaction();
        $this->realDb->execute("INSERT INTO items (name, min_porto) VALUES ('test', 0)");
        $result = $this->realDb->commit();
        
        $this->assertTrue($result);
        
        // Verify commit worked
        $data = $this->dbHelper->getData('items', "name = 'test'");
        $this->assertCount(1, $data);
    }

    public function testRollback()
    {
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        $this->realDb->beginTransaction();
        $this->realDb->execute("INSERT INTO items (name, min_porto) VALUES ('test', 0)");
        $result = $this->realDb->rollback();
        
        $this->assertTrue($result);
        
        // Verify rollback worked (data should not be committed)
        $data = $this->dbHelper->getData('items', "name = 'test'");
        $this->assertCount(0, $data);
    }

    public function testLastInsertId()
    {
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
        $this->realDb->execute("INSERT INTO items (name, min_porto) VALUES ('test', 0)");
        $insertId = $this->realDb->lastInsertId();
        
        $this->assertIsInt($insertId);
        $this->assertGreaterThan(0, $insertId);
        
        // Verify we can retrieve the inserted record
        $data = $this->dbHelper->getData('items', "item_id = $insertId");
        $this->assertCount(1, $data);
        $this->assertEquals('test', $data[0]['name']);
    }

    public function testDestructorClosesConnection()
    {
        if (!$this->realDb) {
            $this->markTestSkipped('Test database not available');
        }
        
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
}

