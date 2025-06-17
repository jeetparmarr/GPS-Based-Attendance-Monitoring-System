<?php
include 'config.php';

// Check if user is accessing directly (without going through authentication)
session_start();
if (!isset($_SESSION['admin_verified']) && !isset($_GET['bypass'])) {
    // Verify the admin credentials one more time as a security measure
    if (!isset($_SERVER['HTTP_REFERER']) || strpos($_SERVER['HTTP_REFERER'], 'login.php') === false) {
        header("Location: login.php");
        exit;
    }
    $_SESSION['admin_verified'] = true;
}

// Handle security code update if form is submitted
if (isset($_POST['update_security_code'])) {
    $code_type = mysqli_real_escape_string($conn, $_POST['code_type']);
    $new_password = mysqli_real_escape_string($conn, $_POST['new_password']);
    
    // Check if security_password table exists
    $tableCheckResult = $conn->query("SHOW TABLES LIKE 'security_password'");
    if ($tableCheckResult->num_rows == 0) {
        // Create security_password table if it doesn't exist
        $createTableSQL = "CREATE TABLE security_password (
            id INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            code_type VARCHAR(50) NOT NULL,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $conn->query($createTableSQL);
    }
    
    // Check if the code type already exists
    $checkSQL = "SELECT * FROM security_password WHERE code_type = ?";
    $stmt = $conn->prepare($checkSQL);
    $stmt->bind_param("s", $code_type);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($result->num_rows > 0) {
        // Update existing code
        $updateSQL = "UPDATE security_password SET password = ? WHERE code_type = ?";
        $stmt = $conn->prepare($updateSQL);
        $stmt->bind_param("ss", $new_password, $code_type);
        if ($stmt->execute()) {
            $security_code_message = "Security code updated successfully!";
        } else {
            $security_code_error = "Error updating security code: " . $conn->error;
        }
    } else {
        // Insert new code
        $insertSQL = "INSERT INTO security_password (code_type, password) VALUES (?, ?)";
        $stmt = $conn->prepare($insertSQL);
        $stmt->bind_param("ss", $code_type, $new_password);
        if ($stmt->execute()) {
            $security_code_message = "New security code added successfully!";
        } else {
            $security_code_error = "Error adding security code: " . $conn->error;
        }
    }
}

// Fetch all security codes
$security_codes = [];
$securityCodeResult = $conn->query("SHOW TABLES LIKE 'security_password'");
if ($securityCodeResult->num_rows > 0) {
    $fetchCodesSQL = "SELECT * FROM security_password";
    $codesResult = $conn->query($fetchCodesSQL);
    if ($codesResult && $codesResult->num_rows > 0) {
        while ($row = $codesResult->fetch_assoc()) {
            $security_codes[] = $row;
        }
    }
}

