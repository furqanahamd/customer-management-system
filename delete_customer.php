<?php
session_start();
require 'db_connect.php';

$_SESSION['loggedin'] = true;
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");  // Redirect to login
    exit;  // Important, script stop karo
}

// Ab protected content yahan se start
echo "Welcome to protected page!";


$id = intval($_GET['id']);

// Soft delete customer (set active to 0 and update timestamp)
$stmt = $conn->prepare("UPDATE customers SET active = 0, updated_at = NOW() WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    header("Location: manage-customers.php");
    exit;
} else {
    echo "Error during soft delete: " . $stmt->error;
}
?>
