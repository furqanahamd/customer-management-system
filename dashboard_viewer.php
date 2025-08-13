<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'viewer') {
    header("Location: login.php");
    exit;
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Viewer Dashboard</title>
    <style>
        body { font-family: Arial; background: #fff; padding: 50px; }
        .box { background: #007bff; color: white; padding: 20px; border-radius: 10px; width: 400px; margin: auto; text-align: center; }
        a { color: white; text-decoration: underline; display: block; margin-top: 20px; }
    </style>
</head>
<body>
    <div class="box">
        <h2>Welcome Viewer, <?= $_SESSION['name'] ?>!</h2>
        <p>You can view records only.</p>
        <a href="logout.php">Logout</a>
    </div>
</body>
</html>
<?php
session_start();
require 'db_connect.php';

// Redirect if not logged in
if (!isset($_SESSION['role'])) {
    header("Location: login.php");
    exit;
}

$role = $_SESSION['role'];
$name = $_SESSION['name'];

// Fetch all customers
$stmt = $conn->prepare("SELECT * FROM customers");
$stmt->execute();
$customers = $stmt->get_result();
?>

<!DOCTYPE html>
<html>
<head>
    <title>Customer Management</title>
    <style>
        body { font-family: Arial; padding: 40px; background: #f0f0f0; }
        h2 { text-align: center; color: #333; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; background: white; }
        th, td { padding: 12px; border: 1px solid #ccc; text-align: center; }
        th { background-color: #007bff; color: white; }
        a.btn { padding: 6px 12px; background: #28a745; color: white; text-decoration: none; border-radius: 4px; }
        a.delete { background: #dc3545; }
        .top-bar { display: flex; justify-content: space-between; margin-bottom: 20px; }
        .welcome { font-weight: bold; }
    </style>
</head>
<body>

<div class="top-bar">
    <div class="welcome">Welcome, <?= htmlspecialchars($name) ?> (<?= $role ?>)</div>
    <div><a href="logout.php" class="btn">Logout</a></div>
</div>

<h2>Customer List</h2>

<?php if ($role === 'admin' || $role === 'editor'): ?>
    <div style="margin-bottom: 10px;">
        <a href="add_customer.php" class="btn">+ Add Customer</a>
    </div>
<?php endif; ?>

<table>
    <tr>
        <th>ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Address</th>
        <?php if ($role !== 'viewer'): ?>
            <th>Actions</th>
        <?php endif; ?>
    </tr>

    <?php while ($row = $customers->fetch_assoc()): ?>
        <tr>
            <td><?= $row['id'] ?></td>
            <td><?= htmlspecialchars($row['name']) ?></td>
            <td><?= htmlspecialchars($row['email']) ?></td>
            <td><?= htmlspecialchars($row['phone']) ?></td>
            <td><?= htmlspecialchars($row['address']) ?></td>
            <?php if ($role !== 'viewer'): ?>
                <td>
                    <a href="edit_customer.php?id=<?= $row['id'] ?>" class="btn">Edit</a>
                    <?php if ($role === 'admin'): ?>
                        <a href="delete_customer.php?id=<?= $row['id'] ?>" class="btn delete" onclick="return confirm('Delete this customer?')">Delete</a>
                    <?php endif; ?>
                </td>
            <?php endif; ?>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
