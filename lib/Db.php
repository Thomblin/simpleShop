<?php
/**
 * Database wrapper that provides methods for executing queries and managing transactions using mysqli.
 */

class Db implements DatabaseInterface
{
    /**
     * @var mysqli
     */
    private $db;

    /**
     * @param ConfigInterface|mysqli $config
     */
    public function __construct(ConfigInterface|mysqli $config)
    {
        if ($config instanceof mysqli) {
            $this->db = $config;
        } elseif ($config instanceof ConfigInterface) {
            // Suppress warning - we check for failure and throw exception instead
            $this->db = @mysqli_connect(
                $config->getMysqlHost(),
                $config->getMysqlUser(),
                $config->getMysqlPassword(),
                $config->getMysqlDatabase()
            );
            if (!$this->db) {
                throw new RuntimeException('Database connection failed: ' . mysqli_connect_error());
            }
        } else {
            throw new InvalidArgumentException('Constructor requires mysqli or ConfigInterface');
        }
    }

    public function __destruct()
    {
        if ($this->db) {
            $this->db->close();
        }
    }

    /**
     * @param string $query
     * @param array $params
     * @return array
     */
    public function fetchAll(string $query, array $params = []): array
    {
        if (empty($params)) {
            $result = [];
            $queryResult = $this->db->query($query);
            if ($queryResult) {
                while ($row = $queryResult->fetch_assoc()) {
                    $result[] = $row;
                }
                $queryResult->free();
            }
            return $result;
        }

        $result = [];
        $stmt = $this->db->stmt_init();

        if (!$stmt->prepare($query)) {
            throw new RuntimeException('Query preparation failed: ' . $this->db->error);
        }

        if (!empty($params)) {
            $types = '';
            $values = [];

            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b'; // blob
                }
                $values[] = $param;
            }

            $stmt->bind_param($types, ...$values);
        }

        if (!$stmt->execute()) {
            throw new RuntimeException('Query execution failed: ' . $stmt->error);
        }

        $rows = $stmt->get_result();
        while ($row = $rows->fetch_assoc()) {
            $result[] = $row;
        }

        $stmt->close();
        return $result;
    }

    /**
     * @param string $query
     * @param array $params
     * @return bool
     */
    public function execute(string $query, array $params = []): bool
    {
        if (empty($params)) {
            return $this->db->query($query);
        }

        $stmt = $this->db->stmt_init();

        if (!$stmt->prepare($query)) {
            throw new RuntimeException('Query preparation failed: ' . $this->db->error);
        }

        if (!empty($params)) {
            $types = '';
            $values = [];

            foreach ($params as $param) {
                if (is_int($param)) {
                    $types .= 'i';
                } elseif (is_float($param)) {
                    $types .= 'd';
                } elseif (is_string($param)) {
                    $types .= 's';
                } else {
                    $types .= 'b'; // blob
                }
                $values[] = $param;
            }

            $stmt->bind_param($types, ...$values);
        }

        $success = $stmt->execute();
        $stmt->close();

        return $success;
    }


    /**
     * @return bool
     */
    public function beginTransaction(): bool
    {
        return $this->db->begin_transaction();
    }

    /**
     * @return bool
     */
    public function commit(): bool
    {
        return $this->db->commit();
    }

    /**
     * @return bool
     */
    public function rollback(): bool
    {
        return $this->db->rollback();
    }

    /**
     * @return int
     */
    public function lastInsertId(): int
    {
        return $this->db->insert_id;
    }
}