<?php
// This is a diagnostic and fix script for admin registration

include "config.php";

echo "<h1>Admin Registration Fix</h1>";

try {
    // First test connection
    if (!isset($conn) || $conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    
    echo "<p style='color:green'>✓ Database connection is working!</p>";
    
    // Check admin table structure
    $result = $conn->query("DESCRIBE admin");
    if (!$result) {
        throw new Exception("Error checking admin table: " . $conn->error);
    }
    
    echo "<h2>Current 'admin' Table Structure:</h2>";
    echo "<ul>";
    while ($row = $result->fetch_assoc()) {
        echo "<li>{$row['Field']} - {$row['Type']} " . ($row['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "</li>";
    }
    echo "</ul>";
    
    // Extract the problematic code for analysis
    echo "<h2>Current Code in admin_signup.php:</h2>";
    echo "<pre style='background-color: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars('
$stmt = $conn->prepare("INSERT INTO admin (username, email, password, otp, user_type, otp_verified) VALUES (?, ?, ?, ?, ?, 0)");
if (!$stmt) {
    throw new Exception("Database error: " . $conn->error);
}

$stmt->bind_param("sssss", $username, $email, $password, $otp, $user_type);
');
    echo "</pre>";
    
    echo "<h2>The Fix:</h2>";
    echo "<p>The problem is that the SQL statement has 6 placeholders (?, ?, ?, ?, ?, 0) but bind_param only provides 5 parameters (\"sssss\").</p>";
    echo "<p>Here are two possible fixes:</p>";
    
    echo "<h3>Fix 1: Add the missing parameter</h3>";
    echo "<pre style='background-color: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars('
$otp_verified = 0; // Define the variable for binding
$stmt = $conn->prepare("INSERT INTO admin (username, email, password, otp, user_type, otp_verified) VALUES (?, ?, ?, ?, ?, ?)");
if (!$stmt) {
    throw new Exception("Database error: " . $conn->error);
}

$stmt->bind_param("sssssi", $username, $email, $password, $otp, $user_type, $otp_verified);
');
    echo "</pre>";
    
    echo "<h3>Fix 2: Remove the column from the prepared statement</h3>";
    echo "<pre style='background-color: #f5f5f5; padding: 15px; border-radius: 5px;'>";
    echo htmlspecialchars('
$stmt = $conn->prepare("INSERT INTO admin (username, email, password, otp, user_type) VALUES (?, ?, ?, ?, ?)");
if (!$stmt) {
    throw new Exception("Database error: " . $conn->error);
}

$stmt->bind_param("sssss", $username, $email, $password, $otp, $user_type);
');
    echo "</pre>";
    
    echo "<p>Let's try applying Fix 1 to your admin_signup.php file now...</p>";
    
    // First, backup the original file if not already backed up
    if (!file_exists('admin_signup_backup.php')) {
        copy('admin_signup.php', 'admin_signup_backup.php');
        echo "<p>Created backup of admin_signup.php</p>";
    }
    
    // Read the file
    $content = file_get_contents('admin_signup.php');
    if ($content === false) {
        throw new Exception("Could not read admin_signup.php");
    }
    
    // Make the fix - Method 1
    $fixed_content = str_replace(
        'INSERT INTO admin (username, email, password, otp, user_type, otp_verified) VALUES (?, ?, ?, ?, ?, 0)',
        'INSERT INTO admin (username, email, password, otp, user_type, otp_verified) VALUES (?, ?, ?, ?, ?, ?)',
        $content
    );
    
    // Add the otp_verified variable
    $fixed_content = str_replace(
        '$otp = rand(100000, 999999);',
        '$otp = rand(100000, 999999);
                    
                    // Add otp_verified variable with default value 0
                    $otp_verified = 0;',
        $fixed_content
    );
    
    // Update the bind_param call
    $fixed_content = str_replace(
        '$stmt->bind_param("sssss", $username, $email, $password, $otp, $user_type);',
        '$stmt->bind_param("sssssi", $username, $email, $password, $otp, $user_type, $otp_verified);',
        $fixed_content
    );
    
    // Save the fixed content
    if (file_put_contents('admin_signup.php', $fixed_content) === false) {
        throw new Exception("Could not write to admin_signup.php");
    }
    
    echo "<p style='color:green'>✓ Successfully fixed admin_signup.php!</p>";
    echo "<p>Now try registering as an admin again, it should work properly.</p>";
    
} catch (Exception $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?> 