<?php
session_start();
include "db_connect.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST['email']);

    // Check if email exists in users table
    $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND is_active = 1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows === 1) {
        // Email match hua, redirect to reset password page
        header("Location: reset_password.php?email=" . urlencode($email));
        exit;
    } else {
        $message = "No active user found with this email!";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <title>Forgot Password</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="stylesheet" href="css/style.css"> 
</head>
<body class="img js-fullheight" style="background-image: url(images/bg.jpg);">
<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center mb-5">
                <h2 class="heading-section">Forgot Password</h2>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-wrap p-0">

                    <?php if (!empty($message)): ?>
                        <div class="alert alert-danger text-center"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" class="signin-form">
                        <div class="form-group">
                            <input type="email" name="email" class="form-control" placeholder="Enter your email" required>
                        </div>
                        <div class="form-group">
                            <button type="submit" class="form-control btn btn-primary submit px-3">Check Email</button>
                        </div>
                    </form>
                    <a href="login.php" style="color: #fff; text-align: center; display: block;">Back to Login</a>
                </div>
            </div>
        </div>
    </div>
</section>
</body>
</html>
