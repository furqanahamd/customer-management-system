<?php
session_start();
require 'db_connect.php';

// Check if user is logged in and is Admin (role=1)
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 1) {
    header("Location: login.php"); // Redirect to login if not authorized
    exit;
}

$id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($id > 0) {
    // Restore customer (set active to 1 and update timestamp)
    $stmt = $conn->prepare("UPDATE customers SET active = 1, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("i", $id);

    if ($stmt->execute()) {
        header("Location: manage-customers.php?success=Customer restored successfully");
        exit;
    } else {
        echo "Error during restore: " . $stmt->error;
    }
    $stmt->close();
} else {
    // Invalid ID, redirect with error
    header("Location: manage-customers.php?error=Invalid customer ID");
    exit;
}
?>
