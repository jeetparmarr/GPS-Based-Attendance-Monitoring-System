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

// Check if ID is provided
if (!isset($_POST['id']) || empty($_POST['id'])) {
    $response = [
        'success' => false,
        'message' => 'Employee ID is required.'
    ];
    echo json_encode($response);
    exit;
}

$id = intval($_POST['id']);

try {
    // Prepare SQL statement to delete the employee
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    
    if ($stmt->execute()) {
        if ($stmt->affected_rows > 0) {
            $response = [
                'success' => true,
                'message' => 'Employee deleted successfully.'
            ];
        } else {
            $response = [
                'success' => false,
                'message' => 'No employee found with the provided ID.'
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