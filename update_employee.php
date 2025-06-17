<?php
session_start();
include 'config.php';

// Check if user is logged in as admin
if (!isset($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    $response = [
        'success' => false,
        'message' => 'Unauthorized access.'
    ];
    echo json_encode($response);
    exit;
}

// Check if required fields are provided
if (
    !isset($_POST['id']) || empty($_POST['id']) ||
    !isset($_POST['employee_id']) || empty($_POST['employee_id']) ||
    !isset($_POST['employee_name']) || empty($_POST['employee_name'])
) {
    $response = [
        'success' => false,
        'message' => 'All fields are required.'
    ];
    echo json_encode($response);
    exit;
}

$id = intval($_POST['id']);
$employee_id = mysqli_real_escape_string($conn, $_POST['employee_id']);
$employee_name = mysqli_real_escape_string($conn, $_POST['employee_name']);

try {
    // Check if employee_id already exists (except for the current employee being edited)
    $checkStmt = $conn->prepare("SELECT id FROM employees WHERE employee_id = ? AND id != ?");
    
    if (!$checkStmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $checkStmt->bind_param("si", $employee_id, $id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $response = [
            'success' => false,
            'message' => 'Employee ID already exists. Please choose a different one.'
        ];
        echo json_encode($response);
        exit;
    }
    
    // Prepare SQL statement to update the employee (not updating email)
    $stmt = $conn->prepare("UPDATE employees SET employee_id = ?, employee_name = ? WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("ssi", $employee_id, $employee_name, $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response = [
                'success' => true,
                'message' => 'Employee information updated successfully.'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No changes were made or employee not found.'
            ];
        }
    } else {
        throw new Exception("Error executing query: " . $stmt->error);
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?> 