<?php
session_start();
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Get common parameters
    $employee_id = isset($_POST['employee_id']) ? $_POST['employee_id'] : '';
    $employee_name = isset($_POST['employee_name']) ? $_POST['employee_name'] : '';
    $email = isset($_POST['email']) ? $_POST['email'] : '';
    $latitude = isset($_POST['latitude']) ? $_POST['latitude'] : '';
    $longitude = isset($_POST['longitude']) ? $_POST['longitude'] : '';
    $address = isset($_POST['address']) ? $_POST['address'] : '';
    $location = isset($_POST['location']) ? $_POST['location'] : 'inside'; // Default to inside
    $current_time = date('Y-m-d H:i:s');

    // Validate required fields
    if (empty($employee_id) || empty($latitude) || empty($longitude)) {
        echo "Error: Missing required fields.";
        exit;
    }

    // Only allow check-out if inside office
    if ($location !== 'inside') {
        echo "Error: Cannot check out when outside the office radius. Please come to the office.";
        exit;
    }

    // Clean address field if needed
    if (strpos($address, 'Address:') !== false) {
        $address = preg_replace('/^.*Address:\s*/', '', $address);
    }

    // Ensure database connection is valid
    if (!$conn || $conn->connect_error) {
        echo "Database connection error. Please try again.";
        exit;
    }

    // Use the check_out table instead of check_outs
    $table_name = 'check_out';

    // Check if the old table exists and we need to migrate data
    $check_old_table = $conn->query("SHOW TABLES LIKE 'check_outs'");
    if ($check_old_table->num_rows > 0) {
        // Check if the new table already exists
        $check_new_table = $conn->query("SHOW TABLES LIKE '$table_name'");
        if ($check_new_table->num_rows == 0) {
            // Create new table with structure from old table
            $create_table = "CREATE TABLE $table_name LIKE check_outs";
            if (!$conn->query($create_table)) {
                echo "Error creating new table: " . $conn->error;
                exit;
            }
            
            // Copy data from old table to new table
            $copy_data = "INSERT INTO $table_name SELECT * FROM check_outs";
            $conn->query($copy_data); // Don't exit on error, just continue with new table
        }
    }

    // Check if the table exists, create if not
    $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check_table->num_rows == 0) {
        // Table doesn't exist, create it with consistent column name
        $create_table = "CREATE TABLE $table_name (
            id INT(11) AUTO_INCREMENT PRIMARY KEY,
            employee_id VARCHAR(50) NOT NULL,
            employee_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            latitude VARCHAR(50) NOT NULL,
            longitude VARCHAR(50) NOT NULL,
            address TEXT NOT NULL,
            check_time DATETIME NOT NULL,
            distance VARCHAR(50) DEFAULT 'N/A'
        )";
        if (!$conn->query($create_table)) {
            echo "Error creating table: " . $conn->error;
            exit;
        }
        // New table uses consistent naming: check_time
        $time_column = 'check_time';
    } else {
        // Table exists, check which time column it has
        $check_out_time_column = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'check_out_time'");
        $check_time_column = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'check_time'");
        
        if ($check_out_time_column->num_rows > 0) {
            // Using check_out_time
            $time_column = 'check_out_time';
        } elseif ($check_time_column->num_rows > 0) {
            // Using check_time
            $time_column = 'check_time';
        } else {
            // Neither column exists, add check_time
            $alter_table = "ALTER TABLE $table_name ADD COLUMN check_time DATETIME NOT NULL";
            if (!$conn->query($alter_table)) {
                echo "Error adding time column: " . $conn->error;
                exit;
            }
            $time_column = 'check_time';
        }
        
        // Check if email column exists
        $check_column = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'email'");
        if ($check_column->num_rows == 0) {
            // Email column doesn't exist, add it
            $alter_table = "ALTER TABLE $table_name ADD COLUMN email VARCHAR(100) AFTER employee_name";
            if (!$conn->query($alter_table)) {
                // If we can't add email column, proceed without it
                $email_exists = false;
            } else {
                $email_exists = true;
            }
        } else {
            $email_exists = true;
        }

        // Check if distance column exists
        $check_distance_column = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'distance'");
        if ($check_distance_column->num_rows == 0) {
            // Distance column doesn't exist, add it
            $conn->query("ALTER TABLE $table_name ADD COLUMN distance VARCHAR(50) DEFAULT 'N/A'");
        }
    }

    // Look up today's check-in records for this employee
    $today = date('Y-m-d');
    $check_in_record = false;
    
    // Check if check_in has a record for today
    // First determine which time column is used in check_in
    $check_in_table = 'check_in';
    $check_old_table = $conn->query("SHOW TABLES LIKE 'inside_office'");
    if ($check_old_table->num_rows > 0 && !$conn->query("SHOW TABLES LIKE 'check_in'")->num_rows) {
        $check_in_table = 'inside_office';
    }
    
    $check_in_time_column = $conn->query("SHOW COLUMNS FROM $check_in_table LIKE 'check_in_time'");
    $check_in_check_time_column = $conn->query("SHOW COLUMNS FROM $check_in_table LIKE 'check_time'");
    
    if ($check_in_time_column->num_rows > 0) {
        $check_in_time_col = 'check_in_time';
    } else {
        $check_in_time_col = 'check_time';
    }
    
    $check_sql = "SELECT id FROM $check_in_table WHERE employee_id = ? AND DATE($check_in_time_col) = ? ORDER BY $check_in_time_col DESC LIMIT 1";
    $check_stmt = $conn->prepare($check_sql);
    if (!$check_stmt) {
        // If statement preparation fails, don't stop execution, just note no check-in found
        $has_checkin = false;
    } else {
        $check_stmt->bind_param("ss", $employee_id, $today);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if ($check_result->num_rows > 0) {
            $check_in_record = true;
        }
        $check_stmt->close();
    }
    
    // Prepare SQL statement based on column existence and time column name
    if (isset($email_exists) && $email_exists === false) {
        // Email column doesn't exist, don't include it
        $stmt = $conn->prepare("INSERT INTO $table_name (employee_id, employee_name, latitude, longitude, address, $time_column) VALUES (?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo "Error preparing statement: " . $conn->error;
            exit;
        }
        $stmt->bind_param("ssssss", $employee_id, $employee_name, $latitude, $longitude, $address, $current_time);
    } else {
        // Email column exists, include it
        $stmt = $conn->prepare("INSERT INTO $table_name (employee_id, employee_name, email, latitude, longitude, address, $time_column) VALUES (?, ?, ?, ?, ?, ?, ?)");
        if (!$stmt) {
            echo "Error preparing statement: " . $conn->error;
            exit;
        }
        $stmt->bind_param("sssssss", $employee_id, $employee_name, $email, $latitude, $longitude, $address, $current_time);
    }

    if ($stmt->execute()) {
        if ($check_in_record) {
            echo "Check-out successful! You have completed your workday.";
        } else {
            echo "Check-out successful, but no check-in record was found for today.";
        }
    } else {
        echo "Error during check-out: " . $conn->error;
    }
    
    // Close the statement
    $stmt->close();
} else {
    echo "Invalid request method. Please use POST.";
}

// Close the connection
$conn->close();
?>