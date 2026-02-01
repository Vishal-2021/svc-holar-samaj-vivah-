<?php
class Database {
  //  private $host = "localhost"; // Database host
    private $host = "103.86.177.193"; // Database host
    private $db_name = "bpkrskyu_holarsamaj"; // Database name
    private $username = "bpkrskyu_holarsamaj"; // Database username
    private $password = "O0(GjZHTKC8{"; // Database password
    public $conn;

    public function getConnection() {
        $this->conn = null;

        try {
            $this->conn = new PDO(
                "mysql:host={$this->host};dbname={$this->db_name}",
                $this->username,
                $this->password
            );
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        } catch (PDOException $exception) {
            echo "Connection error: " . $exception->getMessage();
        }

        return $this->conn;
    }
}
