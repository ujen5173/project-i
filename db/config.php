<?php
// Database configuration
$db_host = 'localhost';     // Usually localhost
$db_user = 'root'; // Your MySQL username
$db_pass = ''; // Your MySQL password
$db_name = 'stayhaven';   // Your database name

// Create connection
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);


// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>