<?php
session_start();
include 'config.php';

// Check if user is logged in
if (!isset($_SESSION['email']) || !isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'employee') {
    // Not logged in or not an employee, redirect to login
    header("Location: login.php");
    exit;
}

// Get employee data from database
$email = $_SESSION['email'];
$userId = $_SESSION['user_id'];
$employee = null;
$error = null;
$success = null;

try {
    $stmt = $conn->prepare("SELECT * FROM employees WHERE id = ? AND email = ?");
    $stmt->bind_param("is", $userId, $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
    } else {
        // Employee not found, session might be invalid
        session_destroy();
        header("Location: login.php?error=invalid_session");
        exit;
    }
} catch (Exception $e) {
    $error = "Database error: " . $e->getMessage();
}

// Check if first_login column exists
$columnCheckResult = $conn->query("SHOW COLUMNS FROM employees LIKE 'first_login'");
if ($columnCheckResult->num_rows == 0) {
    // Add first_login column
    $addColumnSQL = "ALTER TABLE employees ADD COLUMN first_login TINYINT(1) DEFAULT 0";
    $conn->query($addColumnSQL);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Employee Dashboard</title>
    <link rel="stylesheet" href="responsive.css">
    <link rel="stylesheet" href="form-mobile.css">
    <script src="mobile_optimizations.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');
        
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 0;
            min-height: 100vh;
        }
        
        .header {
            background: linear-gradient(to right, #36d1dc, #5b86e5);
            color: white;
            padding: 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            flex-wrap: wrap;
        }
        
        .header h1 {
            margin: 0;
            font-size: 24px;
        }
        
        .user-info {
            display: flex;
            align-items: center;
            margin-top: 5px;
            flex-wrap: nowrap;
        }
        
        .user-avatar {
            min-width: 40px;
            height: 40px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #5b86e5;
            font-weight: bold;
            margin-right: 10px;
            font-size: 16px;
            flex-shrink: 0;
        }
        
        .user-name {
            margin-right: 15px;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 150px;
            font-weight: 500;
        }
        
        .logout-btn {
            background-color: rgba(255,255,255,0.2);
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            text-decoration: none;
            transition: background-color 0.3s;
            white-space: nowrap;
            flex-shrink: 0;
            font-weight: 500;
        }
        
        .logout-btn:hover, .logout-btn:active {
            background-color: rgba(255,255,255,0.3);
        }
        
        .container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .dashboard-card {
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            padding: 25px;
            margin-bottom: 20px;
        }
        
        .dashboard-card h2 {
            margin-top: 0;
            color: #333;
            font-size: 20px;
            border-bottom: 1px solid #eee;
            padding-bottom: 15px;
            display: flex;
            align-items: center;
        }
        
        .dashboard-card h2::before {
            content: "";
            display: inline-block;
            width: 4px;
            height: 20px;
            background-color: #5b86e5;
            margin-right: 10px;
            border-radius: 4px;
        }
        
        .employee-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 20px;
        }
        
        .detail-item {
            margin-bottom: 15px;
            background-color: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
        }
        
        .detail-label {
            font-size: 14px;
            color: #666;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .detail-value {
            font-size: 16px;
            font-weight: 500;
            word-break: break-word;
            color: #333;
        }
        
        .error-message {
            background-color: #fce4ec;
            border: 1px solid #f8bbd0;
            color: #c62828;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
        }
        
        .quick-actions-container {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
            margin-top: 20px;
        }
        
        .action-button {
            background-color: #5b86e5;
            color: white;
            padding: 18px 20px;
            border-radius: 8px;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            min-width: 200px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
            transition: transform 0.2s, background-color 0.2s;
            text-align: center;
            font-weight: 500;
        }
        
        .action-button span {
            margin-right: 10px;
            font-size: 1.5em;
        }
        
        .action-button:hover, .action-button:active {
            background-color: #36d1dc !important;
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.2);
        }
        
        .action-button:active {
            transform: translateY(0);
        }
        
        /* Mobile responsiveness */
        @media (max-width: 768px) {
            .header {
                padding: 15px;
            }
            
            .header h1 {
                font-size: 22px;
                margin-right: 15px;
            }
            
            .user-info {
                margin-top: 0;
            }
            
            .user-avatar {
                min-width: 36px;
                height: 36px;
                font-size: 14px;
            }
            
            .container {
                padding: 15px;
                margin: 10px auto;
            }
            
            .dashboard-card {
                padding: 20px;
            }
            
            .employee-details {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .quick-actions-container {
                flex-direction: column;
                gap: 12px;
            }
            
            .action-button {
                width: 100%;
                min-width: unset;
                padding: 16px;
            }
            
            .password-change-form {
                width: 85%;
                padding: 20px;
            }
            
            .detail-item {
                padding: 10px;
                margin-bottom: 10px;
            }
        }
        
        @media (max-width: 480px) {
            .header {
                flex-direction: column;
                align-items: center;
                text-align: center;
                padding: 12px;
            }
            
            .header h1 {
                margin-bottom: 12px;
                font-size: 20px;
                margin-right: 0;
            }
            
            .user-info {
                width: 100%;
                justify-content: center;
            }
            
            .user-name {
                max-width: 120px;
                font-size: 14px;
            }
            
            .logout-btn {
                padding: 6px 12px;
                font-size: 14px;
            }
            
            .dashboard-card {
                padding: 15px;
            }
            
            .dashboard-card h2 {
                font-size: 18px;
                padding-bottom: 12px;
            }
            
            .detail-label {
                font-size: 13px;
            }
            
            .detail-value {
                font-size: 15px;
            }
            
            .action-button {
                padding: 14px;
                font-size: 15px;
            }
            
            .action-button span {
                font-size: 1.3em;
            }
        }
        
        @media (max-width: 380px) {
            .header h1 {
                font-size: 18px;
            }
            
            .user-avatar {
                min-width: 32px;
                height: 32px;
                font-size: 13px;
            }
            
            .user-name {
                max-width: 100px;
                font-size: 13px;
            }
            
            .logout-btn {
                padding: 5px 10px;
                font-size: 13px;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>Employee Dashboard</h1>
        <div class="user-info">
            <div class="user-avatar">
                <?php echo strtoupper(substr($employee['employee_name'], 0, 1)); ?>
            </div>
            <div class="user-name"><?php echo htmlspecialchars($employee['employee_name']); ?></div>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
    </div>
    
    <div class="container">
        <?php if(isset($error)): ?>
            <div class="error-message"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <?php if(isset($success)): ?>
            <div class="success-message"><?php echo $success; ?></div>
        <?php endif; ?>
        
        <div class="dashboard-card">
            <h2>Welcome, <?php echo htmlspecialchars($employee['employee_name']); ?>!</h2>
            <p>You've successfully logged in to your employee account.</p>
        </div>
        
        <div class="dashboard-card">
            <h2>Your Profile</h2>
            <div class="employee-details">
                <div>
                    <div class="detail-item">
                        <div class="detail-label">Employee ID</div>
                        <div class="detail-value"><?php echo htmlspecialchars($employee['employee_id']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Full Name</div>
                        <div class="detail-value"><?php echo htmlspecialchars($employee['employee_name']); ?></div>
                    </div>
                </div>
                <div>
                    <div class="detail-item">
                        <div class="detail-label">Email Address</div>
                        <div class="detail-value"><?php echo htmlspecialchars($employee['email']); ?></div>
                    </div>
                    <div class="detail-item">
                        <div class="detail-label">Account Created</div>
                        <div class="detail-value"><?php echo date('F j, Y', strtotime($employee['created_at'])); ?></div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="dashboard-card">
            <h2>Quick Actions</h2>
            <p>You can access employee functions here. Additional features will be available soon.</p>
            
            <div class="quick-actions-container">
                <a href="store.php" class="action-button">
                    <span>📍</span> Attendance Location
                </a>
                <!-- Additional quick actions can be added here -->
            </div>
        </div>
    </div>
    
    <script>
        // No password change functionality
    </script>
</body>
</html>
