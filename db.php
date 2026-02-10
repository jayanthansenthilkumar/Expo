<?php
// Database configuration - Works both locally and in Docker
$servername = getenv('DB_HOST') ?: 'localhost';
$username = getenv('DB_USER') ?: 'root';
$password = getenv('DB_PASSWORD') ?: '';
$dbname = getenv('DB_NAME') ?: 'spark';

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error() . 
        " (Host: $servername, User: $username, DB: $dbname)");
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
?>
