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

    // Only allow check-in if inside office
    if ($location !== 'inside') {
        echo "Error: Cannot check in when outside the office radius. Please come to the office.";
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

    // Use the check_in table instead of inside_office
    $table_name = 'check_in';

    // Check if the old table exists and we need to migrate data
    $check_old_table = $conn->query("SHOW TABLES LIKE 'inside_office'");
    if ($check_old_table->num_rows > 0) {
        // Check if the new table already exists
        $check_new_table = $conn->query("SHOW TABLES LIKE '$table_name'");
        if ($check_new_table->num_rows == 0) {
            // Create new table with structure from old table
            $create_table = "CREATE TABLE $table_name LIKE inside_office";
            if (!$conn->query($create_table)) {
                echo "Error creating new table: " . $conn->error;
                exit;
            }
            
            // Copy data from old table to new table
            $copy_data = "INSERT INTO $table_name SELECT * FROM inside_office";
            $conn->query($copy_data); // Don't exit on error, just continue with new table
        }
    }

    // Check if the table exists, create if not
    $check_table = $conn->query("SHOW TABLES LIKE '$table_name'");
    if ($check_table->num_rows == 0) {
        // Table doesn't exist, create it with the consistent column name
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
        // New table created with check_time
        $time_column = 'check_time';
    } else {
        // Table exists, check which time column it has
        $check_in_time_column = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'check_in_time'");
        $check_time_column = $conn->query("SHOW COLUMNS FROM $table_name LIKE 'check_time'");
        
        if ($check_in_time_column->num_rows > 0) {
            // Using check_in_time
            $time_column = 'check_in_time';
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
        echo "Check-in successful! Your attendance has been recorded.";
    } else {
        echo "Error during check-in: " . $conn->error;
    }
    
    // Close the statement
    $stmt->close();
} else {
    echo "Invalid request method. Please use POST.";
}

// Close the connection
$conn->close();
?>