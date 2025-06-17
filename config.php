<?php
$host = "sql312.infinityfree.com"; 
$user = "if0_38228795"; 
$pass = "Infinity3138"; 
$dbname = "if0_38228795_gps"; 

// Error handling function
function displayDatabaseError($message, $error) {
    die("<div style='background-color: #fce4ec; border: 1px solid #f8bbd0; color: #c62828; padding: 15px; margin: 20px auto; width: 80%; text-align: center; border-radius: 10px; font-family: Arial, sans-serif;'>
        <h3>Database Error</h3>
        <p>$message</p>
        <p><strong>Error Details:</strong> $error</p>
        <p>Please contact the administrator for assistance.</p>
    </div>");
}

try {
    // First connect without specifying a database
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        displayDatabaseError("Connection failed", $conn->connect_error);
    }
    
    // Create database if it doesn't exist
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if (!$conn->query($sql)) {
        displayDatabaseError("Error creating database", $conn->error);
    }
    
    // Close the initial connection
    $conn->close();
    
    // Reconnect with database selected
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        displayDatabaseError("Connection failed after database creation", $conn->connect_error);
    }
    
    // Set character set
    $conn->set_charset("utf8mb4");
    
    // Function to ensure tables exist
    function ensureTablesExist($conn) {
        // Check/create employees table
        $result = $conn->query("SHOW TABLES LIKE 'employees'");
        if ($result->num_rows == 0) {
            $sql = "CREATE TABLE employees (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                employee_id VARCHAR(50) NOT NULL UNIQUE,
                employee_name VARCHAR(100) NOT NULL,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                otp VARCHAR(20) DEFAULT NULL,
                otp_verified TINYINT(1) DEFAULT 0,
                first_login TINYINT(1) DEFAULT 1,
                user_type VARCHAR(20) NOT NULL DEFAULT 'employee',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            if (!$conn->query($sql)) {
                displayDatabaseError("Error creating employees table", $conn->error);
            }
        }
        
        // Check/create admin table
        $result = $conn->query("SHOW TABLES LIKE 'admin'");
        if ($result->num_rows == 0) {
            $sql = "CREATE TABLE admin (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL UNIQUE,
                email VARCHAR(100) NOT NULL UNIQUE,
                password VARCHAR(255) NOT NULL,
                otp VARCHAR(20) DEFAULT NULL,
                otp_verified TINYINT(1) DEFAULT 0,
                user_type VARCHAR(20) NOT NULL DEFAULT 'admin',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            if (!$conn->query($sql)) {
                displayDatabaseError("Error creating admin table", $conn->error);
            }
        }
        
        // Check/create security_password table
        $result = $conn->query("SHOW TABLES LIKE 'security_password'");
        if ($result->num_rows == 0) {
            $sql = "CREATE TABLE security_password (
                id INT(11) AUTO_INCREMENT PRIMARY KEY,
                code_type VARCHAR(50) NOT NULL,
                password VARCHAR(255) NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            if (!$conn->query($sql)) {
                displayDatabaseError("Error creating security_password table", $conn->error);
            }
            
            // Insert default security code
            $defaultCode = password_hash('user@123', PASSWORD_BCRYPT);
            $stmt = $conn->prepare("INSERT INTO security_password (code_type, password) VALUES ('admin_registration', ?)");
            $stmt->bind_param("s", $defaultCode);
            $stmt->execute();
            $stmt->close();
        }
    }
    
    // Ensure tables exist
    ensureTablesExist($conn);
    
} catch (Exception $e) {
    displayDatabaseError("An unexpected error occurred", $e->getMessage());
}
?>