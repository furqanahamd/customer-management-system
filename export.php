<?php
require 'db_connect.php';

// Fetch all customers ordered by latest
$result = $conn->query("SELECT * FROM customers ORDER BY id DESC");

// Set CSV headers so browser knows to download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=customers_export_' . date("Y-m-d") . '.csv');

// Open output stream for writing CSV
$output = fopen('php://output', 'w');

// If you want specific columns/order, adjust here:
$columns = [
    'ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Date of Birth', 'City', 'State', 'Zip', 'Profile Picture', 'Created At'
];
fputcsv($output, $columns);

// Output each row from the database
while ($row = $result->fetch_assoc()) {
    fputcsv($output, [
        $row['id'],
        $row['firstname'],
        $row['lastname'],
        $row['email'],
        $row['phone'],
        $row['dob'],
        $row['city'],
        $row['state'],
        $row['zip'],
        $row['profile_picture'],
        isset($row['created_at']) ? $row['created_at'] : ''
    ]);
}
fclose($output);
exit;
?>
