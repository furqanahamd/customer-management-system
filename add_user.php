<?php
session_start();
require 'db_connect.php';

$_SESSION['loggedin'] = true;
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php");  
    exit; 
}

// Fetch roles for dropdown
$rolesResult = $conn->query("SELECT Role_ID, Role_Name FROM auth_user ORDER BY Role_Name ASC");
$roles = [];
while ($role = $rolesResult->fetch_assoc()) {
    $roles[] = $role;
}

// Handle form submission
$errors = []; // Array to store validation errors
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_user'])) {
    $firstname = trim($_POST['firstname']);
    $lastname = trim($_POST['lastname']);
    $email = trim($_POST['email']);
    $password = trim($_POST['password']);
    $confirm_password = trim($_POST['confirm_password']);
    $role_id = intval($_POST['role_id']);

    // Firstname Validation Checks
    if (empty($firstname)) {
        $errors[] = "First Name is required.";
    } elseif (strlen($firstname) < 2 || strlen($firstname) > 50) {
        $errors[] = "First Name must be between 2 and 50 characters.";
    } elseif (!preg_match("/^[A-Za-z\s\-']+$/", $firstname)) {
        $errors[] = "Invalid First Name: Only alphabets, spaces, hyphens, and apostrophes allowed.";
    } else {
        // Profanity check (simple example, expand list as needed)
        $bad_words = ['badword', 'offensive']; // Add more prohibited words
        if (in_array(strtolower($firstname), $bad_words)) {
            $errors[] = "Invalid First Name: Contains prohibited words.";
        }
    }

    // Lastname Validation Checks
    if (empty($lastname)) {
        $errors[] = "Last Name is required.";
    } elseif (strlen($lastname) < 2 || strlen($lastname) > 50) {
        $errors[] = "Last Name must be between 2 and 50 characters.";
    } elseif (!preg_match("/^[A-Za-z\s\-']+$/", $lastname)) {
        $errors[] = "Invalid Last Name: Only alphabets, spaces, hyphens, and apostrophes allowed.";
    } else {
        // Profanity check
        if (in_array(strtolower($lastname), $bad_words)) {
            $errors[] = "Invalid Last Name: Contains prohibited words.";
        }
    }

    // Email Validation (assuming basic, add more if needed)
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format.";
    }

    // Password Validation Checks
    if (empty($password)) {
        $errors[] = "Password is required.";
    } elseif (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters long.";
    } elseif (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password) || !preg_match('/[0-9]/', $password) || !preg_match('/[!@#$%^&*(),.?":{}|<>]/', $password)) {
        $errors[] = "Password must include at least one uppercase letter, one lowercase letter, one number, and one special character.";
    } elseif ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    } else {
        // No common patterns (simple check, expand as needed)
        $common_patterns = ['password', '123456', 'qwerty', 'letmein'];
        if (in_array(strtolower($password), $common_patterns)) {
            $errors[] = "Password is too common; choose a stronger one.";
        }
    }

    // Profile picture handling
    $profile_picture = 'default.jpeg'; // Default if no upload
    if (isset($_FILES['profile_picture']) && $_FILES['profile_picture']['error'] === 0) {
        $allowed_extensions = ['jpg', 'jpeg', 'png', 'gif'];
        $file_name = $_FILES['profile_picture']['name'];
        $file_tmp = $_FILES['profile_picture']['tmp_name'];
        $file_ext = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));

        if (in_array($file_ext, $allowed_extensions)) {
            $unique_name = uniqid() . '_' . basename($file_name); // Unique filename to avoid overwrites
            $upload_path = 'uploads/' . $unique_name;
            if (move_uploaded_file($file_tmp, $upload_path)) {
                $profile_picture = $unique_name;
            } else {
                $errors[] = "Error uploading profile picture.";
            }
        } else {
            $errors[] = "Invalid file type! Only JPG, JPEG, PNG, GIF allowed.";
        }
    }

    // If no errors, insert into DB
    if (empty($errors)) {
        $hashed_password = password_hash($password, PASSWORD_DEFAULT); // Hash the password
        $stmt = $conn->prepare("INSERT INTO users (firstname, lastname, email, password, is_active, created_at, Role_ID, profile_picture) VALUES (?, ?, ?, ?, 1, NOW(), ?, ?)");
        $stmt->bind_param("ssssis", $firstname, $lastname, $email, $hashed_password, $role_id, $profile_picture);

        if ($stmt->execute()) {
            echo "<script>alert('User added successfully!'); window.location='manage-roles.php';</script>";
        } else {
            echo "<script>alert('Error adding user!');</script>";
        }
    } else {
        // Show errors in alert for simplicity (you can display in form too)
        $error_msg = implode("\\n", $errors);
        echo "<script>alert('$error_msg');</script>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add New User</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <!-- Font Awesome for eye icon -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <style>
        body { background: #f4f8fb; }
        .form-container { max-width: 450px; margin: auto; background: white; padding: 30px; border-radius: 12px; box-shadow: 0px 4px 20px rgba(0,0,0,0.15); }
        .password-container { position: relative; margin-bottom: 10px; /* Space for strength indicator */ }
        .password-container input { padding-right: 40px; }
        .password-container .toggle-password { position: absolute; right: 10px; top: 50%; transform: translateY(-50%); cursor: pointer; color: #888; }
        #password-strength { margin-top: 5px; /* Positive margin to avoid overlap */ margin-bottom: 15px; font-size: 14px; position: relative; z-index: 10; /* Ensure it's on top */ }
        .weak { color: red; }
        .medium { color: orange; }
        .strong { color: green; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        #profile-preview { max-width: 150px; margin-top: 10px; display: none; border-radius: 50%; } /* Circular preview */
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="form-container">
            <h2 class="text-center mb-4">Add New User</h2>
            <form method="POST" id="addUserForm" enctype="multipart/form-data"> <!-- Added enctype for file upload -->
                <div class="mb-3">
                    <label for="firstname" class="form-label fw-bold">First Name</label>
                    <input type="text" class="form-control" id="firstname" name="firstname" required>
                </div>

                <div class="mb-3">
                    <label for="lastname" class="form-label fw-bold">Last Name</label>
                    <input type="text" class="form-control" id="lastname" name="lastname" required>
                </div>

                <div class="mb-3">
                    <label for="email" class="form-label fw-bold">Email</label>
                    <input type="email" class="form-control" id="email" name="email" required>
                </div>

                <div class="mb-3">
                    <label for="password" class="form-label fw-bold">Password</label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="password" name="password" required>
                        <i class="fas fa-eye toggle-password" id="togglePassword"></i>
                    </div>
                    <div id="password-strength"></div>
                </div>

                <div class="mb-3"> <!-- New Confirm Password Field -->
                    <label for="confirm_password" class="form-label fw-bold">Confirm Password</label>
                    <div class="password-container">
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                        <i class="fas fa-eye toggle-password" id="toggleConfirmPassword"></i>
                    </div>
                </div>

                <div class="mb-3"> <!-- New Profile Picture Field -->
                    <label for="profile_picture" class="form-label fw-bold">Profile Picture</label>
                    <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                    <img id="profile-preview" src="#" alt="Profile Preview"> <!-- Preview image -->
                </div>

                <div class="mb-3">
                    <label for="role_id" class="form-label fw-bold">Role</label>
                    <select class="form-select" id="role_id" name="role_id" required>
                        <?php foreach ($roles as $role) { ?>
                            <option value="<?= $role['Role_ID'] ?>"><?= htmlspecialchars($role['Role_Name']) ?></option>
                        <?php } ?>
                    </select>
                </div>

                <button type="submit" name="add_user" class="btn btn-primary w-100">Add User</button>
            </form>
            <a href="manage-roles.php" class="back-link">← Back to Manage Roles</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>

    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const form = document.getElementById("addUserForm");
        const passwordInput = document.getElementById("password");
        const confirmPasswordInput = document.getElementById("confirm_password");
        const strengthDisplay = document.getElementById("password-strength");
        const togglePassword = document.getElementById("togglePassword");
        const toggleConfirmPassword = document.getElementById("toggleConfirmPassword");
        const profileInput = document.getElementById("profile_picture");
        const profilePreview = document.getElementById("profile-preview");
        const firstnameInput = document.getElementById("firstname");
        const lastnameInput = document.getElementById("lastname");

        togglePassword.addEventListener("click", function() {
            const type = passwordInput.getAttribute("type") === "password" ? "text" : "password";
            passwordInput.setAttribute("type", type);
            this.classList.toggle("fa-eye-slash");
        });

        toggleConfirmPassword.addEventListener("click", function() {
            const type = confirmPasswordInput.getAttribute("type") === "password" ? "text" : "password";
            confirmPasswordInput.setAttribute("type", type);
            this.classList.toggle("fa-eye-slash");
        });

        // Password Strength Check Function
        function checkPasswordStrength(password) {
            const minLength = 8;
            const hasUpperCase = /[A-Z]/.test(password);
            const hasLowerCase = /[a-z]/.test(password);
            const hasNumber = /[0-9]/.test(password);
            const hasSymbol = /[!@#$%^&*(),.?":{}|<>]/.test(password);
            const lengthValid = password.length >= minLength;

            // Common patterns check
            const commonPatterns = ['password', '123456', 'qwerty', 'letmein', 'welcome'];
            const isCommon = commonPatterns.includes(password.toLowerCase());

            let strength = 'Weak';
            if (lengthValid && hasUpperCase && hasLowerCase && hasNumber && hasSymbol && !isCommon) {
                strength = 'Strong';
            } else if (lengthValid && ((hasUpperCase && hasLowerCase && hasNumber) || (hasUpperCase && hasLowerCase && hasSymbol)) && !isCommon) {
                strength = 'Medium';
            }

            return {
                strength: strength,
                lengthValid: lengthValid,
                hasUpperCase: hasUpperCase,
                hasLowerCase: hasLowerCase,
                hasNumber: hasNumber,
                hasSymbol: hasSymbol,
                isCommon: isCommon
            };
        }

        passwordInput.addEventListener("input", function() {
            const password = this.value;
            const result = checkPasswordStrength(password);
            let strengthClass = '';
            if (result.strength === 'Strong') {
                strengthClass = 'strong';
            } else if (result.strength === 'Medium') {
                strengthClass = 'medium';
            } else {
                strengthClass = 'weak';
            }
            strengthDisplay.innerHTML = `Password Strength: <span class="${strengthClass}">${result.strength}</span>`;
        });

        // Profile picture preview
        profileInput.addEventListener("change", function() {
            const file = this.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    profilePreview.src = e.target.result;
                    profilePreview.style.display = 'block';
                };
                reader.readAsDataURL(file);
            } else {
                profilePreview.style.display = 'none';
            }
        });

        form.addEventListener("submit", function(event) {
            const password = passwordInput.value;
            const confirmPassword = confirmPasswordInput.value;
            const result = checkPasswordStrength(password);

            if (password !== confirmPassword) {
                alert("Passwords do not match!");
                event.preventDefault(); // Prevent form submission
                return;
            }

            if (!result.lengthValid || !result.hasUpperCase || !result.hasLowerCase || !result.hasNumber || !result.hasSymbol || result.isCommon) {
                alert("Password must be at least 8 characters long, include one uppercase letter, one lowercase letter, one number, one symbol, and not be a common pattern.");
                event.preventDefault(); // Prevent form submission
            }

            // Client-side firstname and lastname checks
            const firstname = firstnameInput.value.trim();
            const lastname = lastnameInput.value.trim();
            const nameRegex = /^[A-Za-z\s\-']{2,50}$/;
            if (!nameRegex.test(firstname)) {
                alert("Invalid First Name: Must be 2-50 characters with only alphabets, spaces, hyphens, apostrophes.");
                event.preventDefault();
            }
            if (!nameRegex.test(lastname)) {
                alert("Invalid Last Name: Must be 2-50 characters with only alphabets, spaces, hyphens, apostrophes.");
                event.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
