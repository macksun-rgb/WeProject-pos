<?php
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'katunyu_coffee';

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    header('Content-Type: application/json; charset=utf-8');
    die(json_encode(['status' => 'error', 'message' => 'DB Error: ' . $conn->connect_error]));
}
$conn->set_charset("utf8mb4");
?>