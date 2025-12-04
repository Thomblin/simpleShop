<?php

/**
 * Helper class for managing test database
 * Sets up schema, loads fixtures, and cleans up after tests
 */
class TestDatabaseHelper
{
    /**
     * @var mysqli
     */
    private $connection;

    /**
     * @var string
     */
    private $database;

    /**
     * @var array
     */
    private $tables = [
        'bundle_options',
        'options',
        'option_groups',
        'bundles',
        'items'
    ];

    /**
     * @param string $host
     * @param string $user
     * @param string $password
     * @param string $database
     */
    public function __construct($host = 'test_mysql', $user = 'testuser', $password = 'testpass', $database = 'test_shop')
    {
        $this->database = $database;
        $this->connection = mysqli_connect($host, $user, $password, $database);
        
        if (!$this->connection) {
            throw new RuntimeException('Failed to connect to test database: ' . mysqli_connect_error());
        }
        
        // Set charset
        mysqli_set_charset($this->connection, 'utf8');
    }

    /**
     * Get database connection
     *
     * @return mysqli
     */
    public function getConnection()
    {
        return $this->connection;
    }

    /**
     * Set up database schema
     */
    public function setupSchema()
    {
        // Check if schema already exists by checking for items table
        $result = mysqli_query($this->connection, "SHOW TABLES LIKE 'items'");
        if ($result && mysqli_num_rows($result) > 0) {
            mysqli_free_result($result);
            return; // Schema already exists
        }
        if ($result) {
            mysqli_free_result($result);
        }
        
        $migrationFile = __DIR__ . '/../../migrate.sql';
        
        if (!file_exists($migrationFile)) {
            throw new RuntimeException("Migration file not found: $migrationFile");
        }

        $sql = file_get_contents($migrationFile);
        
        // Remove SQL comments (-- style and /* */ style)
        $sql = preg_replace('/--.*$/m', '', $sql);
        $sql = preg_replace('/\/\*.*?\*\//s', '', $sql);
        
        // Disable foreign key checks during schema creation
        mysqli_query($this->connection, 'SET FOREIGN_KEY_CHECKS = 0');
        
        // Split by semicolons and execute each statement
        $statements = array_filter(
            array_map('trim', explode(';', $sql)),
            function($stmt) {
                $stmt = trim($stmt);
                // Filter out empty statements and version markers
                return !empty($stmt) && 
                       !preg_match('/^version/i', $stmt);
            }
        );

        foreach ($statements as $statement) {
            $statement = trim($statement);
            if (!empty($statement)) {
                // Fix MySQL 8.4+ issue: Remove DEFAULT '' from TEXT columns
                $statement = preg_replace('/TEXT\s+DEFAULT\s+[\'"]?[\'"]/i', 'TEXT', $statement);
                $statement = preg_replace('/TEXT\s+COMMENT/i', 'TEXT COMMENT', $statement);
                
                if (!mysqli_query($this->connection, $statement)) {
                    $error = mysqli_error($this->connection);
                    // Ignore "table already exists" errors
                    if (strpos($error, 'already exists') === false && 
                        strpos($error, 'Duplicate') === false &&
                        strpos($error, 'Duplicate key') === false) {
                        mysqli_query($this->connection, 'SET FOREIGN_KEY_CHECKS = 1');
                        throw new RuntimeException('Failed to execute migration: ' . $error . "\nStatement: " . substr($statement, 0, 200));
                    }
                }
            }
        }
        
        // Re-enable foreign key checks
        mysqli_query($this->connection, 'SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Clean all tables (truncate)
     */
    public function cleanup()
    {
        // Disable foreign key checks temporarily
        mysqli_query($this->connection, 'SET FOREIGN_KEY_CHECKS = 0');
        
        foreach ($this->tables as $table) {
            // Check if table exists before truncating
            $result = mysqli_query($this->connection, "SHOW TABLES LIKE '$table'");
            if ($result && mysqli_num_rows($result) > 0) {
                mysqli_query($this->connection, "TRUNCATE TABLE `$table`");
            }
            if ($result) {
                mysqli_free_result($result);
            }
        }
        
        // Re-enable foreign key checks
        mysqli_query($this->connection, 'SET FOREIGN_KEY_CHECKS = 1');
    }

    /**
     * Insert test data
     *
     * @param array $data Array with table names as keys and arrays of rows as values
     */
    public function insertData(array $data)
    {
        foreach ($data as $table => $rows) {
            if (empty($rows)) {
                continue;
            }

            foreach ($rows as $row) {
                $columns = array_keys($row);
                $values = array_values($row);
                
                // Handle NULL values - replace with NULL in SQL
                $placeholders = [];
                $types = '';
                $bindValues = [];
                
                foreach ($values as $value) {
                    if (is_null($value)) {
                        $placeholders[] = 'NULL';
                    } else {
                        $placeholders[] = '?';
                        if (is_int($value)) {
                            $types .= 'i';
                        } elseif (is_float($value)) {
                            $types .= 'd';
                        } else {
                            $types .= 's';
                        }
                        $bindValues[] = $value;
                    }
                }
                
                $columnList = '`' . implode('`, `', $columns) . '`';
                $placeholderStr = implode(', ', $placeholders);
                
                $stmt = mysqli_prepare($this->connection, 
                    "INSERT INTO `$table` ($columnList) VALUES ($placeholderStr)");
                
                if (!$stmt) {
                    throw new RuntimeException('Failed to prepare statement: ' . mysqli_error($this->connection));
                }

                // Only bind if there are non-NULL values
                if (!empty($bindValues)) {
                    mysqli_stmt_bind_param($stmt, $types, ...$bindValues);
                }
                
                if (!mysqli_stmt_execute($stmt)) {
                    throw new RuntimeException('Failed to execute insert: ' . mysqli_stmt_error($stmt));
                }
                
                mysqli_stmt_close($stmt);
            }
        }
    }

    /**
     * Get data from a table
     *
     * @param string $table
     * @param string $where Optional WHERE clause
     * @return array
     */
    public function getData($table, $where = '')
    {
        $query = "SELECT * FROM `$table`";
        if (!empty($where)) {
            $query .= " WHERE $where";
        }
        
        $result = mysqli_query($this->connection, $query);
        if (!$result) {
            throw new RuntimeException('Failed to query table: ' . mysqli_error($this->connection));
        }

        $data = [];
        while ($row = mysqli_fetch_assoc($result)) {
            $data[] = $row;
        }
        
        mysqli_free_result($result);
        return $data;
    }

    /**
     * Close connection
     */
    public function close()
    {
        if ($this->connection) {
            mysqli_close($this->connection);
        }
    }

    /**
     * Get a Db instance for testing
     *
     * @return Db
     */
    public function getDb()
    {
        return new Db($this->connection);
    }
}

