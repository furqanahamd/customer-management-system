<?php
$host = "localhost";
$user = "root";
$password = ""; // WAMP ka default password khali hota hai
$database = "customer_management";

$conn = new mysqli($host, $user, $password, $database);

if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
// echo "Database connected successfully!";
?>
