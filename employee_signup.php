<?php
session_start();
include "config.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// PHPMailer dependencies (adjust paths if needed)
$phpmailer_paths = [
    'Exception.php' => __DIR__ . '/Exception.php',
    'PHPMailer.php' => __DIR__ . '/PHPMailer.php',
    'SMTP.php' => __DIR__ . '/SMTP.php'
];
foreach ($phpmailer_paths as $file => $path) {
    if (!file_exists($path)) {
        die("PHPMailer dependency missing: $file");
    }
    require $path;
}

// Initialize error and success variables
$error = null;
$success = null;
$debug_mode = false; // Define debug mode variable with default value

// Ensure tables exist (from config.php)
if (!isset($conn) || $conn->connect_error) {
    $error_msg = "Database connection failed: " . ($conn->connect_error ?? 'Unknown error');
    error_log($error_msg);
    displayDatabaseError("Database connection failed", $conn->connect_error ?? 'Unknown error');
} else {
    try {
        // Call ensureTablesExist without checking its return value
        ensureTablesExist($conn);
    } catch (Exception $e) {
        $error = "Failed to ensure database tables exist. Please try again later.";
        error_log("Table creation failed in ensureTablesExist: " . $e->getMessage());
    }
}

// Check the actual schema of the employees table
$schema_check = $conn->query("SHOW COLUMNS FROM employees LIKE 'user_type'");
if ($schema_check === false || $schema_check->num_rows == 0) {
    $error_msg = "Column 'user_type' not found in 'employees' table. Expected schema might not match.";
    error_log($error_msg);
    $error = "Database schema error: 'user_type' column missing. Contact the administrator.";
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($error)) {
    try {
        // Sanitize and validate input
        $employee_id = mysqli_real_escape_string($conn, trim($_POST['employee_id'] ?? ''));
        $employee_name = mysqli_real_escape_string($conn, trim($_POST['employee_name'] ?? ''));
        $email = mysqli_real_escape_string($conn, trim($_POST['email'] ?? ''));
        $password = trim($_POST['password'] ?? '');
        $confirm_password = trim($_POST['confirm_password'] ?? '');

        if (empty($employee_id)) {
            throw new Exception("Employee ID is required.");
        }
        if (empty($employee_name)) {
            throw new Exception("Full name is required.");
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            throw new Exception("Please enter a valid email address.");
        }
        if (strlen($password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }

        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        $user_type = 'employee';

        // Check for existing employee ID or email
        $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ? OR email = ?");
        if (!$stmt) {
            $error_msg = "Prepare failed for SELECT: " . $conn->error;
            error_log($error_msg);
            throw new Exception("Database error during check: " . $conn->error);
        }
        $stmt->bind_param("ss", $employee_id, $email);
        if (!$stmt->execute()) {
            $error_msg = "Execute failed for SELECT: " . $stmt->error;
            error_log($error_msg);
            throw new Exception("Database execution error: " . $stmt->error);
        }
        $result = $stmt->get_result();

        if ($result->num_rows > 0) {
            $row = $result->fetch_assoc();
            if ($row['employee_id'] === $employee_id) {
                throw new Exception("Employee ID already exists!");
            }
            if ($row['email'] === $email) {
                throw new Exception("Email address already exists!");
            }
        }
        $stmt->close();

        // Generate OTP
        $otp = rand(100000, 999999);

        // Check connection before insert
        if ($conn->ping() === false) {
            $error_msg = "Database connection lost before insert: " . $conn->error;
            error_log($error_msg);
            throw new Exception("Database connection lost: " . $conn->error);
        }

        // Insert new employee
        $stmt = $conn->prepare("INSERT INTO employees (employee_id, employee_name, email, password, otp, user_type, otp_verified, first_login) VALUES (?, ?, ?, ?, ?, ?, 0, 1)");
        if (!$stmt) {
            $error_msg = "Prepare failed for INSERT: " . $conn->error;
            error_log($error_msg);
            throw new Exception("Database error during insert preparation: " . $conn->error);
        }
        $stmt->bind_param("ssssss", $employee_id, $employee_name, $email, $password_hash, $otp, $user_type);
        if (!$stmt->execute()) {
            $error_msg = "Execute failed for INSERT: " . $stmt->error;
            error_log($error_msg);
            throw new Exception("Database error during insert: " . $stmt->error);
        }
        $stmt->close();

        // Set session variables
        $_SESSION['email'] = $email;
        $_SESSION['user_type'] = 'employee';
        $_SESSION['otp'] = $otp;

        // Send OTP email
        $mail = new PHPMailer(true);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Username = 'quantambytes@gmail.com';
        $mail->Password = 'zuec qeof alla tbqt';
        $mail->SMTPSecure = 'tls';
        $mail->Port = 587;
        $mail->setFrom('quantambytes@gmail.com', 'QuantamBytes');
        $mail->addAddress($email);
        $mail->isHTML(true);
        $mail->Subject = 'Employee Registration OTP';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #5b86e5;'>Employee Registration Verification</h2>
                <p>Dear $employee_name,</p>
                <p>Your OTP for registration is: <strong>$otp</strong></p>
                <p>This OTP is valid for 30 minutes.</p>
            </div>
        ";

        if (!$mail->send()) {
            // Rollback on email failure
            $conn->query("DELETE FROM employees WHERE email = '$email'");
            $error_msg = "Failed to send OTP email: " . $mail->ErrorInfo;
            error_log($error_msg);
            throw new Exception($error_msg);
        }

        header("Location: verify_otp.php");
        exit;

    } catch (Exception $e) {
        error_log("Employee signup error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

if (isset($stmt) && $stmt instanceof mysqli_stmt) {
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Employee Registration</title>
    <link rel="stylesheet" href="responsive.css">
    <link rel="stylesheet" href="form-mobile.css">
    <script src="mobile_optimizations.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 15px;
        }
        
        .back-to-options {
            position: absolute;
            top: 20px;
            left: 20px;
            color: #333;
            text-decoration: none;
            display: flex;
            align-items: center;
            gap: 5px;
            font-weight: 500;
            transition: color 0.3s;
            z-index: 10;
        }
        
        .back-to-options:hover {
            color: #36d1dc;
        }
        
        .back-to-options span {
            font-size: 1.5em;
        }
        
        .container {
            display: flex;
            width: 800px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .left-section {
            width: 40%;
            background: linear-gradient(to bottom right, #36d1dc, #5b86e5);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        
        .left-section h2 {
            font-size: 2.5em;
            margin-bottom: 20px;
        }
        
        .left-section p {
            margin-bottom: 30px;
            text-align: center;
        }
        
        .left-section a {
            background-color: white;
            color: #36d1dc;
            padding: 12px 25px;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background-color 0.3s;
        }
        
        .left-section a:hover {
            background-color: #e0e0e0;
        }
        
        .right-section {
            width: 60%;
            padding: 40px;
            overflow-y: auto;
        }
        
        .right-section h2 {
            text-align: center;
            margin-bottom: 30px;
            font-size: 2em;
            color: #333;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .right-section label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }
        
        .right-section input[type="text"],
        .right-section input[type="email"],
        .right-section input[type="password"] {
            width: 100%;
            padding: 13px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        .right-section input:focus {
            border-color: #36d1dc;
            outline: none;
        }
        
        .right-section button[type="submit"] {
            background-color: #36d1dc;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 17px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s;
            margin-top: 10px;
        }
        
        .right-section button[type="submit"]:hover {
            background-color: #5b86e5;
        }
        
        .error-message {
            background-color: #fce4ec;
            border: 1px solid #f8bbd0;
            color: #c62828;
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
        }
        
        .success-message {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
        }
        
        .password-requirements {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                width: 95%;
                max-width: 500px;
                height: auto;
            }
            
            .left-section,
            .right-section {
                width: 100%;
                padding: 30px;
            }
            
            .left-section {
                border-radius: 15px 15px 0 0;
            }
            
            .right-section {
                border-radius: 0 0 15px 15px;
                max-height: 400px;
            }
            
            .back-to-options {
                position: fixed;
                top: 15px;
                left: 15px;
            }
        }
        
        @media (max-width: 576px) {
            body {
                padding: 10px;
            }
            
            .container {
                width: 100%;
            }
            
            .left-section,
            .right-section {
                padding: 20px;
            }
            
            .left-section h2 {
                font-size: 2em;
            }
            
            .right-section h2 {
                font-size: 1.8em;
            }
            
            .right-section label,
            .right-section input,
            .right-section button {
                font-size: 15px;
            }
        }
        
        @media (max-height: 700px) {
            .container {
                margin: 60px 0;
            }
            
            body {
                height: auto;
                min-height: 100vh;
                align-items: flex-start;
                padding-top: 50px;
            }
        }
    </style>
</head>
<body>
    <a href="signup.php" class="back-to-options"><span>←</span> Back to Options</a>
    <div class="container">
        <div class="left-section">
            <h2>Employee Sign Up</h2>
            <p>Join our team and manage your work schedule efficiently!</p>
            <a href="login.php">Login Instead</a>
        </div>
        <div class="right-section">
            <h2>Create Employee Account</h2>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form action="" method="post">
                <div class="form-group">
                    <label for="employee_id">Employee ID:</label>
                    <input type="text" name="employee_id" id="employee_id" placeholder="Enter your employee ID" required value="<?php echo htmlspecialchars($_POST['employee_id'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="employee_name">Full Name:</label>
                    <input type="text" name="employee_name" id="employee_name" placeholder="Enter your full name" required value="<?php echo htmlspecialchars($_POST['employee_name'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" placeholder="Enter your email address" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" placeholder="Create a strong password" required>
                    <p class="password-requirements">Password must be at least 6 characters long</p>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
                </div>
                <button type="submit">Register as Employee</button>
            </form>
        </div>
    </div>
</body>
</html>