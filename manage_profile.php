<?php
session_start();
require 'db_connect.php';

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit;
}

$user_id = $_SESSION['user_id'];
$error_password = '';
$success_password = '';
$error_picture = '';
$success_picture = '';

// Fetch current profile picture for display
$stmt = $conn->prepare("SELECT profile_picture FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();
$current_picture = !empty($user['profile_picture']) ? $user['profile_picture'] : 'default.jpeg';

// Handle password update
if (isset($_POST['update_password'])) {
    $current_password = trim($_POST['current_password']);
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);

    if ($new_password !== $confirm_password) {
        $error_password = "New password and confirm password do not match.";
    } elseif (strlen($new_password) < 6) {
        $error_password = "New password must be at least 6 characters.";
    } else {
        // Fetch current hashed password
        $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $user = $result->fetch_assoc();

        if (password_verify($current_password, $user['password'])) {
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $update_stmt = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $update_stmt->bind_param("si", $hashed_password, $user_id);
            if ($update_stmt->execute()) {
                $success_password = "Password updated successfully!";
            } else {
                $error_password = "Error updating password.";
            }
        } else {
            $error_password = "Current password is incorrect.";
        }
    }
}

// Handle profile picture update
if (isset($_POST['update_picture'])) {
    if (isset($_FILES['new_picture']) && $_FILES['new_picture']['error'] === 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['new_picture']['name'];
        $file_tmp = $_FILES['new_picture']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_extensions)) {
            $unique_name = uniqid() . '_' . basename($file_name); // Unique filename
            $upload_path = 'uploads/' . $unique_name;
            if (move_uploaded_file($file_tmp, $upload_path)) {
                // Update database
                $update_stmt = $conn->prepare("UPDATE users SET profile_picture = ? WHERE id = ?");
                $update_stmt->bind_param("si", $unique_name, $user_id);
                if ($update_stmt->execute()) {
                    $success_picture = "Profile picture updated successfully!";
                    $current_picture = $unique_name; // Update current for display
                } else {
                    $error_picture = "Error updating profile picture in database.";
                }
            } else {
                $error_picture = "Error uploading file.";
            }
        } else {
            $error_picture = "Invalid file type! Only JPG, JPEG, PNG, GIF allowed.";
        }
    } else {
        $error_picture = "No file selected or upload error.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Profile</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        img.profile { width: 100px; height: 100px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc; cursor: pointer; }
        h3 { font-size: 1.25rem; } /* Made h3 smaller compared to h2 */
        .modal-img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <div class="container my-5">
        <h2>Manage Profile</h2>

        <!-- Update Profile Picture Section (First) -->
        <h3 class="mt-5">Update Profile Picture</h3>
        <?php if ($error_picture): ?>
            <div class="alert alert-danger"><?php echo $error_picture; ?></div>
        <?php endif; ?>
        <?php if ($success_picture): ?>
            <div class="alert alert-success"><?php echo $success_picture; ?></div>
        <?php endif; ?>
        <p>Current Profile Picture:</p>
        <img src="uploads/<?php echo htmlspecialchars($current_picture); ?>" alt="Current Profile" class="profile mb-3" data-bs-toggle="modal" data-bs-target="#imageModal" data-src="uploads/<?php echo htmlspecialchars($current_picture); ?>">
        <form method="POST" enctype="multipart/form-data">
            <input type="hidden" name="update_picture" value="1">
            <div class="mb-3">
                <label for="new_picture" class="form-label">New Profile Picture</label>
                <input type="file" class="form-control" id="new_picture" name="new_picture" accept="image/*" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Picture</button>
        </form>

        <!-- Update Password Section (Second) -->
        <h3 class="mt-5">Update Password</h3>
        <?php if ($error_password): ?>
            <div class="alert alert-danger"><?php echo $error_password; ?></div>
        <?php endif; ?>
        <?php if ($success_password): ?>
            <div class="alert alert-success"><?php echo $success_password; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="update_password" value="1">
            <div class="mb-3">
                <label for="current_password" class="form-label">Current Password</label>
                <input type="password" class="form-control" id="current_password" name="current_password" required>
            </div>
            <div class="mb-3">
                <label for="new_password" class="form-label">New Password</label>
                <input type="password" class="form-control" id="new_password" name="new_password" required>
            </div>
            <div class="mb-3">
                <label for="confirm_password" class="form-label">Confirm New Password</label>
                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
            </div>
            <button type="submit" class="btn btn-primary">Update Password</button>
        </form>

        <a href="manage-customers.php" class="btn btn-secondary mt-3">Back to Manage Users</a>
    </div>

    <!-- Modal for large image view -->
    <div class="modal fade" id="imageModal" tabindex="-1" aria-labelledby="imageModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="imageModalLabel">Profile Picture</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body text-center">
                    <img src="" alt="Large Profile" class="modal-img" id="modalImage">
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        const imageModal = document.getElementById('imageModal');
        imageModal.addEventListener('show.bs.modal', function (event) {
            const button = event.relatedTarget;
            const src = button.getAttribute('data-src');
            const modalImg = document.getElementById('modalImage');
            modalImg.src = src;
        });
    </script>
</body>
</html>
