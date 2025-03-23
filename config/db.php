<?php


$servername = "localhost"; // Change this if using a different host
$username = "root"; // Change if your MySQL username is different
$password = ""; // Change if your MySQL password is set
$database = "saddb"; // Make sure this matches the actual database name

// Create connection
$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
?>
