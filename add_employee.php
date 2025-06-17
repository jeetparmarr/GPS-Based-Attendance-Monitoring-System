<?php
session_start();
include 'config.php';

// Check if user is admin
if ((!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') && 
    !isset($_SESSION['admin_verified'])) {
    // Return error response
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized access. Please login as admin.'
    ]);
    exit;
}

// Check if request method is POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid request method.'
    ]);
    exit;
}

// Sanitize and validate input
$employee_id = mysqli_real_escape_string($conn, trim($_POST['employee_id']));
$employee_name = mysqli_real_escape_string($conn, trim($_POST['employee_name']));
$email = mysqli_real_escape_string($conn, trim($_POST['email']));

// Validate email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode([
        'success' => false,
        'message' => 'Invalid email format.'
    ]);
    exit;
}

// Default password is "employee@123"
$default_password = 'employee@123';
$password_hash = password_hash($default_password, PASSWORD_BCRYPT);
$user_type = 'employee';
$first_login = 1; // Flag to indicate this is a new account

try {
    // Check if employee ID already exists
    $stmt = $conn->prepare("SELECT * FROM employees WHERE employee_id = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Employee ID already exists!'
        ]);
        exit;
    }
    
    // Check if email already exists
    $stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        echo json_encode([
            'success' => false,
            'message' => 'Email already exists!'
        ]);
        exit;
    }
    
    // Check if employees table exists, if not create it
    $tableCheckResult = $conn->query("SHOW TABLES LIKE 'employees'");
    if ($tableCheckResult->num_rows == 0) {
        // Create employees table
        $createTableSQL = "CREATE TABLE employees (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            employee_id VARCHAR(50) NOT NULL UNIQUE,
            employee_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            otp VARCHAR(20) DEFAULT NULL,
            otp_verified TINYINT(1) DEFAULT 1,
            first_login TINYINT(1) DEFAULT 1,
            user_type VARCHAR(20) NOT NULL DEFAULT 'employee',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        
        if (!$conn->query($createTableSQL)) {
            throw new Exception("Error creating employees table: " . $conn->error);
        }
    } else {
        // Check if first_login column exists
        $columnCheckResult = $conn->query("SHOW COLUMNS FROM employees LIKE 'first_login'");
        if ($columnCheckResult->num_rows == 0) {
            // Add first_login column
            $addColumnSQL = "ALTER TABLE employees ADD COLUMN first_login TINYINT(1) DEFAULT 0";
            if (!$conn->query($addColumnSQL)) {
                throw new Exception("Error adding first_login column: " . $conn->error);
            }
        }
    }
    
    // Insert new employee
    $stmt = $conn->prepare("INSERT INTO employees (employee_id, employee_name, email, password, otp, otp_verified, first_login, user_type) VALUES (?, ?, ?, ?, NULL, ?, ?, ?)");
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    // Set values for otp_verified and first_login
    $otp_verified = 1;
    $first_login = 1;
    
    $stmt->bind_param("ssssiis", $employee_id, $employee_name, $email, $password_hash, $otp_verified, $first_login, $user_type);
    
    if ($stmt->execute()) {
        echo json_encode([
            'success' => true,
            'message' => "Employee {$employee_name} has been added successfully with default password."
        ]);
    } else {
        throw new Exception("Error adding employee: " . $conn->error);
    }
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Error adding employee: " . $e->getMessage());
    
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?> 