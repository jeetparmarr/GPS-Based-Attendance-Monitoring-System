<?php 
session_start();
include 'config.php';

// Check if there's a verification success message
$verification_success = "";
if (isset($_SESSION['verification_success'])) {
    $verification_success = $_SESSION['verification_success'];
    unset($_SESSION['verification_success']);
}

// Reset authentication variables
$valid_admin_credentials = false;
$admin_id = 0;

// Process login form
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = mysqli_real_escape_string($conn, $_POST['email']);
    $password = $_POST['password'];
    $user_type = mysqli_real_escape_string($conn, $_POST['user_type']);
    $security_code = isset($_POST['security_code']) ? mysqli_real_escape_string($conn, $_POST['security_code']) : '';
    
    $error = "";
    
    // Determine which table to check based on user type
    $table = ($user_type == 'admin') ? 'admin' : 'employees';
    
    try {
        // Check if email exists
        $stmt = $conn->prepare("SELECT * FROM $table WHERE email = ?");
        if (!$stmt) {
            throw new Exception("Database error: " . $conn->error);
        }
        
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows > 0) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password'])) {
                // Check if OTP is verified
                if (!isset($user['otp_verified']) || $user['otp_verified'] != 1) {
                    // User exists but not verified
                    $error = "Your account is not verified yet. Please complete OTP verification.";
                    
                    // Store information for OTP verification
                    $_SESSION['email'] = $email;
                    $_SESSION['user_type'] = $user_type;
                    $_SESSION['needs_verification'] = true;
                    $_SESSION['return_to'] = 'login.php';
                    
                    echo '<div class="error-message">
                        ' . $error . ' 
                        <a href="verify_otp.php" class="verification-link">Verify Now</a>
                    </div>';
                } else {
                    // For admin users, set flag to show security code input
                    if ($user_type == 'admin') {
                        if (!empty($security_code)) {
                            // Security code is provided, verify it
                            $securityQuery = $conn->query("SELECT password FROM security_password WHERE code_type = 'admin_registration'");
                            if ($securityQuery && $securityQuery->num_rows > 0) {
                                $securityRow = $securityQuery->fetch_assoc();
                                $stored_security_code = $securityRow['password'];
                                
                                if ($security_code == $stored_security_code) {
                                    // Security code is correct, proceed with login
                                    $_SESSION['user_id'] = $user['id'];
                                    $_SESSION['username'] = $user['username'] ?? $user['employee_name'];
                                    $_SESSION['email'] = $user['email'];
                                    $_SESSION['user_type'] = $user_type;
                                    
                                    // Redirect to appropriate dashboard
                                    header("Location: admin.php");
                                    exit;
                                } else {
                                    $error = "Invalid security code. Admin login failed.";
                                }
                            } else {
                                $error = "Security code configuration error. Please contact administrator.";
                            }
                        } else {
                            // Password is correct but security code not yet provided
                            // Store user ID for security code verification
                            $valid_admin_credentials = true;
                            $admin_id = $user['id'];
                            $admin_username = $user['username'] ?? '';
                            $admin_email = $user['email'];
                        }
                    } else {
                        // For employee users, proceed with login
                        $_SESSION['user_id'] = $user['id'];
                        $_SESSION['username'] = $user['employee_name'];
                        $_SESSION['email'] = $user['email'];
                        $_SESSION['user_type'] = $user_type;
                        
                        // Set a flag to indicate the user came through login page
                        $_SESSION['login_redirect'] = true;
                        
                        // Check for first-time login
                        if (isset($user['first_login']) && $user['first_login'] == 1) {
                            $_SESSION['first_login'] = true;
                        }
                        
                        // Redirect to dashboard
                        header("Location: dashboard.php");
                        exit;
                    }
                }
            } else {
                $error = "Invalid password. Please try again.";
            }
        } else {
            $error = "No account found with this email. Please register.";
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
  <title>Login</title>
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

    .left-section h2 {
      font-size: 2.5em;
      margin-bottom: 20px;
    }

    .left-section p {
      margin-bottom: 30px;
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
    .right-section input[type="password"] {
      width: 100%;
      padding: 13px;
      border: 1px solid #ccc;
      border-radius: 8px;
      box-sizing: border-box;
      font-size: 16px;
      transition: border-color 0.3s;
    }

    .right-section input[type="email"]:focus,
    .right-section input[type="password"]:focus {
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

    .right-section a {
      display: block;
      text-align: right;
      margin-top: 15px;
      text-decoration: none;
      color: #0072ff;
      transition: color 0.3s;
    }

    .right-section a:hover {
      color: #0056b3;
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
    
    .admin-info {
      background-color: #e3f2fd;
      border: 1px solid #bbdefb;
      color: #0d47a1;
      padding: 15px;
      margin: 20px 0;
      border-radius: 10px;
      text-align: center;
      font-weight: 500;
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
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="left-section">
      <h2>Hello, Welcome!</h2>
      <p>Don't have an account?</p>
      <a href="signup.php">Register</a>
    </div>
    <div class="right-section">
      <h2>Login</h2>
      
      <?php if(isset($error) && !empty($error)): ?>
        <div class="error-message">
          <?php echo $error; ?>
          <?php if(isset($_SESSION['needs_otp_verification'])): ?>
            <div style="margin-top: 10px;">
              <a href="verify_otp.php" style="background-color: #0072ff; color: white; padding: 8px 15px; border-radius: 5px; text-decoration: none; display: inline-block; margin-top: 5px;">Verify OTP Now</a>
            </div>
          <?php endif; ?>
        </div>
      <?php endif; ?>
      
      <?php if(!empty($verification_success)): ?>
        <div class="success-message"><?php echo $verification_success; ?></div>
      <?php endif; ?>
      
      <?php if($valid_admin_credentials): ?>
        <div class="admin-info">
          Admin credentials verified. Please enter your security code to continue.
        </div>
        <form method="POST" action="">
          <input type="hidden" name="email" value="<?php echo htmlspecialchars($admin_email); ?>">
          <input type="hidden" name="password" value="<?php echo htmlspecialchars($password); ?>">
          <input type="hidden" name="user_type" value="admin">
          
          <div class="form-group">
            <label for="security_code">Security Code:</label>
            <input type="password" name="security_code" id="security_code" placeholder="Enter Security Code" required autofocus>
          </div>
          <button type="submit">Complete Admin Login</button>
        </form>
      <?php else: ?>
        <form method="POST" action="">
          <div class="form-group">
            <label for="email">Email:</label>
            <input type="email" name="email" id="email" placeholder="Enter Email" required>
          </div>
          <div class="form-group">
            <label for="password">Password:</label>
            <input type="password" name="password" id="password" placeholder="Enter Password" required>
          </div>
          <div class="form-group">
            <label>User Type:</label>
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
          <button type="submit">Login</button>
          <a href="forgot_password.php" class="forgot-password">Change/Forgot Password?</a>
        </form>
      <?php endif; ?>
    </div>
  </div>
</body>
</html>