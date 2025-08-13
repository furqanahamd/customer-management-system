<?php
session_start();
require 'db_connect.php'; // Yeh tumhara DB connection file

// Check if logged in and admin
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 1) {
    header("Location: login.php"); // Redirect if not admin
    exit;
}

// Get the customer ID from URL
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = intval($_GET['id']);
    
    // First, fetch the customer to get profile picture (if you want to delete the file)
    $stmt = $conn->prepare("SELECT profile_picture FROM customers WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows === 1) {
        $customer = $result->fetch_assoc();
        
        // Delete the profile picture file if exists and not default
        if (!empty($customer['profile_picture']) && $customer['profile_picture'] !== 'default.jpeg') {
            $filePath = 'uploads/' . $customer['profile_picture'];
            if (file_exists($filePath)) {
                unlink($filePath); // Delete the file
            }
        }
        
        // Now, permanently delete the record
        $deleteStmt = $conn->prepare("DELETE FROM customers WHERE id = ?");
        $deleteStmt->bind_param("i", $id);
        
        if ($deleteStmt->execute()) {
            // Success: Redirect back to manage page with success message (you can use session or GET param)
            $_SESSION['success'] = "Customer permanently deleted successfully!";
            header("Location: manage-customers.php"); // Change to your actual manage page name
            exit;
        } else {
            // Error
            $_SESSION['error'] = "Error deleting customer: " . $deleteStmt->error;
            header("Location: manage-customers.php");
            exit;
        }
    } else {
        // Customer not found
        $_SESSION['error'] = "Customer not found!";
        header("Location: manage-customers.php");
        exit;
    }
} else {
    // Invalid ID
    $_SESSION['error'] = "Invalid customer ID!";
    header("Location: manage-customers.php");
    exit;
}
?>
