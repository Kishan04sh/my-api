<?php
// This class handles database connection using PDO
class Database {
    private $host = "localhost";
    private $db_name = "myapi";
    private $username = "root";
    private $password = "";
    public $conn;

    public function connect() {
        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username, $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8mb4");
        } catch (PDOException $e) {
            die("Database Connection Error: " . $e->getMessage());
        }
        return $this->conn;
    }
}
