<?php
/**
 * Database connection class for Moodle MySQL
 */
class MoodleDBConnection {
    private $connection;
    private $host;
    private $dbname;
    private $username;
    private $password;
    private $charset = 'utf8mb4';

    /**
     * Constructor - Initializes database connection parameters
     * 
     * @param string $host     Database host
     * @param string $dbname   Database name
     * @param string $username Database username
     * @param string $password Database password
     */
    public function __construct($host, $dbname, $username, $password) {
        $this->host = $host;
        $this->dbname = $dbname;
        $this->username = $username;
        $this->password = $password;
    }

    /**
     * Establishes a PDO connection to the Moodle database
     * 
     * @return PDO Database connection
     * @throws PDOException If connection fails
     */
    public function connect() {
        if ($this->connection === null) {
            try {
                $dsn = "mysql:host={$this->host};dbname={$this->dbname};charset={$this->charset}";
                $options = [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ];

                $this->connection = new PDO($dsn, $this->username, $this->password, $options);
            } catch (PDOException $e) {
                // Log the error instead of exposing it to the user
                error_log("Connection failed: " . $e->getMessage());
                throw new PDOException("Could not connect to the database. Please try again later.");
            }
        }
        return $this->connection;
    }

    /**
     * Gets the database connection
     * 
     * @return PDO Database connection
     */
    public function getConnection() {
        return $this->connection ?? $this->connect();
    }

    /**
     * Closes the database connection
     */
    public function close() {
        $this->connection = null;
    }

    // Prevent cloning of the instance
    private function __clone() {}
    
    // Prevent unserializing of the instance
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}
