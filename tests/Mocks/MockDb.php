<?php

/**
 * Mock database implementation for testing
 */
class MockDb implements DatabaseInterface
{
    private $data = [];
    private $queries = [];
    private $shouldFail = false;
    private $transactionActive = false;
    private $lastInsertId = 0;

    /**
     * Set data to be returned by fetchAll
     */
    public function setData($key, $data)
    {
        $this->data[$key] = $data;
    }

    /**
     * Get all executed queries
     */
    public function getQueries()
    {
        return $this->queries;
    }

    /**
     * Make next operation fail
     */
    public function setShouldFail($fail)
    {
        $this->shouldFail = $fail;
    }

    /**
     * Check if transaction is active
     */
    public function isTransactionActive()
    {
        return $this->transactionActive;
    }

    public function fetchAll($query, $params = [])
    {
        $this->queries[] = ['type' => 'fetchAll', 'query' => $query, 'params' => $params];

        if ($this->shouldFail) {
            throw new RuntimeException('Mock database failure');
        }

        // Match query patterns to return appropriate data
        if (strpos($query, 'SELECT * FROM items') !== false) {
            return $this->data['items'] ?? [];
        }

        if (strpos($query, 'SELECT * FROM bundles') !== false) {
            return $this->data['bundles'] ?? [];
        }

        if (strpos($query, 'SELECT bundle_option_id') !== false) {
            return $this->data['bundle_options'] ?? [];
        }

        // Return data based on query hash
        $hash = md5($query);
        return $this->data[$hash] ?? [];
    }

    public function execute($query, $params = [])
    {
        $this->queries[] = ['type' => 'execute', 'query' => $query, 'params' => $params];

        if ($this->shouldFail) {
            throw new RuntimeException('Mock database failure');
        }

        // Simulate auto-increment for INSERT queries
        if (stripos($query, 'INSERT') === 0) {
            $this->lastInsertId++;
        }

        return true;
    }

    public function beginTransaction()
    {
        $this->queries[] = ['type' => 'beginTransaction'];
        $this->transactionActive = true;
        return true;
    }

    public function commit()
    {
        $this->queries[] = ['type' => 'commit'];
        $this->transactionActive = false;
        return true;
    }

    public function rollback()
    {
        $this->queries[] = ['type' => 'rollback'];
        $this->transactionActive = false;
        return true;
    }

    public function lastInsertId()
    {
        return $this->lastInsertId;
    }

    /**
     * Reset mock state
     */
    public function reset()
    {
        $this->data = [];
        $this->queries = [];
        $this->shouldFail = false;
        $this->transactionActive = false;
        $this->lastInsertId = 0;
    }
}
