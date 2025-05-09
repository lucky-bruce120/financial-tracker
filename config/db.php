<?php
// Database configuration
$host = '127.0.0.1'; // Database host
$user = 'root';      // Database username (default for XAMPP)
$pass = '';          // Database password (default is empty for XAMPP)
$db   = 'financial_tracker'; // Database name

// Create MySQLi connection
$conn = new mysqli($host, $user, $pass, $db);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
