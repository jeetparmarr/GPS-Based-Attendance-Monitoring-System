<?php
session_start();
include "config.php";
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
require 'Exception.php';
require 'PHPMailer.php';
require 'SMTP.php';

// Initialize error variable
$error = null;
$success = null;

// Ensure database connection is valid
if (!isset($conn) || $conn->connect_error) {
    $error = "Database connection failed. Please try again later.";
} else {
    try {
        // Use the ensureTablesExist function to make sure all tables are created
        ensureTablesExist($conn);
    } catch (Exception $e) {
        $error = "Database setup error: " . $e->getMessage();
    }
}

if ($_SERVER["REQUEST_METHOD"] == "POST" && !isset($error)) {
    try {
        $username = mysqli_real_escape_string($conn, $_POST['username']);
        $email = mysqli_real_escape_string($conn, $_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Check if passwords match
        if ($password !== $confirm_password) {
            throw new Exception("Passwords do not match.");
        }
        
        $password = password_hash($password, PASSWORD_BCRYPT);
        $security_code = mysqli_real_escape_string($conn, $_POST['security_code']);
        $user_type = 'admin';
        
        // Get security code from database
        $securityQuery = $conn->query("SELECT password FROM security_password WHERE code_type = 'admin_registration'");
        if ($securityQuery === false) {
            throw new Exception("Error fetching security code: " . $conn->error);
        }
        
        if ($securityQuery->num_rows == 0) {
            // No security code found in database, insert the current one
            $insertCode = "INSERT INTO security_password (code_type, password) VALUES ('admin_registration', '$security_code')";
            if (!$conn->query($insertCode)) {
                throw new Exception("Error setting security code: " . $conn->error);
            }
            // Use the entered code as valid
            $stored_security_code = $security_code;
        } else {
            $securityRow = $securityQuery->fetch_assoc();
            $stored_security_code = $securityRow['password'];
        }
        
        // Verify security code
        if ($security_code != $stored_security_code) {
            $error = "Invalid security code. Admin registration failed.";
        } else {
            // Check if username already exists
            $stmt = $conn->prepare("SELECT * FROM admin WHERE username = ?");
            if (!$stmt) {
                throw new Exception("Database error: " . $conn->error);
            }
            
            $stmt->bind_param("s", $username);
            $stmt->execute();
            $result = $stmt->get_result();
            
            if ($result->num_rows > 0) {
                $error = "Username already exists!";
            } else {
                // Check if email already exists
                $stmt = $conn->prepare("SELECT * FROM admin WHERE email = ?");
                if (!$stmt) {
                    throw new Exception("Database error: " . $conn->error);
                }
                
                $stmt->bind_param("s", $email);
                $stmt->execute();
                $result = $stmt->get_result();
                
                if ($result->num_rows > 0) {
                    $error = "Email already exists!";
                } else {
                    // Generate OTP
                    $otp = rand(100000, 999999);
                    
                    // Insert new admin with OTP
                    $stmt = $conn->prepare("INSERT INTO admin (username, email, password, otp, user_type, otp_verified) VALUES (?, ?, ?, ?, ?, 0)");
                    if (!$stmt) {
                        throw new Exception("Database error: " . $conn->error);
                    }
                    
                    $stmt->bind_param("sssss", $username, $email, $password, $otp, $user_type);
                    
                    if ($stmt->execute()) {
                        // Set email in session for OTP verification
                        $_SESSION['email'] = $email;
                        $_SESSION['user_type'] = 'admin';
                        
                        // Store OTP in session for verification
                        $_SESSION['otp'] = $otp;
                        
                        // Send OTP email
                        try {
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
                            $mail->Subject = 'Admin Registration OTP';
                            $mail->Body = "
                                <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #ddd; border-radius: 10px;'>
                                    <h2 style='color: #ff416c;'>Admin Registration Verification</h2>
                                    <p>Dear Admin User,</p>
                                    <p>Thank you for registering as an administrator. To complete your account setup, please use the following OTP:</p>
                                    <div style='background-color: #f5f5f5; padding: 15px; border-radius: 5px; text-align: center; font-size: 24px; font-weight: bold; margin: 20px 0;'>
                                        $otp
                                    </div>
                                    <p>This OTP is valid for the next 30 minutes. Please do not share this code with anyone.</p>
                                    <p>If you did not request this registration, please ignore this email.</p>
                                    <p>Best regards,<br>Company Name Team</p>
                                </div>
                            ";

                            $mail->send();
                            
                            // Redirect to OTP verification page
                            header("Location: verify_otp.php");
                            exit;
                        } catch (Exception $e) {
                            // Log error but continue
                            error_log("Email sending failed: " . $e->getMessage());
                            
                            // Redirect to OTP verification page
                            header("Location: verify_otp.php");
                            exit;
                        }
                    } else {
                        throw new Exception("Error creating admin account: " . $conn->error);
                    }
                }
            }
        }
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
    <title>Admin Registration</title>
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
            color: #ff416c;
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
            background: linear-gradient(to bottom right, #ff416c, #ff4b2b);
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
            color: #ff416c;
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
            border-color: #ff416c;
            outline: none;
        }

        .right-section button[type="submit"] {
            background-color: #ff416c;
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
            background-color: #ff2b4b;
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
            <h2>Admin Sign Up</h2>
            <p>Create an administrator account to manage the system.</p>
            <a href="login.php">Login Instead</a>
        </div>
        <div class="right-section">
            <h2>Create Admin Account</h2>
            
            <?php if(isset($error)): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <?php if(isset($success)): ?>
                <div class="success-message"><?php echo $success; ?></div>
            <?php endif; ?>
            
            <form action="" method="post">
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" name="username" id="username" placeholder="Enter admin username" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" placeholder="Enter admin email" required>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" name="password" id="password" placeholder="Create a strong password" required>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password:</label>
                    <input type="password" name="confirm_password" id="confirm_password" placeholder="Confirm your password" required>
                </div>
                <div class="form-group">
                    <label for="security_code">Security Code:</label>
                    <input type="password" name="security_code" id="security_code" placeholder="Enter admin security code" required>
                    <p class="password-requirements">Security code is required for admin registration</p>
                </div>
                <button type="submit">Register as Admin</button>
            </form>
        </div>
    </div>
</body>
</html> 