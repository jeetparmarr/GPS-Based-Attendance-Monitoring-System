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

// Ensure database connection is valid and tables exist
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
<title>Choose Role</title>
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
  background: linear-gradient(to bottom right, #6a11cb, #2575fc);
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
  color: #6a11cb;
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
  text-align: center;
  margin-bottom: 30px;
  font-size: 2em;
  color: #333;
}

.register-options {
  display: flex;
  justify-content: space-between;
  margin-top: 30px;
}

.register-option {
  text-align: center;
  width: 45%;
  padding: 25px 15px;
  border-radius: 10px;
  transition: all 0.3s ease;
  box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
  cursor: pointer;
  text-decoration: none;
  color: white;
}

.admin-option {
  background: linear-gradient(to bottom right, #ff416c, #ff4b2b);
}

.employee-option {
  background: linear-gradient(to bottom right, #36d1dc, #5b86e5);
}

.register-option:hover {
  transform: translateY(-5px);
  box-shadow: 0 10px 20px rgba(0, 0, 0, 0.15);
}

.register-option h3 {
  font-size: 1.5em;
  margin-bottom: 10px;
}

.register-option p {
  margin-bottom: 0;
}

.icon {
  font-size: 2.5em;
  margin-bottom: 15px;
}

.error-message {
  background-color: #fce4ec;
  border: 1px solid #f8bbd0;
  color: #c62828;
  padding: 15px;
  margin: 20px auto;
  width: 340px;
  text-align: center;
  border-radius: 10px;
  box-shadow: 0 3px 6px rgba(0, 0, 0, 0.16);
}

/* Add flex-direction responsive rule */
@media (max-width: 768px) {
  .container {
    width: 95%;
    flex-direction: column;
  }
  
  .left-section,
  .right-section {
    width: 100%;
    padding: 30px;
  }
  
  .register-options {
    flex-direction: column;
    gap: 20px;
  }
  
  .register-option {
    width: 100%;
  }
}
</style>
</head>
<body>
<div class="container">
    <div class="left-section">
        <h2>Get Started!</h2>
        <p>Already have an account with us?</p>
        <a href="login.php">Login Instead</a>
    </div>
    <div class="right-section">
        <h2>Choose Registration Type</h2>
        
        <?php if(isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="register-options">
            <a href="admin_signup.php" class="register-option admin-option">
                <div class="icon">👑</div>
                <h3>Admin Registration</h3>
                <p>Register as a system administrator</p>
            </a>
            
            <a href="employee_signup.php" class="register-option employee-option">
                <div class="icon">👤</div>
                <h3>Employee Registration</h3>
                <p>Register as an employee</p>
            </a>
        </div>
    </div>
</div>
</body>
</html>