<?php
session_start();
require 'db_connect.php';

$_SESSION['loggedin'] = true;
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); 
    exit; 
}

// Fetch all roles for dropdowns
$rolesResult = $conn->query("SELECT Role_ID, Role_Name FROM auth_user ORDER BY Role_Name ASC");
$roles = [];
while ($role = $rolesResult->fetch_assoc()) {
    $roles[] = $role;
}

// ===== Update Role =====
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_role'])) {
    $role_id = intval($_POST['role_id']);
    $user_id = intval($_POST['user_id']);

    $stmt = $conn->prepare("UPDATE users SET Role_ID = ? WHERE id = ?");
    $stmt->bind_param("ii", $role_id, $user_id);
    if ($stmt->execute()) {
        echo "<script>alert('Role updated successfully!'); window.location='manage-roles.php';</script>";
    } else {
        echo "Error updating role.";
    }
}

// ===== Fetch Users with Role Names and Profile Picture =====
$sql = "SELECT u.id, u.firstname, u.lastname, u.email, u.profile_picture, a.Role_Name 
        FROM users u
        LEFT JOIN auth_user a ON u.Role_ID = a.Role_ID
        ORDER BY u.id DESC";
$result = $conn->query($sql);
$users_exist = $result->num_rows > 0;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Roles & Users</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background: #f4f8fb; }
        img.profile { width: 40px; height: 40px; border-radius: 50%; object-fit: cover; border: 1px solid #ccc; cursor: pointer; }
        .modal-img { max-width: 100%; height: auto; }
    </style>
</head>
<body>
    <div class="container my-5 shadow rounded bg-white">
        <h2 class="text-center mb-4 text-primary">Manage Users & Roles</h2>

        <?php if ($users_exist) { ?>
            <div class="table-responsive">
                <table class="table table-hover table-bordered">
                    <thead class="table-primary">
                        <tr>
                            <th>Picture</th> <!-- Moved before Name -->
                            <th>Name</th>
                            <th>Email</th>
                            <th>Current Role</th>
                            <th>Change Role</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($row = $result->fetch_assoc()) { ?>
                            <tr>
                                <td>
                                    <img src="uploads/<?= !empty($row['profile_picture']) ? htmlspecialchars($row['profile_picture']) : 'default.jpeg' ?>" 
                                         alt="Profile" class="profile" data-bs-toggle="modal" data-bs-target="#imageModal" 
                                         data-src="uploads/<?= !empty($row['profile_picture']) ? htmlspecialchars($row['profile_picture']) : 'default.jpeg' ?>">
                                </td>
                                <td><?= htmlspecialchars($row['firstname'] . " " . $row['lastname']) ?></td>
                                <td><?= htmlspecialchars($row['email']) ?></td>
                                <td><?= htmlspecialchars($row['Role_Name']) ?></td>
                                <td>
                                    <form method="POST" class="d-flex align-items-center">
                                        <input type="hidden" name="user_id" value="<?= $row['id'] ?>">
                                        <select name="role_id" class="form-select me-2">
                                            <?php foreach ($roles as $role) { ?>
                                                <option value="<?= $role['Role_ID'] ?>" <?= ($row['Role_Name'] == $role['Role_Name']) ? 'selected' : '' ?>>
                                                    <?= htmlspecialchars($role['Role_Name']) ?>
                                                </option>
                                            <?php } ?>
                                        </select>
                                        <button type="submit" name="update_role" class="btn btn-success">Update</button>
                                    </form>
                                </td>
                            </tr>
                        <?php } ?>
                    </tbody>
                </table>
            </div>
            <div class="text-center mt-3">
                <a href="add_user.php" class="btn btn-primary">Add New User</a>
            </div>
        <?php } else { ?>
            <p class="text-center fs-5 mb-3">No users found. Add a new user below:</p>
            <div class="text-center">
                <a href="add_user.php" class="btn btn-primary">Add New User</a>
            </div>
        <?php } ?>

        <div class="text-center mt-4">
            <a href="manage-customers.php" class="text-primary">← Back to Manage Users</a>
        </div>
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
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
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
