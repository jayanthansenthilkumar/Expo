<?php
// Docker-specific database configuration
// Use this file when running in Docker containers

$servername = getenv('DB_HOST') ?: 'spark-db';
$username = getenv('DB_USER') ?: 'spark_user';
$password = getenv('DB_PASSWORD') ?: 'spark_password';
$dbname = getenv('DB_NAME') ?: 'spark';

// Create connection
$conn = mysqli_connect($servername, $username, $password, $dbname);

// Check connection
if (!$conn) {
    die("Connection failed: " . mysqli_connect_error());
}

// Set charset to UTF-8
$conn->set_charset("utf8mb4");
?>
