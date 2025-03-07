<?php

namespace Backend\Core;

use PDO;
use Exception;

class Database
{
    private $connections = []; // Connection pool
    private $maxConnections = 100; // Maximum connections allowed
    private $config;
    private $username;
    private $password;

    public function __construct(array $config, string $username = "root", string $password = "11112222")
    {
        $this->config = $config;
        $this->username = $username;
        $this->password = $password;
    }

    public function getConnection(): PDO
    {
        // Check for available connections
        if (count($this->connections) < $this->maxConnections) {
            $dsn = "mysql:" . http_build_query($this->config, '', ';');
            $pdo = new PDO($dsn, $this->username, $this->password, [
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_PERSISTENT => true,
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_TIMEOUT => 5, // Set a timeout in seconds,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
            $this->connections[] = $pdo; // Store the connection in the pool
            return $pdo;
        }

        // If no connections are available, wait and try again
        while (count($this->connections) >= $this->maxConnections) {
            usleep(100); // Wait for a bit before checking again
        }
        return end($this->connections); // Return the last connection in the pool
    }

    public function releaseConnection(PDO $connection): void
    {
        // Optionally, you can add logic here to clean up connections if needed.
        // Currently, we do not release them; they stay in the pool.
    }

    public function query($query, $params = [])
    {
        $connection = $this->getConnection();
        $statement = $connection->prepare($query);
        $statement->execute($params);
        return $statement;
    }

    public function getOne($statement)
    {
        return $statement->fetch();
    }

    public function getAll($statement)
    {
        return $statement->fetchAll();
    }

    public function lastInsertId(): string
    {
        $connection = $this->getConnection();
        return $connection->lastInsertId();
    }

    public function rowCount($statement): int
    {
        return $statement->rowCount();
    }

    public function beginTransaction(): void
    {
        $connection = $this->getConnection();
        $connection->beginTransaction();
    }

    public function commit(): void
    {
        $connection = $this->getConnection();
        $connection->commit();
    }

    public function rollBack(): void
    {
        $connection = $this->getConnection();
        $connection->rollBack();
    }

    public function inTransaction(): bool
    {
        $connection = $this->getConnection();
        return $connection->inTransaction();
    }
}
