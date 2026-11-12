<?php
if(!defined('DB_SERVER')){
    require_once(__DIR__ . "/../initialize.php");
}

// Disable mysqli exception mode in PHP 8.1+ so failed queries return false instead of throwing fatal uncaught exceptions
if (function_exists('mysqli_report')) {
    mysqli_report(MYSQLI_REPORT_OFF);
}

class DBConnection{
    private $host;
    private $username;
    private $password;
    private $database;
    private $port;
    private $ssl;
    
    public $conn;
    public $connect_error = null;
    
    public function __construct(){
        if (function_exists('mysqli_report')) {
            mysqli_report(MYSQLI_REPORT_OFF);
        }

        // PHP 8.x Safe Property Initializations inside constructor
        $this->host     = defined('DB_SERVER') ? DB_SERVER : 'localhost';
        $this->username = defined('DB_USERNAME') ? DB_USERNAME : 'root';
        $this->password = defined('DB_PASSWORD') ? DB_PASSWORD : '';
        $this->database = defined('DB_NAME') ? DB_NAME : 'cdsms_db';
        $this->port     = defined('DB_PORT') ? (int)DB_PORT : 3306;
        $this->ssl      = defined('DB_SSL') ? (bool)DB_SSL : false;

        if (!isset($this->conn) || !$this->conn) {
            $this->conn = mysqli_init();
            if (!$this->conn) {
                $this->connect_error = "mysqli_init initialization failed";
                error_log($this->connect_error);
                return;
            }

            // Set connection timeout
            $this->conn->options(MYSQLI_OPT_CONNECT_TIMEOUT, 15);

            $flags = 0;
            // Aiven Cloud enforces SSL (require_secure_transport=ON)
            if ($this->ssl || $this->port !== 3306 || strpos($this->host, 'aivencloud.com') !== false) {
                $this->conn->options(MYSQLI_OPT_SSL_VERIFY_SERVER_CERT, false);
                $this->conn->ssl_set(NULL, NULL, NULL, NULL, NULL);
                $flags |= MYSQLI_CLIENT_SSL;
            }

            try {
                $connected = @$this->conn->real_connect(
                    $this->host,
                    $this->username,
                    $this->password,
                    $this->database,
                    $this->port,
                    NULL,
                    $flags
                );

                if (!$connected) {
                    // Fallback attempt without SSL just in case local localhost
                    if ($flags & MYSQLI_CLIENT_SSL) {
                        $this->conn = mysqli_init();
                        $connected = @$this->conn->real_connect(
                            $this->host,
                            $this->username,
                            $this->password,
                            $this->database,
                            $this->port
                        );
                    }
                }

                if (!$connected) {
                    $this->connect_error = mysqli_connect_error();
                    error_log("Aiven/MySQL Connection error: " . $this->connect_error);
                } else {
                    $this->conn->set_charset("utf8mb4");
                }
            } catch (Throwable $e) {
                $this->connect_error = $e->getMessage();
                error_log("Aiven/MySQL Exception during real_connect: " . $this->connect_error);
            }
        }
    }

    public function isConnected(){
        return ($this->conn instanceof mysqli && empty($this->connect_error) && !@$this->conn->connect_errno);
    }

    public function __destruct(){
        if ($this->conn instanceof mysqli && !@$this->conn->connect_errno) {
            @$this->conn->close();
        }
    }
}
?>
