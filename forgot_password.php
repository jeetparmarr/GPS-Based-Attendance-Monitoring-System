<?php
session_start();
include "config.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// PHPMailer dependencies
require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

// Initialize variables
$error = null;
$success = null;
$step = isset($_GET['step']) ? $_GET['step'] : 'email';

// Step 1: Request password reset with email
if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 'email') {
    try {
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
        
        if (empty($email)) {
            throw new Exception("Email is required.");
        }
        
        // Determine which table to use
        $table = ($user_type == 'admin') ? 'admin' : 'employees';
        
        // Check if email exists
        $stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows == 0) {
            throw new Exception("No account found with this email.");
        }
        
        $user = $result->fetch_assoc();
        
        // Generate OTP
        $otp = rand(100000, 999999);
        
        // Update OTP in database
        $stmt = $conn->prepare("UPDATE $table SET otp = ? WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $otp, $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to generate OTP. Please try again.");
        }
        
        // Store data in session
        $_SESSION['reset_email'] = $email;
        $_SESSION['reset_user_type'] = $user_type;
        $_SESSION['reset_otp'] = $otp;
        
        // Send OTP via email
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
        $mail->Subject = 'Password Reset OTP';
        $mail->Body = "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                <h2 style='color: #0072ff;'>Password Reset Verification</h2>
                <p>We received a request to reset your password.</p>
                <p>Your OTP for password reset is: <strong>$otp</strong></p>
                <p>This OTP is valid for 30 minutes.</p>
                <p>If you did not request a password reset, please ignore this email.</p>
            </div>
        ";
        
        if (!$mail->send()) {
            throw new Exception("Failed to send OTP email. Please try again.");
        }
        
        // Redirect to OTP verification step
        header("Location: forgot_password.php?step=verify");
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Step 2: Verify OTP
if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 'verify') {
    try {
        if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_otp'])) {
            header("Location: forgot_password.php");
            exit;
        }
        
        $entered_otp = trim($_POST['otp']);
        $stored_otp = $_SESSION['reset_otp'];
        
        if ($entered_otp != $stored_otp) {
            throw new Exception("Incorrect OTP. Please try again.");
        }
        
        // OTP is correct, move to reset password step
        header("Location: forgot_password.php?step=reset");
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// Step 3: Reset password
if ($_SERVER["REQUEST_METHOD"] == "POST" && $step == 'reset') {
    try {
        if (!isset($_SESSION['reset_email']) || !isset($_SESSION['reset_user_type'])) {
            header("Location: forgot_password.php");
            exit;
        }
        
        $email = $_SESSION['reset_email'];
        $user_type = $_SESSION['reset_user_type'];
        $table = ($user_type == 'admin') ? 'admin' : 'employees';
        
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        if (strlen($new_password) < 6) {
            throw new Exception("Password must be at least 6 characters long.");
        }
        
        if ($new_password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }
        
        // Hash new password
        $password_hash = password_hash($new_password, PASSWORD_BCRYPT);
        
        // Update password and clear OTP
        $stmt = $conn->prepare("UPDATE $table SET password = ?, otp = NULL WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("ss", $password_hash, $email);
        
        if (!$stmt->execute()) {
            throw new Exception("Failed to update password. Please try again.");
        }
        
        // Clear session data
        unset($_SESSION['reset_email']);
        unset($_SESSION['reset_user_type']);
        unset($_SESSION['reset_otp']);
        
        // Set success message
        $_SESSION['verification_success'] = "Password has been reset successfully. You can now login with your new password.";
        
        // Redirect to login page
        header("Location: login.php");
        exit;
        
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Forgot Password</title>
    <link rel="stylesheet" href="responsive.css">
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
            color: #0072ff;
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
        }
        
        .right-section h2 {
            text-align: left;
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
        
        .right-section input[type="email"],
        .right-section input[type="text"],
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
            border-color: #0072ff;
            outline: none;
        }
        
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
        
        .right-section button[type="submit"]:hover {
            background-color: #0056b3;
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
        
        .back-to-login {
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
        }
        
        .back-to-login:hover {
            color: #0072ff;
        }
        
        .back-to-login span {
            font-size: 1.5em;
        }
        
        .password-requirements {
            font-size: 0.8em;
            color: #666;
            margin-top: 5px;
        }
        
        .otp-input {
            letter-spacing: 8px;
            font-size: 24px;
            text-align: center;
        }
        
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
            
            .otp-input {
                letter-spacing: 4px;
                font-size: 20px;
            }
        }
    </style>
</head>
<body>
    <a href="login.php" class="back-to-login"><span>←</span> Back to Login</a>
    <div class="container">
        <div class="left-section">
            <h2>Reset Password</h2>
            <p>Follow the steps to reset your password and regain access to your account.</p>
            <a href="login.php">Return to Login</a>
        </div>
        <div class="right-section">
            <?php if($step == 'email'): ?>
                <h2>Forgot Password</h2>
                <?php if(isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <p>Enter your email address and we'll send you an OTP to reset your password.</p>
                <form action="forgot_password.php?step=email" method="post">
                    <div class="form-group">
                        <label for="email">Email:</label>
                        <input type="email" name="email" id="email" placeholder="Enter your email" required>
                    </div>
                    <div class="form-group">
                        <label>Account Type:</label>
                        <div style="display: flex; gap: 20px; margin-top: 10px;">
                            <div style="display: flex; align-items: center;">
                                <input type="radio" name="user_type" id="employee" value="employee" required checked>
                                <label for="employee" style="margin-left: 8px; margin-bottom: 0;">Employee</label>
                            </div>
                            <div style="display: flex; align-items: center;">
                                <input type="radio" name="user_type" id="admin" value="admin" required>
                                <label for="admin" style="margin-left: 8px; margin-bottom: 0;">Admin</label>
                            </div>
                        </div>
                    </div>
                    <button type="submit">Send Reset OTP</button>
                </form>
            <?php elseif($step == 'verify'): ?>
                <h2>Verify OTP</h2>
                <?php if(isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <p>Enter the OTP sent to your email address.</p>
                <form action="forgot_password.php?step=verify" method="post">
                    <div class="form-group">
                        <label for="otp">One-Time Password:</label>
                        <input type="text" name="otp" id="otp" class="otp-input" maxlength="6" placeholder="Enter 6-digit OTP" required>
                    </div>
                    <button type="submit">Verify OTP</button>
                </form>
            <?php elseif($step == 'reset'): ?>
                <h2>Create New Password</h2>
                <?php if(isset($error)): ?>
                    <div class="error-message"><?php echo $error; ?></div>
                <?php endif; ?>
                <p>Create a new password for your account.</p>
                <form action="forgot_password.php?step=reset" method="post">
                    <div class="form-group">
                        <label for="new_password">New Password:</label>
                        <input type="password" name="new_password" id="new_password" placeholder="Enter new password" required>
                        <p class="password-requirements">Password must be at least 6 characters long</p>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Confirm Password:</label>
                        <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm new password" required>
                    </div>
                    <button type="submit">Reset Password</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</body>
</html> 