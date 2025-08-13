<?php
session_start();
include "db_connect.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $new_password = trim($_POST['new_password']);
    $confirm_password = trim($_POST['confirm_password']);
    $token = $_POST['token'];
    $email = $_POST['email'];

    if ($new_password !== $confirm_password) {
        $message = "Passwords do not match!";
    } else {
        // Validate token
        $hashed_token = hash('sha256', $token);
        $stmt = $conn->prepare("SELECT * FROM password_resets WHERE email = ? AND token = ? AND created_at > DATE_SUB(NOW(), INTERVAL 1 HOUR)");
        $stmt->bind_param("ss", $email, $hashed_token);
        $stmt->execute();
        $result = $stmt->get_result();

        if ($result->num_rows === 1) {
            // Update password in users table
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE users SET password = ? WHERE email = ?");
            $stmt->bind_param("ss", $hashed_password, $email);
            $stmt->execute();

            // Delete the token
            $stmt = $conn->prepare("DELETE FROM password_resets WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();

            $message = "Password reset successful! <a href='login.php'>Login now</a>";
        } else {
            $message = "Invalid or expired token!";
        }
    }
} else {
    // Get token and email from URL
    if (!isset($_GET['token']) || !isset($_GET['email'])) {
        header("Location: login.php");
        exit;
    }
    $token = $_GET['token'];
    $email = $_GET['email'];
}
?>

<!doctype html>
<html lang="en">
<head>
    <title>Reset Password</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="img js-fullheight" style="background-image: url(images/bg.jpg);">
<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center mb-5">
                <h2 class="heading-section">Reset Password</h2>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-wrap p-0">

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-info text-center"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" class="signin-form">
                        <input type="hidden" name="token" value="<?= htmlspecialchars($token); ?>">
                        <input type="hidden" name="email" value="<?= htmlspecialchars($email); ?>">
                        <div class="form-group">
                            <input type="password" name="new_password" class="form-control" placeholder="New Password" required>
                        </div>
                        <div class="form-group">
                            <input type="password" name="confirm_password" class="form-control" placeholder="Confirm Password" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="form-control btn btn-primary submit px-3">Reset Password</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>
</body>
</html>
