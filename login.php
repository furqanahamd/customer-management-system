<?php
session_start();
include "db_connect.php";

$message = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];
    error_log("[DEBUG] POST received: email=$email"); // Step 1
    error_log("[DEBUG] POST received: password=*** (hidden for security)"); // Step 1 (password log mat kar real mein)

    // Use prepared statement to prevent SQL injection
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1");
    if (!$stmt) {
        error_log('[ERROR] Prepare failed: ' . $conn->error);
        echo "Database query error. See log.";
        exit;
    }
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    error_log("[DEBUG] Query run for $email; num_rows=" . $result->num_rows); // Step 2

    if ($result->num_rows === 1) {
        $user = $result->fetch_assoc();
        error_log("[DEBUG] User found: " . print_r($user, true)); // Step 3

        // Ab hashed password verify kar (plain password ko hashed se compare)
        if (password_verify($password, $user['password'])) {
            error_log("[DEBUG] Password verified for user id=" . $user['id']); // Step 4

            $_SESSION['user_id'] = $user['id'];
            $_SESSION['role'] = $user['Role_ID'];
            $_SESSION['name'] = $user['full_name'];

            // Debug which role matched
            error_log("[DEBUG] User role: " . $user['Role_ID']);

            // Redirect all users to manage-customers.php regardless of role
            error_log("[DEBUG] Redirect: manage-customers.php");
            header("Location: manage-customers.php");
            exit;
        } else {
            error_log("[DEBUG] Invalid password for $email"); // Step 5
            $message = "❌ Invalid password!";
        }
    } else {
        error_log("[DEBUG] No user found or inactive for $email"); // Step 6
        $message = "❌ Email not found or inactive!";
    }
}
?>

<!doctype html>
<html lang="en">
<head>
    <title>Login - Customer Management</title>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <!-- Fonts and Icons -->
    <link href="https://fonts.googleapis.com/css?family=Lato:300,400,700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">

    <!-- Bootstrap & Custom CSS -->
    <link rel="stylesheet" href="css/style.css">
</head>
<body class="img js-fullheight" style="background-image: url(images/bg.jpg);">
<section class="ftco-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-6 text-center mb-5">
                <h2 class="heading-section">Customer Management</h2>
            </div>
        </div>
        <div class="row justify-content-center">
            <div class="col-md-6 col-lg-4">
                <div class="login-wrap p-0">

                    <!-- Show error message -->
                    <?php if (!empty($message)): ?>
                        <div class="alert alert-danger text-center"><?php echo $message; ?></div>
                    <?php endif; ?>

                    <form action="" method="POST" class="signin-form">
                        <div class="form-group">
                            <input type="email" name="email" class="form-control" placeholder="Email" required>
                        </div>

                        <div class="form-group">
                            <input id="password-field" type="password" name="password" class="form-control" placeholder="Password" required>
                            <span toggle="#password-field" class="fa fa-fw fa-eye field-icon toggle-password"></span>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="form-control btn btn-primary submit px-3">Sign In</button>
                        </div>

                        <div class="form-group d-md-flex">
                            <div class="w-50">
                                <label class="checkbox-wrap checkbox-primary">Remember Me
                                    <input type="checkbox" name="remember" checked>
                                    <span class="checkmark"></span>
                                </label>
                            </div>
                            <div class="w-50 text-md-right">
                                <a href="#" style="color: #fff">Forgot Password</a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Scripts -->
<script src="js/jquery.min.js"></script>
<script src="js/popper.js"></script>
<script src="js/bootstrap.min.js"></script>
<script src="js/main.js"></script>
</body>
</html>