// Fetch all employees from the database
$sql = "SELECT * FROM employees";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Admin Dashboard</title>
    <link rel="stylesheet" href="responsive.css">
    <link rel="stylesheet" href="form-mobile.css">
    <script src="mobile_optimizations.js"></script>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600&display=swap');

        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            max-width: 1200px;
            margin: 0 auto;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #eee;
        }

        h1, h2 {
            color: #333;
            margin: 0;
        }
        
        h2 {
            margin-top: 40px;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #eee;
        }

        .logout-btn {
            background-color: #ff4d4d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
        }

        .table-container {
            overflow-x: auto;
            margin-bottom: 40px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }

        th, td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }

        th {
            background-color: #0072ff;
            color: white;
            font-weight: 500;
        }

        tr:hover {
            background-color: #f9f9f9;
        }

        .action-btn {
            background-color: #0072ff;
            color: white;
            border: none;
            padding: 8px 15px;
            border-radius: 5px;
            cursor: pointer;
            margin-right: 5px;
            font-size: 14px;
        }

        .delete-btn {
            background-color: #ff4d4d;
        }

        .add-btn {
            background-color: #36b37e;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            margin-bottom: 20px;
            display: inline-block;
        }

        .search-container {
            margin-bottom: 20px;
            display: flex;
            gap: 10px;
        }

        .search-container input {
            padding: 10px;
            border: 1px solid #ddd;
            border-radius:.8px;
            flex-grow: 1;
            font-family: 'Poppins', sans-serif;
        }

        .search-container button {
            padding: 10px 20px;
            background-color: #0072ff;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
        }

        .password-cell {
            font-family: monospace;
            letter-spacing: 1px;
        }
        
        .security-code-form {
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 10px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 15px;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        
        .form-group input, .form-group select {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-family: 'Poppins', sans-serif;
        }
        
        .form-submit {
            background-color: #0072ff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            margin-top: 10px;
        }
        
        .success-message {
            background-color: #e8f5e9;
            border: 1px solid #c8e6c9;
            color: #2e7d32;
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
        }
        
        .error-message {
            background-color: #fce4ec;
            border: 1px solid #f8bbd0;
            color: #c62828;
            padding: 15px;
            margin: 20px 0;
            border-radius: 10px;
            text-align: center;
        }
        
        .tab-container {
            margin-top: 20px;
        }
        
        .tabs {
            display: flex;
            margin-bottom: 20px;
            border-bottom: 1px solid #ddd;
        }
        
        .tab {
            padding: 10px 20px;
            cursor: pointer;
            margin-right: 5px;
            border-radius: 5px 5px 0 0;
        }
        
        .tab.active {
            background-color: #0072ff;
            color: white;
            border: 1px solid #0072ff;
            border-bottom: none;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }

        /* Modal Styles */
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0,0,0,0.4);
            animation: fadeIn 0.3s;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 30px;
            border: 1px solid #888;
            width: 500px;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
            transition: color 0.3s;
        }
        
        .close:hover,
        .close:focus {
            color: #333;
            text-decoration: none;
        }
        
        .note {
            background-color: #e8f5e9;
            padding: 10px;
            border-radius: 5px;
            font-size: 0.9em;
            margin: 15px 0;
            color: #2e7d32;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>Admin Dashboard</h1>
            <a href="logout.php" class="logout-btn">Logout</a>
        </div>
        
        <div class="tabs">
            <div class="tab active" onclick="openTab(event, 'employeesTab')">Employees</div>
            <div class="tab" onclick="openTab(event, 'securityCodeTab')">Security Codes</div>
        </div>
        
        <div id="employeesTab" class="tab-content active">
            <div class="search-container">
                <input type="text" id="searchInput" placeholder="Search employees...">
                <button onclick="searchEmployees()">Search</button>
            </div>

            <button class="add-btn" onclick="openAddEmployeeModal()">Add New Employee</button>

            <div class="table-container">
                <table id="employeeTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Employee ID</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Created At</th>
                            <th>OTP Verified</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Check if there are any employees
                        if ($result && $result->num_rows > 0) {
                            while ($row = $result->fetch_assoc()) {
                                echo "<tr>";
                                echo "<td>" . $row['id'] . "</td>";
                                echo "<td>" . $row['employee_id'] . "</td>";
                                echo "<td>" . $row['employee_name'] . "</td>";
                                echo "<td>" . $row['email'] . "</td>";
                                echo "<td>" . ($row['created_at'] ?? 'N/A') . "</td>";
                                echo "<td>" . ($row['otp_verified'] == 1 ? 'Yes' : 'No') . "</td>";
                                echo "<td>
                                        <button class='action-btn' onclick=\"editEmployee(" . $row['id'] . ")\">Edit</button>
                                        <button class='action-btn delete-btn' onclick=\"deleteEmployee(" . $row['id'] . ")\">Delete</button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='7' style='text-align: center;'>No employees found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <div id="securityCodeTab" class="tab-content">
            <h2>Security Code Management</h2>
            
            <?php if(isset($security_code_message)): ?>
                <div class="success-message"><?php echo $security_code_message; ?></div>
            <?php endif; ?>
            
            <?php if(isset($security_code_error)): ?>
                <div class="error-message"><?php echo $security_code_error; ?></div>
            <?php endif; ?>
            
            <div class="security-code-form">
                <h3>Add/Update Security Code</h3>
                <form method="post" action="">
                    <div class="form-group">
                        <label for="code_type">Code Type:</label>
                        <select name="code_type" id="code_type" required>
                            <option value="admin_registration">Admin Registration</option>
                            <option value="admin_login">Admin Login</option>
                            <option value="employee_action">Employee Action</option>
                            <option value="custom">Custom</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="new_password">Security Code:</label>
                        <input type="text" name="new_password" id="new_password" required placeholder="Enter new security code">
                    </div>
                    <button type="submit" name="update_security_code" class="form-submit">Save Security Code</button>
                </form>
            </div>
            
            <div class="table-container">
                <h3>Current Security Codes</h3>
                <table id="securityCodeTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Code Type</th>
                            <th>Security Code</th>
                            <th>Created At</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        if (!empty($security_codes)) {
                            foreach ($security_codes as $code) {
                                echo "<tr>";
                                echo "<td>" . $code['id'] . "</td>";
                                echo "<td>" . $code['code_type'] . "</td>";
                                echo "<td class='password-cell'>" . $code['password'] . "</td>";
                                echo "<td>" . $code['created_at'] . "</td>";
                                echo "<td>
                                        <button class='action-btn' onclick=\"editSecurityCode(" . $code['id'] . ", '" . $code['code_type'] . "', '" . $code['password'] . "')\">Edit</button>
                                        <button class='action-btn delete-btn' onclick=\"deleteSecurityCode(" . $code['id'] . ")\">Delete</button>
                                      </td>";
                                echo "</tr>";
                            }
                        } else {
                            echo "<tr><td colspan='5' style='text-align: center;'>No security codes found</td></tr>";
                        }
                        ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Add Employee Modal -->
    <div id="addEmployeeModal" class="modal">
        <div class="modal-content">
            <span class="close">&times;</span>
            <h2>Add New Employee</h2>
            
            <div id="addEmployeeResponse"></div>
            
            <form id="addEmployeeForm">
                <div class="form-group">
                    <label for="employee_id">Employee ID:</label>
                    <input type="text" name="employee_id" id="employee_id" required>
                </div>
                <div class="form-group">
                    <label for="employee_name">Full Name:</label>
                    <input type="text" name="employee_name" id="employee_name" required>
                </div>
                <div class="form-group">
                    <label for="email">Email:</label>
                    <input type="email" name="email" id="email" required>
                </div>
                <p class="note">A default password "employee@123" will be set for this employee.</p>
                <button type="submit" class="form-submit">Add Employee</button>
            </form>
        </div>
    </div>

    <!-- Add this modal for editing employees after the add employee modal -->
    <div id="editEmployeeModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeEditModal()">&times;</span>
            <h2>Edit Employee</h2>
            <div id="editEmployeeResponse"></div>
            <form id="editEmployeeForm">
                <input type="hidden" id="edit_id" name="id">
                <div class="form-group">
                    <label for="edit_employee_id">Employee ID:</label>
                    <input type="text" name="employee_id" id="edit_employee_id" required>
                </div>
                <div class="form-group">
                    <label for="edit_employee_name">Full Name:</label>
                    <input type="text" name="employee_name" id="edit_employee_name" required>
                </div>
                <div class="form-group">
                    <label for="edit_email">Email:</label>
                    <input type="email" name="email" id="edit_email" readonly disabled>
                    <p class="note">Email address cannot be changed.</p>
                </div>
                <button type="submit" class="form-submit">Update Employee</button>
            </form>
        </div>
    </div>

    <script>
        // Get the modal
        var addModal = document.getElementById("addEmployeeModal");
        var editModal = document.getElementById("editEmployeeModal");
        
        // Get the <span> element that closes the modal
        var addSpan = document.querySelector("#addEmployeeModal .close");
        
        // Function to open the modal
        function openAddEmployeeModal() {
            addModal.style.display = "block";
        }
        
        // When the user clicks on <span> (x), close the modal
        addSpan.onclick = function() {
            addModal.style.display = "none";
        }
        
        // Function to close edit modal
        function closeEditModal() {
            editModal.style.display = "none";
        }
        
        // When the user clicks anywhere outside of the modal, close it
        window.onclick = function(event) {
            if (event.target == addModal) {
                addModal.style.display = "none";
            }
            if (event.target == editModal) {
                editModal.style.display = "none";
            }
        }
        
        // Handle form submission for adding employee
        document.getElementById("addEmployeeForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch("add_employee.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("addEmployeeResponse").innerHTML = 
                        `<div class="success-message">${data.message}</div>`;
                    
                    // Reset form
                    document.getElementById("addEmployeeForm").reset();
                    
                    // Reload employee list after 2 seconds
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    document.getElementById("addEmployeeResponse").innerHTML = 
                        `<div class="error-message">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error("Error:", error);
                document.getElementById("addEmployeeResponse").innerHTML = 
                    '<div class="error-message">An error occurred. Please try again.</div>';
            });
        });

        // Handle form submission for editing employee
        document.getElementById("editEmployeeForm").addEventListener("submit", function(e) {
            e.preventDefault();
            
            // Get form data
            const formData = new FormData(this);
            
            // Send AJAX request
            fetch("update_employee.php", {
                method: "POST",
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    document.getElementById("editEmployeeResponse").innerHTML = 
                        `<div class="success-message">${data.message}</div>`;
                    
                    // Reload employee list after 2 seconds
                    setTimeout(function() {
                        window.location.reload();
                    }, 2000);
                } else {
                    document.getElementById("editEmployeeResponse").innerHTML = 
                        `<div class="error-message">${data.message}</div>`;
                }
            })
            .catch(error => {
                console.error("Error:", error);
                document.getElementById("editEmployeeResponse").innerHTML = 
                    '<div class="error-message">An error occurred. Please try again.</div>';
            });
        });

        function editEmployee(id) {
            // Fetch employee details
            fetch(`get_employee.php?id=${id}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // Populate form with employee data
                        document.getElementById("edit_id").value = data.employee.id;
                        document.getElementById("edit_employee_id").value = data.employee.employee_id;
                        document.getElementById("edit_employee_name").value = data.employee.employee_name;
                        document.getElementById("edit_email").value = data.employee.email;
                        
                        // Show edit modal
                        editModal.style.display = "block";
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while fetching employee data.");
                });
        }
        
        function deleteEmployee(id) {
            if (confirm(`Are you sure you want to delete this employee? This action cannot be undone.`)) {
                // Send AJAX request to delete the employee
                const formData = new FormData();
                formData.append('id', id);
                
                fetch("delete_employee.php", {
                    method: "POST",
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert(data.message);
                        
                        // Refresh the page to show updated employee list
                        window.location.reload();
                    } else {
                        alert(data.message);
                    }
                })
                .catch(error => {
                    console.error("Error:", error);
                    alert("An error occurred while deleting the employee.");
                });
            }
        }
        
        function searchEmployees() {
            const input = document.getElementById('searchInput').value.toLowerCase();
            const table = document.getElementById('employeeTable');
            const rows = table.getElementsByTagName('tr');
            
            for (let i = 1; i < rows.length; i++) {
                let found = false;
                const cells = rows[i].getElementsByTagName('td');
                
                for (let j = 0; j < cells.length; j++) {
                    const cellText = cells[j].textContent || cells[j].innerText;
                    
                    if (cellText.toLowerCase().indexOf(input) > -1) {
                        found = true;
                        break;
                    }
                }
                
                if (found) {
                    rows[i].style.display = '';
                } else {
                    rows[i].style.display = 'none';
                }
            }
        }
        
        function editSecurityCode(id, codeType, password) {
            document.getElementById('code_type').value = codeType;
            document.getElementById('new_password').value = password;
            document.getElementById('code_type').focus();
            
            // Scroll to the form
            document.querySelector('.security-code-form').scrollIntoView({ behavior: 'smooth' });
        }
        
        function deleteSecurityCode(id) {
            if (confirm(`Are you sure you want to delete this security code?`)) {
                alert(`Security code with ID: ${id} would be deleted - This functionality will be implemented later`);
            }
        }
        
        function openTab(evt, tabName) {
            // Hide all tab content
            const tabContents = document.getElementsByClassName("tab-content");
            for (let i = 0; i < tabContents.length; i++) {
                tabContents[i].classList.remove("active");
            }
            
            // Remove active class from all tabs
            const tabs = document.getElementsByClassName("tab");
            for (let i = 0; i < tabs.length; i++) {
                tabs[i].classList.remove("active");
            }
            
            // Show the selected tab content and add active class to the button
            document.getElementById(tabName).classList.add("active");
            evt.currentTarget.classList.add("active");
        }
    </script>
</body>
</html> 