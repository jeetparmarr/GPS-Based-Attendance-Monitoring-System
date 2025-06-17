<?php
include "config.php";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $employee_id = $_POST['employee_id'];
    $employee_name = $_POST['employee_name'];
    $latitude = $_POST['latitude'];
    $longitude = $_POST['longitude'];
    $address = $_POST['address'];

    $stmt = $conn->prepare("INSERT INTO outside_office (employee_id, employee_name, latitude, longitude, address, check_out_time) VALUES (?, ?, ?, ?, ?, NOW())");
    $stmt->bind_param("sssss", $employee_id, $employee_name, $latitude, $longitude, $address);

    if ($stmt->execute()) {
        echo "Check-out (outside office) successful!";
    } else {
        echo "Error: " . $stmt->error;
    }

    $stmt->close();
    $conn->close();
}
?>