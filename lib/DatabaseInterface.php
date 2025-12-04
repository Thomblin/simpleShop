<?php

interface DatabaseInterface
{
    /**
     * Execute a query and fetch all results
     *
     * @param string $query SQL query with placeholders (?)
     * @param array $params Array of parameters to bind
     * @return array Array of associative arrays
     */
    public function fetchAll($query, $params = []);

    /**
     * Execute a query without returning results (INSERT, UPDATE, DELETE)
     *
     * @param string $query SQL query with placeholders (?)
     * @param array $params Array of parameters to bind
     * @return bool Success status
     */
    public function execute($query, $params = []);

    /**
     * Begin a database transaction
     *
     * @return bool Success status
     */
    public function beginTransaction();

    /**
     * Commit the current transaction
     *
     * @return bool Success status
     */
    public function commit();

    /**
     * Rollback the current transaction
     *
     * @return bool Success status
     */
    public function rollback();

    /**
     * Get the last inserted ID
     *
     * @return int Last insert ID
     */
    public function lastInsertId();
}
