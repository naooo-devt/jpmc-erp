<?php
$db_host = 'localhost';     // Your database host (usually 'localhost')
$db_user = 'root';          // Your database username
$db_pass = '';              // Your database password
$db_name = 'james_polymer_erp'; // The name of the database created with the SQL script

// --- Establish Connection ---
$conn = new mysqli($db_host, $db_user, $db_pass, $db_name);

// --- Check Connection ---
if ($conn->connect_error) {
    // If connection fails, stop the script and display an error.
    die("Connection failed: " . $conn->connect_error);
}

// Set character set to utf8mb4 for full Unicode support
$conn->set_charset("utf8mb4");

?>