<?php
// This is a simple database test file

// Database connection parameters
$host = "localhost"; 
$user = "root"; 
$pass = ""; 
$dbname = "gps"; 

echo "<h1>Database Connection Test</h1>";

try {
    // Connect to MySQL (without specifying a database)
    echo "<p>Attempting to connect to MySQL server...</p>";
    $conn = new mysqli($host, $user, $pass);
    
    if ($conn->connect_error) {
        throw new Exception("Connection to MySQL server failed: " . $conn->connect_error);
    }
    
    echo "<p style='color:green'>✓ Connected to MySQL server successfully!</p>";
    
    // Try to create database if it doesn't exist
    echo "<p>Checking/creating database '{$dbname}'...</p>";
    $sql = "CREATE DATABASE IF NOT EXISTS $dbname";
    if (!$conn->query($sql)) {
        throw new Exception("Error creating database: " . $conn->error);
    }
    
    echo "<p style='color:green'>✓ Database '{$dbname}' is ready!</p>";
    
    // Close the initial connection
    $conn->close();
    
    // Reconnect with database selected
    echo "<p>Connecting to the '{$dbname}' database...</p>";
    $conn = new mysqli($host, $user, $pass, $dbname);
    
    if ($conn->connect_error) {
        throw new Exception("Connection to database failed: " . $conn->connect_error);
    }
    
    echo "<p style='color:green'>✓ Connected to '{$dbname}' database successfully!</p>";
    
    // Check for tables
    echo "<h2>Checking Database Tables</h2>";
    
    $tables = array('employees', 'admin', 'security_password');
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '{$table}'");
        
        if ($result->num_rows > 0) {
            echo "<p style='color:green'>✓ Table '{$table}' exists</p>";
            
            // Get column information
            $columns = $conn->query("DESCRIBE {$table}");
            echo "<ul>";
            while ($col = $columns->fetch_assoc()) {
                echo "<li>{$col['Field']} - {$col['Type']} " . ($col['Null'] === 'NO' ? 'NOT NULL' : 'NULL') . "</li>";
            }
            echo "</ul>";
        } else {
            echo "<p style='color:red'>✗ Table '{$table}' does not exist</p>";
        }
    }
    
} catch (Exception $e) {
    echo "<p style='color:red'><strong>Error:</strong> " . $e->getMessage() . "</p>";
}
?> 