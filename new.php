<?php
class Database {
    private $host = "127.0.0.1"; // Changed from localhost
    private $dbname = "forum";
    private $username = "root";
    private $password = "Keshav@123";
    private $charset = "utf8mb4";
    private $pdo;
    private $error;

    public function __construct() {
        $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];

        try {
            $this->pdo = new PDO($dsn, $this->username, $this->password, $options);
            echo "Connected successfully!";
        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            die("Database connection failed: " . $this->error);
        }
    }

    public function getConnection() {
        return $this->pdo;
    }
}

// Usage
$db = new Database();
$conn = $db->getConnection();
