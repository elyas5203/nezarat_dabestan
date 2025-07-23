<?php
class Database {
    private static $instance = null;
    private $connection;

    private $host = 'localhost';
    private $user = 'root';
    private $pass = '';
    private $name = 'dabestan_db';

    // The constructor is private to prevent initiation with 'new'
    private function __construct() {
        $this->connection = new mysqli($this->host, $this->user, $this->pass, $this->name);

        if ($this->connection->connect_error) {
            die("Connection failed: " . $this->connection->connect_error);
        }
        $this->connection->set_charset("utf8mb4");
    }

    // The single point of access to the instance
    public static function getInstance() {
        if (self::$instance == null) {
            self::$instance = new Database();
        }
        return self::$instance;
    }

    // Get the mysqli connection
    public function getConnection() {
        return $this->connection;
    }

    // Prevent cloning of the instance
    private function __clone() {}

    // Close connection automatically when the script ends
    public function __destruct() {
        // This destructor can cause issues when the script exits and tries to close a connection
        // that might have already been closed, especially in CLI scripts.
        // The connection will be closed automatically by PHP when the script finishes.
        // It's safer to remove the explicit close() call here to avoid "already closed" errors.
        // if ($this->connection && $this->connection->thread_id) {
        //     $this->connection->close();
        // }
    }

    public function closeConnection() {
        if ($this->connection && $this->connection->thread_id) {
            $this->connection->close();
            self::$instance = null; // Reset instance after closing
        }
    }
}

// Global function to get the database connection easily
function get_db_connection() {
    return Database::getInstance()->getConnection();
}

// DO NOT assign to a global variable here to prevent premature instantiation.
// $link = get_db_connection();
?>
