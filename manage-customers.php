<?php
ob_start(); // Output buffering start to allow headers anytime
session_start();
require 'db_connect.php';

// No-cache headers to prevent back button after logout
header("Cache-Control: no-cache, no-store, must-revalidate");
header("Pragma: no-cache");
header("Expires: 0");

// Fetch logged-in user's name and profile picture from DB
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $stmt = $conn->prepare("SELECT firstname, lastname, profile_picture FROM users WHERE id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        $fullname = htmlspecialchars($user['firstname']) . ' ' . htmlspecialchars($user['lastname']);
        $user_profile_pic = !empty($user['profile_picture']) ? htmlspecialchars($user['profile_picture']) : "default.jpeg";
    } else {
        $fullname = 'User'; // Default if not found
        $user_profile_pic = "default.jpeg";
    }
    $stmt->close();
} else {
    // Not logged in, redirect to login
    header("Location: login.php");
    exit;
}

// Assume role from session (1=Admin, 2=Editor, other=Viewer)
$role = isset($_SESSION['role']) ? $_SESSION['role'] : 0;

// Search handling
$search = isset($_GET['search']) ? trim($_GET['search']) : '';
$searchQuery = "";
$params = [];
$paramTypes = "";

// Agar search diya gaya hai to query me filter lagayenge
if (!empty($search)) {
    $searchQuery = " WHERE firstname LIKE ? OR lastname LIKE ? OR email LIKE ? ";
    $searchTerm = "%" . $search . "%";
    $params = [$searchTerm, $searchTerm, $searchTerm];
    $paramTypes = "sss";
}

// Fetch all users (latest first) with updated_at and active, ordered by active DESC then id DESC
$orderBy = " ORDER BY active DESC, id DESC";
if (!empty($searchQuery)) {
    $stmt = $conn->prepare("SELECT * FROM customers $searchQuery $orderBy");
    $stmt->bind_param($paramTypes, ...$params);
    $stmt->execute();
    $users = $stmt->get_result();
} else {
    $users = $conn->query("SELECT * FROM customers $orderBy");
}

