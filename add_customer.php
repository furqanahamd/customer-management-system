<?php
ob_start(); // Output buffering start to allow headers anytime
session_start();
require 'db_connect.php';

// Remove forced login for security - rely on actual login system
// $_SESSION['loggedin'] = true; // Commented out
if (!isset($_SESSION['loggedin']) || $_SESSION['loggedin'] !== true) {
    header("Location: login.php"); 
    exit; 
}

// CSRF Token Generation
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

// Check DB connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$errors = []; // Array to store validation errors

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF Validation
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION['csrf_token']) {
        $errors[] = "Invalid form submission (CSRF token mismatch).";
    } else {
        $firstname = trim($_POST['firstname']);
        $lastname  = trim($_POST['lastname']);
        $email     = trim($_POST['email']);
        $phone     = trim($_POST['phone']);
        $city      = trim($_POST['city']);
        $state     = trim($_POST['state']);
        $pincode   = trim($_POST['pincode']); // Changed from zip to pincode
        $dob       = trim($_POST['dob']);

        // Empty checks for select fields
        if (empty($city)) {
            $errors[] = "City is required.";
        }
        if (empty($state)) {
            $errors[] = "State is required.";
        }

        // Firstname Validation Checks (Professional Level)
        if (empty($firstname)) {
            $errors[] = "Firstname is required.";
        } else {
            // Length: min 2, max 50
            if (strlen($firstname) < 2 || strlen($firstname) > 50) {
                $errors[] = "Firstname must be between 2 and 50 characters.";
            }
            // Allowed characters: alphabets, spaces, hyphens, apostrophes
            if (!preg_match("/^[A-Za-z\s\-']+$/", $firstname)) {
                $errors[] = "Invalid firstname: Only alphabets, spaces, hyphens, and apostrophes allowed.";
            }
            // No leading/trailing spaces (already trimmed, but extra check)
            if ($firstname !== trim($firstname)) {
                $errors[] = "Firstname should not have leading/trailing spaces.";
            }
            // Simple profanity check (case-insensitive)
            $bad_words = ['badword1', 'offensive']; // Expand this list
            if (in_array(strtolower($firstname), array_map('strtolower', $bad_words))) {
                $errors[] = "Invalid firstname: Contains prohibited words.";
            }
        }

        // Lastname Validation Checks (Similar to Firstname)
        if (empty($lastname)) {
            $errors[] = "Lastname is required.";
        } else {
            if (strlen($lastname) < 2 || strlen($lastname) > 50) {
                $errors[] = "Lastname must be between 2 and 50 characters.";
            }
            if (!preg_match("/^[A-Za-z\s\-']+$/", $lastname)) {
                $errors[] = "Invalid lastname: Only alphabets, spaces, hyphens, and apostrophes allowed.";
            }
            if ($lastname !== trim($lastname)) {
                $errors[] = "Lastname should not have leading/trailing spaces.";
            }
            // Profanity check (case-insensitive)
            if (in_array(strtolower($lastname), array_map('strtolower', $bad_words))) {
                $errors[] = "Invalid lastname: Contains prohibited words.";
            }
        }

        // Validate email: proper format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = "Invalid email: Please enter a valid email address.";
        }
        // Email uniqueness check
        if (!empty($email)) {
            $stmt = $conn->prepare("SELECT id FROM customers WHERE email = ?");
            $stmt->bind_param("s", $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "This email is already registered.";
            }
            $stmt->close();
        }

        // Phone Number Validation Checks (Professional Level)
        $full_phone = '+91' . $phone; // Hardcode +91 as per original code
        if (empty($phone)) {
            $errors[] = "Phone number is required.";
        } else {
            // Format, Length, and Numeric Check
            if (!preg_match('/^(\+91)?[6-9]\d{9}$/', $full_phone) || strlen($phone) !== 10) {
                $errors[] = "Invalid phone number: Must be exactly 10 digits starting with 6-9 (optional +91).";
            }
            if (!is_numeric($phone)) {
                $errors[] = "Phone number must contain only digits.";
            }
            // Uniqueness Check
            $stmt = $conn->prepare("SELECT id FROM customers WHERE phone = ?");
            $stmt->bind_param("s", $full_phone);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $errors[] = "This phone number is already registered.";
            }
            $stmt->close();
            // Security: Sanitize
            $phone = filter_var($phone, FILTER_SANITIZE_NUMBER_INT);
        }

        // DOB Validation Checks (Professional Level)
        if (empty($dob)) {
            $errors[] = "Date of Birth is required.";
        } else {
            // Format Validation
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $dob)) {
                $errors[] = "Invalid DOB: Required format is YYYY-MM-DD (e.g., 1990-01-01).";
            }
            // Valid Date Check
            $date_parts = explode('-', $dob);
            if (count($date_parts) !== 3 || !checkdate($date_parts[1], $date_parts[2], $date_parts[0])) {
                $errors[] = "Invalid DOB: Date does not exist (e.g., invalid day/month).";
            } else {
                // Past Date Only
                if (strtotime($dob) > time()) {
                    $errors[] = "Invalid DOB: Cannot be in the future.";
                }
                // Age Range (18-120)
                $age = date_diff(date_create($dob), date_create('today'))->y;
                if ($age < 18) {
                    $errors[] = "You must be at least 18 years old.";
                } elseif ($age > 120) {
                    $errors[] = "Invalid DOB: Age exceeds reasonable limit.";
                }
            }
        }

        // Validate PIN Code: 6 digits, starts with 1-9, optional space, and basic city-based check
        if (empty($pincode)) {
            $errors[] = "PIN Code is required.";
        } elseif (!preg_match('/^[1-9]\d{2}\s?\d{3}$/', $pincode)) {
            $errors[] = "Invalid PIN Code: Must be exactly 6 digits (optional space after first 3) starting with 1-9.";
        } else {
            // Remove space for integer conversion
            $pincode_clean = str_replace(' ', '', $pincode);
            $pin_int = intval($pincode_clean);
            $valid_pin = false;
            switch ($city) {
                case 'Delhi': // Example: 110000-110099
                    if ($pin_int >= 110000 && $pin_int <= 110099) $valid_pin = true;
                    break;
                case 'Mumbai': // Example: 400000-400099
                    if ($pin_int >= 400000 && $pin_int <= 400099) $valid_pin = true;
                    break;
                case 'Bengaluru': // Added example
                    if ($pin_int >= 560000 && $pin_int <= 562162) $valid_pin = true;
                    break;
                // Add more cities here
                default:
                    $valid_pin = true; // If city not in list, allow (expand this or use API)
            }
            if (!$valid_pin) {
                $errors[] = "Invalid PIN Code for selected city: Does not match the city's range.";
            }
        }

        // Profile picture handling with security
        $profilePicName = null;
        if (!empty($_FILES['profile_picture']['name'])) {
            $targetDir = "uploads/";
            if (!is_dir($targetDir)) {
                if (!mkdir($targetDir, 0777, true)) {
                    $errors[] = "Failed to create upload directory.";
                }
            }
            $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
            $file_type = mime_content_type($_FILES['profile_picture']['tmp_name']);
            if (!in_array($file_type, $allowed_types) || $_FILES['profile_picture']['size'] > 2000000) {  // 2MB limit
                $errors[] = "Invalid profile picture: Only JPG/PNG/GIF up to 2MB allowed.";
            } else {
                $profilePicName = time() . "_" . basename($_FILES["profile_picture"]["name"]);
                $targetFilePath = $targetDir . $profilePicName;
                if (!move_uploaded_file($_FILES["profile_picture"]["tmp_name"], $targetFilePath)) {
                    $errors[] = "File upload failed.";
                }
            }
        }

        // If no errors, insert into DB
        if (empty($errors)) {
            $stmt = $conn->prepare("INSERT INTO customers (firstname, lastname, email, phone, dob, city, state, pincode, profile_picture) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->bind_param("sssssssis", $firstname, $lastname, $email, $full_phone, $dob, $city, $state, $pincode, $profilePicName);
            if ($stmt->execute() && $stmt->affected_rows > 0) {
                $new_id = $conn->insert_id;
                // Regenerate CSRF token after successful submission
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
                header("Location: manage-customers.php?new_id=" . $new_id . "&success=Customer added successfully");
                exit;
            } else {
                $errors[] = "Database error: " . $stmt->error;
            }
            $stmt->close();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Add Customer</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { background: #f0f0f0; }
        .form-container { max-width: 800px; margin: auto; background: white; padding: 30px; border-radius: 8px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
        h2 { text-align: center; color: #007bff; margin-bottom: 25px; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #007bff; text-decoration: none; }
        .back-link:hover { text-decoration: underline; }
        .error-message { color: red; margin-bottom: 15px; }
    </style>
</head>
<body>
    <div class="container my-5">
        <div class="form-container">
            <h2>Add New Customer</h2>

            <!-- Show errors if any -->
            <?php if (!empty($errors)): ?>
                <div class="alert alert-danger">
                    <ul>
                        <?php foreach ($errors as $error): ?>
                            <li><?= htmlspecialchars($error) ?></li> <!-- Sanitized for display -->
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" id="addCustomerForm">
                <input type="hidden" name="csrf_token" value="<?php echo $_SESSION['csrf_token']; ?>">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label for="firstname" class="form-label">First name</label>
                        <input type="text" class="form-control" id="firstname" name="firstname" placeholder="First name" required>
                    </div>
                    <div class="col-md-4">
                        <label for="lastname" class="form-label">Last name</label>
                        <input type="text" class="form-control" id="lastname" name="lastname" placeholder="Last name" required>
                    </div>
                    <div class="col-md-4">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email" placeholder="Email" required>
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label for="phone" class="form-label">Phone (India)</label>
                        <div class="input-group">
                            <span class="input-group-text">+91</span>
                            <input type="tel" class="form-control" id="phone" name="phone" placeholder="Phone number" pattern="[6-9]\d{9}" maxlength="10" required>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="dob" class="form-label">Date of Birth</label>
                        <input type="date" class="form-control" id="dob" name="dob" required max="<?= date('Y-m-d'); ?>"> <!-- Client-side past date -->
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-6">
                        <label for="profile_picture" class="form-label">Profile Picture</label>
                        <input type="file" class="form-control" id="profile_picture" name="profile_picture" accept="image/*">
                    </div>
                </div>

                <div class="row g-3 mt-3">
                    <div class="col-md-4">
                        <label for="state" class="form-label">State/UT</label>
                        <select class="form-select" id="state" name="state" required>
                            <option value="">Select State/UT</option>
                            <option value="Andaman and Nicobar Islands">Andaman and Nicobar Islands</option>
                            <option value="Andhra Pradesh">Andhra Pradesh</option>
                            <option value="Arunachal Pradesh">Arunachal Pradesh</option>
                            <option value="Assam">Assam</option>
                            <option value="Bihar">Bihar</option>
                            <option value="Chandigarh">Chandigarh</option>
                            <option value="Chhattisgarh">Chhattisgarh</option>
                            <option value="Dadra and Nagar Haveli and Daman and Diu">Dadra and Nagar Haveli and Daman and Diu</option>
                            <option value="Delhi">Delhi</option>
                            <option value="Goa">Goa</option>
                            <option value="Gujarat">Gujarat</option>
                            <option value="Haryana">Haryana</option>
                            <option value="Himachal Pradesh">Himachal Pradesh</option>
                            <option value="Jammu and Kashmir">Jammu and Kashmir</option>
                            <option value="Jharkhand">Jharkhand</option>
                            <option value="Karnataka">Karnataka</option>
                            <option value="Kerala">Kerala</option>
                            <option value="Ladakh">Ladakh</option>
                            <option value="Lakshadweep">Lakshadweep</option>
                            <option value="Madhya Pradesh">Madhya Pradesh</option>
                            <option value="Maharashtra">Maharashtra</option>
                            <option value="Manipur">Manipur</option>
                            <option value="Meghalaya">Meghalaya</option>
                            <option value="Mizoram">Mizoram</option>
                            <option value="Nagaland">Nagaland</option>
                            <option value="Odisha">Odisha</option>
                            <option value="Puducherry">Puducherry</option>
                            <option value="Punjab">Punjab</option>
                            <option value="Rajasthan">Rajasthan</option>
                            <option value="Sikkim">Sikkim</option>
                            <option value="Tamil Nadu">Tamil Nadu</option>
                            <option value="Telangana">Telangana</option>
                            <option value="Tripura">Tripura</option>
                            <option value="Uttar Pradesh">Uttar Pradesh</option>
                            <option value="Uttarakhand">Uttarakhand</option>
                            <option value="West Bengal">West Bengal</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="city" class="form-label">City</label>
                        <select class="form-select" id="city" name="city" required>
                            <option value="">Select City</option>
                            <!-- Cities populated via JS -->
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="pincode" class="form-label">Pin code</label>
                        <input type="text" class="form-control" id="pincode" name="pincode" placeholder="Pin code" required> <!-- Changed to type="text" for space support -->
                    </div>
                </div>

                <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" id="agreeTerms" required>
                    <label class="form-check-label" for="agreeTerms">
                        I agree to the <a href="terms_and_conditions.php" target="_blank">Terms and Conditions</a>
                    </label>
                </div>

                <button class="btn btn-success w-100 mt-3" type="submit">Submit</button>
            </form>

            <a href="manage-customers.php" class="back-link">← Back to Manage Customers</a>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script>
    document.addEventListener("DOMContentLoaded", function() {
        const checkbox = document.getElementById("agreeTerms"); // Updated ID
        const submitBtn = document.querySelector("button[type='submit']");
        const form = document.getElementById("addCustomerForm");
        const phoneInput = document.getElementById("phone");
        const stateSelect = document.getElementById("state");
        const citySelect = document.getElementById("city");
        const firstnameInput = document.getElementById("firstname");
        const lastnameInput = document.getElementById("lastname");
        const dobInput = document.getElementById("dob");
        const pincodeInput = document.getElementById("pincode");

        submitBtn.disabled = true;
        checkbox.addEventListener("change", function() {
            submitBtn.disabled = !this.checked;
        });

        // Client-side firstname check
        firstnameInput.addEventListener("input", function() {
            const name = this.value.trim();
            const nameRegex = /^[A-Za-z\s\-']{2,50}$/;
            if (!nameRegex.test(name)) {
                this.classList.add("is-invalid");
                this.setAttribute('aria-invalid', 'true');
            } else {
                this.classList.remove("is-invalid");
                this.removeAttribute('aria-invalid');
            }
        });

        // Client-side lastname check
        lastnameInput.addEventListener("input", function() {
            const name = this.value.trim();
            const nameRegex = /^[A-Za-z\s\-']{2,50}$/;
            if (!nameRegex.test(name)) {
                this.classList.add("is-invalid");
                this.setAttribute('aria-invalid', 'true');
            } else {
                this.classList.remove("is-invalid");
                this.removeAttribute('aria-invalid');
            }
        });

        // Client-side phone format check
        phoneInput.addEventListener("input", function() {
            const phone = this.value;
            const phoneRegex = /^[6-9]\d{0,9}$/;
            if (!phoneRegex.test(phone) || phone.length > 10) {
                this.classList.add("is-invalid");
                this.setAttribute('aria-invalid', 'true');
            } else {
                this.classList.remove("is-invalid");
                this.removeAttribute('aria-invalid');
            }
        });

        // Client-side DOB check (past date, age 18-120)
        dobInput.addEventListener("change", function() {
            const dob = new Date(this.value);
            const today = new Date();
            const age = today.getFullYear() - dob.getFullYear();
            if (dob > today || age < 18 || age > 120) {
                this.classList.add("is-invalid");
                this.setAttribute('aria-invalid', 'true');
            } else {
                this.classList.remove("is-invalid");
                this.removeAttribute('aria-invalid');
            }
        });

        // Client-side PIN Code check (6 digits, optional space, starts with 1-9)
        pincodeInput.addEventListener("input", function() {
            const pin = this.value.trim();
            const pinRegex = /^[1-9]\d{2}\s?\d{3}$/;
            if (!pinRegex.test(pin)) {
                this.classList.add("is-invalid");
                this.setAttribute('aria-invalid', 'true');
            } else {
                this.classList.remove("is-invalid");
                this.removeAttribute('aria-invalid');
            }
        });

        // Cities by state/UT object (comprehensive list from real sources) - Reverted to hardcoded as requested
        const citiesByState = {
            "Andaman and Nicobar Islands": ["Port Blair", "Car Nicobar", "Mayabunder", "Diglipur", "Rangat"],
            "Andhra Pradesh": ["Visakhapatnam", "Vijayawada", "Guntur", "Nellore", "Kurnool", "Rajahmundry", "Kakinada", "Tirupati", "Anantapur", "Kadapa", "Vizianagaram", "Eluru", "Ongole", "Nandyal", "Machilipatnam", "Adoni", "Tenali", "Proddatur", "Chittoor", "Hindupur", "Bhimavaram", "Madanapalle", "Guntakal", "Srikakulam", "Dharmavaram", "Gudivada", "Narasaraopet", "Tadipatri", "Tadepalligudem", "Amaravati", "Chilakaluripet"],
            "Arunachal Pradesh": ["Itanagar", "Naharlagun", "Tawang", "Pasighat", "Roing", "Tezu", "Ziro", "Bomdila", "Namsai", "Aalo", "Changlang", "Khonsa"],
            "Assam": ["Guwahati", "Silchar", "Dibrugarh", "Jorhat", "Nagaon", "Tinsukia", "Tezpur", "Bongaigaon", "Karimganj", "Diphu", "Dhubri", "North Lakhimpur", "Golaghat", "Barpeta", "Sivasagar", "Kokrajhar", "Hailakandi", "Mangaldoi", "Biswanath Chariali", "Hojai"],
            "Bihar": ["Patna", "Gaya", "Bhagalpur", "Muzaffarpur", "Purnia", "Darbhanga", "Bihar Sharif", "Arrah", "Begusarai", "Katihar", "Munger", "Chhapra", "Bettiah", "Motihari", "Siwan", "Sasaram", "Hajipur", "Dehri", "Sitamarhi", "Madhubani", "Kishanganj", "Saharsa", "Jamalpur", "Buxar", "Jehanabad", "Aurangabad", "Lakhisarai", "Nawada", "Jamui", "Supaul", "Banka"],
            "Chandigarh": ["Chandigarh"],
            "Chhattisgarh": ["Raipur", "Bhilai", "Bilaspur", "Korba", "Raigarh", "Jagdalpur", "Ambikapur", "Dhamtari", "Durg", "Mahasamund", "Rajnandgaon", "Kanker", "Narayanpur", "Bijapur", "Sukma", "Kondagaon"],
            "Dadra and Nagar Haveli and Daman and Diu": ["Daman", "Diu", "Silvassa", "Dadra"],
            "Delhi": ["New Delhi", "Delhi", "North Delhi", "South Delhi", "East Delhi", "West Delhi", "Central Delhi"],
            "Goa": ["Panaji", "Margao", "Vasco da Gama", "Mapusa", "Ponda", "Bicholim", "Curchorem", "Valpoi", "Sanguem", "Canacona"],
            "Gujarat": ["Ahmedabad", "Surat", "Vadodara", "Rajkot", "Bhavnagar", "Jamnagar", "Junagadh", "Gandhinagar", "Anand", "Nadiad", "Morbi", "Mehsana", "Surendranagar", "Veraval", "Navsari", "Bharuch", "Vapi", "Ankleshwar", "Godhra", "Palanpur", "Valsad", "Patan", "Deesa", "Amreli", "Savarkundla", "Dahod", "Botad"],
            "Haryana": ["Faridabad", "Gurgaon", "Hisar", "Rohtak", "Panipat", "Karnal", "Sonipat", "Yamunanagar", "Panchkula", "Bhiwani", "Sirsa", "Bahadurgarh", "Jind", "Kurukshetra", "Kaithal", "Rewari", "Palwal", "Hansi", "Narnaul", "Fatehabad", "Gohana", "Tohana"],
            "Himachal Pradesh": ["Shimla", "Mandi", "Solan", "Dharamshala", "Nahan", "Baddi", "Paonta Sahib", "Sundarnagar", "Chamba", "Una", "Hamirpur", "Bilaspur", "Kullu", "Rampur", "Reckong Peo"],
            "Jammu and Kashmir": ["Srinagar", "Jammu", "Anantnag", "Baramulla", "Sopore", "Udhampur", "Kathua", "Ganderbal", "Rajouri", "Poonch", "Kupwara", "Pulwama", "Shopian", "Kulgam", "Bandipora", "Samba"],
            "Jharkhand": ["Ranchi", "Jamshedpur", "Dhanbad", "Bokaro Steel City", "Deoghar", "Hazaribagh", "Giridih", "Ramgarh", "Medininagar", "Chirkunda", "Phusro", "Jhumri Tilaiya", "Saunda", "Sahibganj", "Dumka", "Chaibasa"],
            "Karnataka": ["Bengaluru", "Hubli-Dharwad", "Mysore", "Mangalore", "Belgaum", "Gulbarga", "Davanagere", "Bellary", "Bijapur", "Shimoga", "Tumkur", "Raichur", "Bidar", "Hospet", "Hassan", "Gadag-Betageri", "Udupi", "Robertson Pet", "Bhadravati", "Chitradurga", "Kolar", "Mandya", "Chikmagalur", "Gangawati", "Bagalkot", "Ranibennur"],
            "Kerala": ["Thiruvananthapuram", "Kochi", "Kozhikode", "Kollam", "Thrissur", "Alappuzha", "Palakkad", "Malappuram", "Kannur", "Kottayam", "Kasaragod", "Pathanamthitta", "Varkala", "Neyyattinkara", "Kayamkulam", "Nedumangad", "Tirur", "Koyilandy", "Ponnani", "Taliparamba"],
            "Ladakh": ["Leh", "Kargil"],
            "Lakshadweep": ["Kavaratti", "Minicoy", "Amini", "Andrott", "Kalpeni", "Agatti"],
            "Madhya Pradesh": ["Indore", "Bhopal", "Jabalpur", "Gwalior", "Ujjain", "Sagar", "Ratlam", "Satna", "Murwara", "Dewas", "Rewa", "Singrauli", "Burhanpur", "Khandwa", "Morena", "Bhind", "Guna", "Shivpuri", "Damoh", "Chhatarpur", "Mandsaur", "Neemuch", "Vidisha", "Sehore", "Betul", "Seoni"],
            "Maharashtra": ["Mumbai", "Pune", "Nagpur", "Thane", "Pimpri-Chinchwad", "Nashik", "Kalyan-Dombivli", "Vasai-Virar", "Aurangabad", "Navi Mumbai", "Solapur", "Mira-Bhayandar", "Bhiwandi", "Amravati", "Nanded", "Kolhapur", "Ulhasnagar", "Sangli", "Malegaon", "Jalgaon", "Akola", "Latur", "Dhule", "Ahmednagar", "Chandrapur", "Parbhani", "Ichalkaranji", "Jalna", "Ambarnath", "Bhusawal", "Panvel", "Badlapur", "Beed", "Gondia", "Satara"],
            "Manipur": ["Imphal", "Thoubal", "Lilong", "Mayang Imphal", "Kakching", "Yairipok", "Wangjing", "Nambol", "Moirang", "Bishnupur"],
            "Meghalaya": ["Shillong", "Tura", "Nongthymmai", "Nongstoin", "Jowai", "Mawlai", "Nongpoh", "Resubelpara", "Williamnagar", "Baghmara"],
            "Mizoram": ["Aizawl", "Lunglei", "Saiha", "Champhai", "Serchhip", "Kolasib", "Lawngtlai", "Vairengte", "Bairabi", "Mamit"],
            "Nagaland": ["Dimapur", "Kohima", "Tuensang", "Wokha", "Mokokchung", "Zunheboto", "Phek", "Mon", "Chumukedima", "Peren"],
            "Odisha": ["Bhubaneswar", "Cuttack", "Rourkela", "Brahmapur", "Sambalpur", "Puri", "Balasore", "Bhadrak", "Baripada", "Balangir", "Jharsuguda", "Bargarh", "Rayagada", "Jeypore", "Bhawanipatna", "Paradip", "Jajpur", "Sunabeda", "Dhenkanal", "Keonjhar"],
            "Puducherry": ["Puducherry", "Karaikal", "Yanam", "Mahe", "Ozhukarai"],
            "Punjab": ["Ludhiana", "Amritsar", "Jalandhar", "Patiala", "Bathinda", "Mohali", "Pathankot", "Hoshiarpur", "Batala", "Moga", "Malerkotla", "Khanna", "Phagwara", "Muktsar", "Barnala", "Rajpura", "Firozpur", "Kapurthala", "Faridkot", "Sunam"],
            "Rajasthan": ["Jaipur", "Jodhpur", "Kota", "Bikaner", "Ajmer", "Udaipur", "Bhilwara", "Alwar", "Bharatpur", "Sikar", "Pali", "Sri Ganganagar", "Tonk", "Kishangarh", "Beawar", "Hanumangarh", "Dholpur", "Gangapur City", "Sawai Madhopur", "Churu", "Jhunjhunu", "Barmer", "Nagaur", "Makrana"],
            "Sikkim": ["Gangtok", "Namchi", "Mangan", "Gyalshing", "Jorethang", "Rangpo", "Singtam", "Ravangla"],
            "Tamil Nadu": ["Chennai", "Coimbatore", "Madurai", "Tiruchirappalli", "Salem", "Tirunelveli", "Tiruppur", "Vellore", "Erode", "Thoothukudi", "Nagercoil", "Thanjavur", "Dindigul", "Cuddalore", "Kanchipuram", "Karur", "Hosur", "Sivakasi", "Rajapalayam", "Pudukkottai", "Krishnagiri", "Neyveli", "Nagapattinam", "Viluppuram", "Tiruchengode", "Pollachi", "Namakkal", "Tiruvannamalai", "Dharmapuri", "Udumalaipettai"],
            "Telangana": ["Hyderabad", "Warangal", "Nizamabad", "Karimnagar", "Ramagundam", "Khammam", "Mahbubnagar", "Nalgonda", "Adilabad", "Suryapet", "Siddipet", "Miryalaguda", "Jagtial", "Mancherial", "Wanaparthy", "Bhongir", "Kamareddy", "Jangaon"],
            "Tripura": ["Agartala", "Udaipur", "Dharmanagar", "Pratapgarh", "Kailasahar", "Belonia", "Khowai", "Bishalgarh", "Sonamura", "Teliamura"],
            "Uttar Pradesh": ["Lucknow", "Kanpur", "Ghaziabad", "Agra", "Varanasi", "Meerut", "Allahabad", "Bareilly", "Aligarh", "Moradabad", "Saharanpur", "Gorakhpur", "Noida", "Firozabad", "Loni", "Jhansi", "Muzaffarnagar", "Mathura", "Shahjahanpur", "Rampur", "Mau", "Farrukhabad", "Hapur", "Etawah", "Mirzapur", "Bulandshahr", "Sambhal", "Amroha", "Hardoi", "Fatehpur", "Raebareli", "Orai", "Sitapur", "Bahraich", "Modinagar", "Unnao", "Jaunpur", "Lakhimpur", "Hathras", "Banda", "Pilibhit", "Barabanki", "Khurja", "Gonda", "Mainpuri", "Lalitpur", "Etah", "Deoria", "Badaun", "Ghazipur", "Sultanpur", "Azamgarh", "Bijnor", "Sahaswan", "Basti", "Chandausi", "Akbarpur", "Ballia", "Tanda", "Greater Noida", "Shikohabad", "Shamli", "Kasganj"],
            "Uttarakhand": ["Dehradun", "Haridwar", "Haldwani", "Roorkee", "Rudrapur", "Kashipur", "Rishikesh", "Pithoragarh", "Ramnagar", "Nainital", "Almora", "Kotdwar", "Jaspur", "Mussoorie", "Sitarganj", "Khatima", "Bageshwar", "Srinagar", "Gopeshwar", "Tanakpur"],
            "West Bengal": ["Kolkata", "Howrah", "Asansol", "Siliguri", "Durgapur", "Bardhaman", "Malda", "Baharampur", "Habra", "Kharagpur", "Shantipur", "Dankuni", "Dhulian", "Ranaghat", "Haldia", "Raiganj", "Krishnanagar", "Nabadwip", "Medinipur", "Jalpaiguri", "Balurghat", "Basirhat", "Bankura", "Chakdaha", "Darjeeling", "Purulia", "Jangipur", "Bolpur", "Bangaon", "Cooch Behar"]
        };

        // Populate cities based on selected state - Reverted to hardcoded
        stateSelect.addEventListener("change", function() {
            const selectedState = this.value;
            citySelect.innerHTML = "<option value=''>Select City</option>"; // Reset cities
            if (citiesByState[selectedState]) {
                citiesByState[selectedState].forEach(function(city) {
                    const option = document.createElement("option");
                    option.value = city;
                    option.textContent = city;
                    citySelect.appendChild(option);
                });
            }
        });

        form.addEventListener("submit", function(event) {
            const phoneValue = phoneInput.value.trim();
            if (phoneValue.length !== 10 || !/^\d{10}$/.test(phoneValue)) {
                alert("Phone number must be exactly 10 digits.");
                event.preventDefault();
            }

            const pinValue = pincodeInput.value.trim();
            if (!/^[1-9]\d{2}\s?\d{3}$/.test(pinValue)) {
                alert("PIN Code must be exactly 6 digits (optional space after first 3) starting with 1-9.");
                event.preventDefault();
            }
        });
    });
    </script>
</body>
</html>
