<?php
/**
 * Interface defining database operations for querying, executing statements, and managing transactions.
 */

interface DatabaseInterface
{
    /**
     * Execute a query and fetch all results
     *
     * @param string $query SQL query with placeholders (?)
     * @param array $params Array of parameters to bind
     * @return array Array of associative arrays
     */
    public function fetchAll(string $query, array $params = []): array;

    /**
     * Execute a query without returning results (INSERT, UPDATE, DELETE)
     *
     * @param string $query SQL query with placeholders (?)
     * @param array $params Array of parameters to bind
     * @return bool Success status
     */
    public function execute(string $query, array $params = []): bool;

    /**
     * Begin a database transaction
     *
     * @return bool Success status
     */
    public function beginTransaction(): bool;

    /**
     * Commit the current transaction
     *
     * @return bool Success status
     */
    public function commit(): bool;

    /**
     * Rollback the current transaction
     *
     * @return bool Success status
     */
    public function rollback(): bool;

    /**
     * Get the last inserted ID
     *
     * @return int Last insert ID
     */
    public function lastInsertId(): int;
}
