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
if (!isset($_GET['id']) || empty($_GET['id'])) {
    $response = [
        'success' => false,
        'message' => 'Employee ID is required.'
    ];
    echo json_encode($response);
    exit;
}

$id = intval($_GET['id']);

try {
    // Prepare SQL statement to get the employee
    $stmt = $conn->prepare("SELECT id, employee_id, employee_name, email FROM employees WHERE id = ?");
    
    if (!$stmt) {
        throw new Exception("Database error: " . $conn->error);
    }
    
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        $employee = $result->fetch_assoc();
        $response = [
            'success' => true,
            'employee' => $employee
        ];
    } else {
        $response = [
            'success' => false,
            'message' => 'No employee found with the provided ID.'
        ];
    }
    
} catch (Exception $e) {
    $response = [
        'success' => false,
        'message' => $e->getMessage()
    ];
}

echo json_encode($response);
?> 