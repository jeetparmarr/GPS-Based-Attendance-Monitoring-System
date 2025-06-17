<?php
session_start();
include "config.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Set error logging
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/error.log');

// PHPMailer dependencies (ensure these paths are correct)
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

// Initialize variables
$error = null;
$success = null;
$debug_mode = false; // Define debug mode variable with default value of false

// Check session variables
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type']) || !isset($_SESSION['otp'])) {
    header("Location: login.php");
    exit;
}

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

$email = $_SESSION['email'];
$user_type = $_SESSION['user_type'];
$stored_otp = $_SESSION['otp'];
$table = ($user_type == 'admin') ? 'admin' : 'employees';

// Handle OTP resend
if (isset($_GET['resend']) && $_GET['resend'] == '1') {
    try {
        $new_otp = rand(100000, 999999);
        $stmt = $conn->prepare("UPDATE $table SET otp = ? WHERE email = ?");
        if (!$stmt) {
            $error_msg = "Prepare failed for UPDATE (resend): " . $conn->error;
            error_log($error_msg);
            throw new Exception("Database error: " . $conn->error);
        }
        $stmt->bind_param("ss", $new_otp, $email);
        if (!$stmt->execute()) {
            $error_msg = "Execute failed for UPDATE (resend): " . $stmt->error;
            error_log($error_msg);
            throw new Exception("Failed to update OTP: " . $stmt->error);
        }

        $_SESSION['otp'] = $new_otp;

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
        $mail->Subject = 'New OTP Verification Code';
        $mail->Body = "<h2>Your new OTP is: <strong>$new_otp</strong></h2><p>Valid for 30 minutes.</p>";

        if (!$mail->send()) {
            $error_msg = "Failed to send new OTP: " . $mail->ErrorInfo;
            error_log($error_msg);
            throw new Exception($error_msg);
        }

        $success = "A new OTP has been sent to your email.";
    } catch (Exception $e) {
        error_log("OTP resend error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}

// Handle OTP verification
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    try {
        $entered_otp = trim($_POST['otp'] ?? '');
        if (empty($entered_otp)) {
            throw new Exception("OTP is required.");
        }
        if ($entered_otp == $stored_otp) {
            $stmt = $conn->prepare("UPDATE $table SET otp_verified = 1, otp = NULL WHERE email = ?");
            if (!$stmt) {
                $error_msg = "Prepare failed for UPDATE (verify): " . $conn->error;
                error_log($error_msg);
                throw new Exception("Database error: " . $conn->error);
            }
            $stmt->bind_param("s", $email);
            if (!$stmt->execute()) {
                $error_msg = "Execute failed for UPDATE (verify): " . $stmt->error;
                error_log($error_msg);
                throw new Exception("Failed to update verification status: " . $stmt->error);
            }

            $success = "OTP verified successfully! Redirecting to login...";
            unset($_SESSION['otp']);
            header("refresh:2;url=login.php");
        } else {
            $error = "Incorrect OTP. Please try again.";
        }
    } catch (Exception $e) {
        error_log("OTP verification error: " . $e->getMessage());
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Verify OTP</title>
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
            height: 100vh;
            margin: 0;
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
            background: linear-gradient(to bottom right, #00c6ff, #0072ff);
            color: white;
            padding: 40px;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .left-section h2 { font-size: 2.5em; margin-bottom: 20px; }
        .left-section p { margin-bottom: 30px; text-align: center; }
        .right-section { width: 60%; padding: 40px; }
        .right-section h2 { text-align: left; margin-bottom: 30px; font-size: 2em; color: #333; }
        .form-group { margin-bottom: 20px; }
        .right-section label { display: block; margin-bottom: 8px; color: #555; font-weight: 500; }
        .right-section input[type="text"] {
            width: 100%;
            padding: 13px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        .right-section input:focus { border-color: #0072ff; outline: none; }
        .right-section button[type="submit"] {
            background-color: #0072ff;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            width: 100%;
            font-size: 17px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s;
        }
        .right-section button:hover { background-color: #0056b3; }
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
        .otp-input { letter-spacing: 8px; font-size: 24px; text-align: center; }
        .resend-link {
            display: block;
            text-align: center;
            margin-top: 20px;
            color: #0072ff;
            text-decoration: none;
        }
        .resend-link:hover { text-decoration: underline; }
        
        /* Responsive styles */
        @media (max-width: 768px) {
            .container {
                flex-direction: column;
                width: 95%;
                max-width: 500px;
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
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="left-section">
            <h2>Verify Your Account</h2>
            <p>Enter the OTP sent to <?php echo htmlspecialchars($email); ?>.</p>
        </div>
        <div class="right-section">
            <h2>Enter OTP</h2>
            <?php if ($error): ?>
                <div class="error-message"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <?php if ($success): ?>
                <div class="success-message"><?php echo htmlspecialchars($success); ?></div>
            <?php endif; ?>
            <form action="" method="post">
                <div class="form-group">
                    <label for="otp">One-Time Password:</label>
                    <input type="text" name="otp" id="otp" class="otp-input" maxlength="6" placeholder="Enter 6-digit OTP" required>
                </div>
                <button type="submit">Verify OTP</button>
            </form>
            <a href="verify_otp.php?resend=1" class="resend-link">Resend OTP</a>
        </div>
    </div>
</body>
</html>