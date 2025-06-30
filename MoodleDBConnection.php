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
                    PDO::ATTR_TIMEOUT            => 5, // 5 second connection timeout
                ];

                error_log("Attempting to connect to: " . $dsn);
                $this->connection = new PDO($dsn, $this->username, $this->password, $options);
                error_log("Successfully connected to database");
                
            } catch (PDOException $e) {
                $errorMsg = sprintf(
                    "Database connection failed: %s (Error Code: %s)",
                    $e->getMessage(),
                    $e->getCode()
                );
                error_log($errorMsg);
                
                // More specific error messages based on error code
                switch ($e->getCode()) {
                    case 2002:
                        $errorMsg = "Could not connect to the database server. Please check if the database server is running.";
                        break;
                    case 1044:
                    case 1045:
                        $errorMsg = "Access denied for user '{$this->username}'. Please check your database credentials.";
                        break;
                    case 1049:
                        $errorMsg = "Database '{$this->dbname}' does not exist. Please check the database name.";
                        break;
                    case 2006:
                        $errorMsg = "Database server has gone away. Please try again later.";
                        break;
                }
                
                throw new PDOException($errorMsg, (int)$e->getCode());
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
