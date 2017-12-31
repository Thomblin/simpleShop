<?php

class Db
{
    /**
     * @var mysqli
     */
    private $db;

    /**
     * @param Config $config
     */
    public function __construct(Config $config)
    {
        $this->db = mysqli_connect(
            $config->mysqlHost,
            $config->mysqlUser,
            $config->mysqlPassword,
            $config->mysqlDatabase
        );
    }

    public function __destruct()
    {
        $this->db->close();
    }

    public function fetchAll($query, $params = [])
    {
        $result = [];
        $stmt = $this->db->stmt_init();

        if ($stmt->prepare($query)) {

            foreach ($params as $value => $type) {
                $stmt->bind_param($type, $value);
            }

            $stmt->execute();
            $rows = $stmt->get_result();

            while($row = $rows->fetch_assoc()) {
                $result[] = $row;
            }

            $stmt->close();
        }

        return $result;
    }
}