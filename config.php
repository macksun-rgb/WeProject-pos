<?php
/**
 * config.php
 * Database connection settings for XAMPP local development.
 * Edit DB_* below to match your setup (defaults are XAMPP's out-of-the-box values).
 */

define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'katunyu_coffee');

/**
 * Returns a mysqli connection. Called once per request from index.php / login.php.
 */
function getDBConnection() {
    static $conn = null;

    if ($conn === null) {
        $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME);

        if ($conn->connect_error) {
            die('ເຊື່ອມຕໍ່ຖານຂໍ້ມູນລົ້ມເຫຼວ: ' . $conn->connect_error);
        }

        $conn->set_charset('utf8mb4');
    }

    return $conn;
}
