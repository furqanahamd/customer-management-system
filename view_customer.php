<?php
session_start();
require 'db_connect.php';

// ID check
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: manage-customers.php");
    exit;
}

$id = intval($_GET['id']);

// Fetch customer data
$stmt = $conn->prepare("SELECT * FROM customers WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$customer = $result->fetch_assoc();

if (!$customer) {
    echo "Customer not found.";
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>View Customer Details</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0"> <!-- For responsiveness -->
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f8fb;
            color: #333;
        }
        .detail-label { font-weight: bold; color: #007bff; }
        .detail-value { margin-left: 10px; }
        img.profile {
            display: block;
            margin: 20px auto;
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 2px solid #007bff;
            cursor: pointer; /* Make it clickable */
        }
        .modal-img {
            max-width: 100%;
            height: auto;
        }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="card shadow rounded">
            <div class="card-body">
                <h2 class="card-title text-center mb-4 text-primary">Customer Details</h2>

                <div class="row justify-content-center">
                    <div class="col-12 col-md-4 text-center mb-3">
                        <?php if (!empty($customer['profile_picture'])): ?>
                            <img src="uploads/<?= htmlspecialchars($customer['profile_picture']) ?>" alt="Profile Picture" class="profile img-fluid" data-bs-toggle="modal" data-bs-target="#imageModal">
                        <?php else: ?>
                            <img src="uploads/default.jpeg" alt="Default Profile" class="profile img-fluid" data-bs-toggle="modal" data-bs-target="#imageModal">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="detail-row mb-2">
                            <span class="detail-label">Full Name:</span>
                            <span class="detail-value"><?= htmlspecialchars($customer['firstname'] . ' ' . $customer['lastname']) ?></span>
                        </div>

                        <div class="detail-row mb-2">
                            <span class="detail-label">Email:</span>
                            <span class="detail-value"><?= htmlspecialchars($customer['email']) ?></span>
                        </div>

                        <div class="detail-row mb-2">
                            <span class="detail-label">Phone:</span>
                            <span class="detail-value"><?= htmlspecialchars($customer['phone']) ?></span>
                        </div>

                        <div class="detail-row mb-2">
                            <span class="detail-label">Date of Birth:</span>
                            <span class="detail-value"><?= htmlspecialchars($customer['dob']) ?></span>
                        </div>

                        <div class="detail-row mb-2">
                            <span class="detail-label">City:</span>
                            <span class="detail-value"><?= htmlspecialchars($customer['city']) ?></span>
                        </div>

                        <div class="detail-row mb-2">
                            <span class="detail-label">State:</span>
                            <span class="detail-value"><?= htmlspecialchars($customer['state']) ?></span>
                        </div>

                        <div class="detail-row mb-2">
                            <span class="detail-label">pincode:</span>
                            <span class="detail-value"><?= htmlspecialchars($customer['pincode']) ?></span>
                        </div>

                        <div class="detail-row mb-2">
                            <span class="detail-label">Created At:</span>
                            <span class="detail-value"><?= date('D, d/M/Y – h:i A', strtotime($customer['created_at'])) ?></span>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-4">
                    <a href="manage-customers.php" class="btn btn-primary">← Back to Manage Users</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap Modal for full image view -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Full Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <?php if (!empty($customer['profile_picture'])): ?>
                        <img src="uploads/<?= htmlspecialchars($customer['profile_picture']) ?>" alt="Full Profile Picture" class="modal-img">
                    <?php else: ?>
                        <img src="uploads/default.jpeg" alt="Default Full Profile" class="modal-img">
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS for modal and responsiveness -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