// New user highlight (now handled in loop)
$new_id = isset($_GET['new_id']) ? intval($_GET['new_id']) : null;
ob_end_flush(); // Send output
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Customers</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome for icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            background-color: #f4f8fb;
            color: #333;
        }
        .highlight { background-color: #e8f7e4; animation: fadeHighlight 3s ease-out forwards; }
        @keyframes fadeHighlight {
            0% { background-color: #c8f5b8; }
            100% { background-color: transparent; }
        }
        img.profile {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            object-fit: cover;
            border: 1px solid #ccc;
        }
        .action-icon {
            font-size: 18px; /* Same size for edit and delete */
            vertical-align: middle;
            margin-right: 5px; /* Spacing */
        }
        .name-link { text-decoration: none; color: #007bff; }
        .name-link:hover { text-decoration: underline; }
        .tick { color: green; font-weight: bold; }
        .cross { color: red; font-weight: bold; }
        .email { font-size: 0.9em; color: #666; display: block; }
        .faded-row td:not(:last-child) { opacity: 0.5; /* Fade all columns except action (last one) */ }
        .faded-row .restore-btn { opacity: 1 !important; /* Ensure restore button is normal */ }
        .greeting { font-size: 1.5em; color: #007bff; text-align: center; margin-bottom: 20px; font-weight: bold; }
        .user-dropdown { cursor: pointer; } /* For better UX */
    </style>
</head>
<body>
    <div class="container my-5 shadow rounded bg-white">
        <!-- Header row with title on left and dropdown on right -->
        <div class="d-flex justify-content-between align-items-start mb-3">
            <div>
                <h2 class="mb-1 text-primary">Manage Customers</h2>
                <p class="description text-muted mb-0">In this section, you can view all listed users along with their details and status.</p>
            </div>
            <!-- User profile dropdown -->
            <div class="d-flex align-items-center">
                <span class="me-2" style="font-size: 1.2em; color: #007bff;">Hi, <?= $fullname ?>!</span>
                <div class="dropdown">
                    <img src="uploads/<?= $user_profile_pic ?>" alt="Profile" class="profile user-dropdown" data-bs-toggle="dropdown" aria-expanded="false">
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="manage_profile.php">Manage Profile</a></li>
                        <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                    </ul>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end flex-wrap gap-2 mb-4">
            <?php if ($role === 1): // Admin only ?>
                <a href="add_customer.php" class="btn btn-primary">+ Add Customer</a>
                <a href="manage-roles.php" class="btn btn-primary">⚙ Manage Roles</a>
            <?php endif; ?>
            <?php if ($role === 1 || $role === 2 || $role > 2): // All roles have Export ?>
                <a href="export.php" class="btn btn-primary">Export here</a>
            <?php endif; ?>
        </div>

        <form method="GET" action="" class="mb-4">
            <div class="input-group">
                <input type="text" name="search" class="form-control" placeholder="Enter name or email" value="<?php echo htmlspecialchars($search); ?>">
                <button type="submit" class="btn btn-primary">Search</button>
            </div>
        </form>

        <div class="table-responsive">
            <table class="table table-hover table-bordered">
                <thead class="table-primary">
                    <tr>
                        <th>S.No.</th>
                        <th>Picture</th>
                        <th>Full Name</th>
                        <th>Created</th>
                        <th>Last Updated</th>
                        <?php if ($role === 1): // Active column only for Admin ?>
                            <th>Active</th>
                        <?php endif; ?>
                        <?php if ($role === 1 || $role === 2): // Action column for Admin and Editor ?>
                            <th>Action</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $serial = 1;

                    // Display all users in sorted order
                    while ($row = $users->fetch_assoc()) {
                        $imageName = !empty($row['profile_picture']) ? htmlspecialchars($row['profile_picture']) : "default.jpeg";
                        $profilePic = "uploads/{$imageName}";
                        $rowClass = ($row['active'] ?? 1) ? '' : 'faded-row';
                        if ($new_id && $row['id'] == $new_id) {
                            $rowClass .= ' highlight'; // Add highlight if it's the new user
                        }
                        echo "<tr class='$rowClass'>
                            <td>{$serial}</td>
                            <td><img src='{$profilePic}' alt='Profile' class='profile'></td>
                            <td><a href='view_customer.php?id={$row['id']}' class='name-link'><strong>Name:</strong> " . strtoupper(htmlspecialchars($row['firstname'] . ' ' . $row['lastname'])) . "</a><br>
                                <span class='email'>Email: " . htmlspecialchars($row['email']) . "</span></td>
                            <td>" . date('D, d/M/Y – h:i A', strtotime($row['created_at'])) . "</td>
                            <td>" . (isset($row['updated_at']) ? date('D, d/M/Y – h:i A', strtotime($row['updated_at'])) : 'N/A') . "</td>";
                        if ($role === 1) {
                            echo "<td>" . (($row['active'] ?? 1) ? "<span class='tick'>✓</span>" : "<span class='cross'>✗</span>") . "</td>";
                        }
                        if ($role === 1 || $role === 2) {
                            echo "<td class='align-middle'>";
                            if (($row['active'] ?? 1) && $role === 1) {
                                echo "<a href='edit_customer.php?id={$row['id']}' class='btn btn-sm btn-primary me-1'>Edit</a>
                                      <a href='delete_customer.php?id={$row['id']}' class='btn btn-sm btn-warning' onclick=\"return confirm('Are you sure you want to disable this customer?')\">Disable</a>";
                            } elseif (!($row['active'] ?? 1) && $role === 1) {
                                echo "<a href='restore_customer.php?id={$row['id']}' class='btn btn-sm btn-success restore-btn me-1' onclick=\"return confirm('Restore this user?')\">Restore</a>
                                      <a href='permanent_delete_customer.php?id={$row['id']}' class='btn btn-sm btn-danger' onclick=\"return confirm('Are you sure you want to permanently delete this customer?')\">Delete</a>";
                            } elseif ($role === 2) {
                                echo "<a href='edit_customer.php?id={$row['id']}' class='btn btn-sm btn-primary'>Edit</a>";
                            }
                            echo "</td>";
                        }
                        echo "</tr>";
                        $serial++;
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Bootstrap JS (for any interactive components if needed) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
        // JS to prevent back button
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1); // Push forward to prevent back
        };
    </script>
</body>
</html>
