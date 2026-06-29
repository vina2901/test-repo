<?php
$host = "localhost";
$username = "root";
$password = "";
$dbname = "daz_inventory";

// Create connection
$conn = new mysqli($host, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Database Connection Failed: " . $conn->connect_error);
}
// Connection successful
?>