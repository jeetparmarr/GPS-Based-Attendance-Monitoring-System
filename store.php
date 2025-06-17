<?php
session_start();
include "config.php";

if (!isset($_SESSION['email'])) {
    header("Location: login.php");
    exit;
}

$email = $_SESSION['email'];
$stmt = $conn->prepare("SELECT * FROM employees WHERE email = ?");
$stmt->bind_param("s", $email);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $employee_data = $result->fetch_assoc();
    $employee_id = $employee_data['employee_id'];
    $employee_name = $employee_data['employee_name'];
} else {
    echo "Employee data not found.";
    $employee_id = "Unknown";
    $employee_name = "Unknown";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Office Location Checker</title>
    <link rel="stylesheet" href="responsive.css">
    <script src="mobile_optimizations.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f0f2f5;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            margin: 0;
            padding: 10px;
        }

        .container {
            display: flex;
            flex-direction: column;
            width: 90%;
            max-width: 800px;
            background-color: white;
            border-radius: 15px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
            padding: 30px;
            box-sizing: border-box;
            margin: 20px auto;
        }

        h2 {
            text-align: center;
            margin-bottom: 20px;
            font-size: 1.8em;
            color: #333;
        }

        p {
            text-align: center;
            margin-bottom: 15px;
            word-wrap: break-word;
        }

        button {
            background-color: #0072ff;
            color: white;
            padding: 14px 20px;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            width: 100%;
            max-width: 200px;
            font-size: 16px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
            margin: 10px auto;
            display: block;
            -webkit-tap-highlight-color: transparent;
            touch-action: manipulation;
            position: relative;
            overflow: hidden;
        }

        button:hover, button:active {
            background-color: #0056b3;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.25);
        }

        button:active {
            transform: translateY(0);
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.2);
        }

        button:disabled {
            background-color: #cccccc;
            cursor: not-allowed;
            transform: none;
            box-shadow: none;
        }

        /* Add a subtle gradient effect */
        button::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(
                120deg,
                transparent,
                rgba(255, 255, 255, 0.2),
                transparent
            );
            transition: 0.5s;
        }

        button:hover::before {
            left: 100%;
        }

        #address {
            text-align: center;
            margin: 15px 0;
            padding: 10px;
            background-color: #f5f5f5;
            border-radius: 8px;
            word-break: break-word;
        }

        /* Map Container */
        #mapContainer {
            width: 100%;
            height: 300px;
            margin: 20px 0;
            border-radius: 10px;
            box-shadow: 0 3px 10px rgba(0, 0, 0, 0.2);
            overflow: hidden;
        }
        
        #locationStatus {
            text-align: center;
            padding: 10px;
            margin: 10px 0;
            border-radius: 8px;
            background-color: #e8f5e9;
            word-break: break-word;
        }
        
        /* Back button */
        .back-button {
            position: absolute;
            top: 20px;
            left: 20px;
            display: flex;
            align-items: center;
            text-decoration: none;
            color: #333;
            font-weight: 500;
            transition: color 0.3s;
        }
        
        .back-button:hover {
            color: #0072ff;
        }
        
        .back-button span {
            margin-right: 5px;
            font-size: 1.5em;
        }
        
        /* Responsive adjustments */
        @media (max-width: 768px) {
            .container {
                width: 95%;
                padding: 20px;
            }
            
            h2 {
                font-size: 1.5em;
            }
            
            #mapContainer {
                height: 250px;
            }
            
            button {
                padding: 12px 15px;
            }
            
            .back-button {
                position: static;
                margin-bottom: 15px;
                justify-content: center;
            }
        }
        
        @media (max-width: 480px) {
            .container {
                padding: 15px;
            }
            
            #mapContainer {
                height: 200px;
            }
        }

        .location-warning {
            background-color: #fff3cd;
            color: #856404;
            padding: 12px;
            margin: 15px 0;
            border-radius: 8px;
            border-left: 5px solid #ffeeba;
            font-weight: 500;
            text-align: center;
            animation: pulse 2s infinite;
        }
        
        @keyframes pulse {
            0% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0.4);
            }
            70% {
                box-shadow: 0 0 0 10px rgba(255, 193, 7, 0);
            }
            100% {
                box-shadow: 0 0 0 0 rgba(255, 193, 7, 0);
            }
        }
    </style>

    <script>
        let isInsideOffice = false;

        function getLocation() {
            document.getElementById("locationStatus").innerHTML = "Detecting your location...";
            
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        let latitude = position.coords.latitude;
                        let longitude = position.coords.longitude;
                        document.getElementById("locationStatus").innerHTML = `Coordinates: Latitude: ${latitude}, Longitude: ${longitude}`;
                        checkLocation(latitude, longitude);
                        showMap(latitude, longitude);
                    },
                    function (error) {
                        let errorMessage = "Location access denied! Please enable GPS.";
                        
                        switch(error.code) {
                            case error.PERMISSION_DENIED:
                                errorMessage = "Location permission denied. Please enable GPS access.";
                                break;
                            case error.POSITION_UNAVAILABLE:
                                errorMessage = "Location information is unavailable. Check your device settings.";
                                break;
                            case error.TIMEOUT:
                                errorMessage = "Location request timed out. Please try again.";
                                break;
                        }
                        
                        document.getElementById("locationStatus").innerHTML = `❌ Error: ${errorMessage}`;
                        alert(errorMessage);
                    },
                    { timeout: 10000, enableHighAccuracy: true }
                );
            } else {
                document.getElementById("locationStatus").innerHTML = "❌ Geolocation is not supported by this browser.";
                alert("Geolocation is not supported by this browser.");
            }
        }

        function checkLocation(lat, lon) {
            fetch(`check_location.php?lat=${lat}&lon=${lon}`)
                .then(response => response.json())
                .then(data => {
                    document.getElementById("locationStatus").innerHTML = data.message;
                    document.getElementById("address").innerHTML = "<strong>📍 Address:</strong> " + data.address;
                    
                    // Check if inside office based on the response
                    isInsideOffice = data.inside_office || data.message.includes("✅");

                    // Show buttons and enable/disable based on location
                    document.getElementById("checkInBtn").style.display = "block";
                    document.getElementById("checkOutBtn").style.display = "block";
                    
                    if (isInsideOffice) {
                        // Inside office - enable both buttons
                        document.getElementById("checkInBtn").disabled = false;
                        document.getElementById("checkOutBtn").disabled = false;
                        
                        // Remove any warning messages
                        if (document.getElementById("locationWarning")) {
                            document.getElementById("locationWarning").remove();
                        }
                    } else {
                        // Outside office - disable both buttons
                        document.getElementById("checkInBtn").disabled = true;
                        document.getElementById("checkOutBtn").disabled = true;
                        
                        // Add warning message
                        if (!document.getElementById("locationWarning")) {
                            const warningDiv = document.createElement("div");
                            warningDiv.id = "locationWarning";
                            warningDiv.className = "location-warning";
                            warningDiv.innerHTML = "⚠️ You are outside the office radius. Check-in and check-out are only available inside the office.";
                            document.getElementById("locationStatus").after(warningDiv);
                        }
                    }
                })
                .catch(error => {
                    document.getElementById("locationStatus").innerHTML = "❌ Error fetching location data.";
                    console.error("Error:", error);
                });
        }

        function showMap(lat, lon) {
            let googleMapsURL = `https://www.google.com/maps?q=${lat},${lon}&output=embed`;
            document.getElementById("mapContainer").innerHTML = `<iframe width="100%" height="100%" style="border:0" src="${googleMapsURL}" allowfullscreen></iframe>`;
        }

        function checkIn() {
            if (!isInsideOffice) {
                alert("You must be inside the office radius to check in!");
                return;
            }
            
            document.getElementById("checkInBtn").disabled = true;
            document.getElementById("checkInBtn").textContent = "Processing...";
            
            // Get coordinates from the current position
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        const address = document.getElementById("address").innerText;
                        
                        // Use check_in.php for check-in (renamed from inside_office)
                        fetch('check_in.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `employee_id=<?php echo $employee_id; ?>&employee_name=<?php echo urlencode($employee_name); ?>&email=<?php echo urlencode($email); ?>&latitude=${latitude}&longitude=${longitude}&address=${encodeURIComponent(address)}&location=inside`
                        })
                        .then(response => response.text())
                        .then(data => {
                            alert(data);
                            document.getElementById("checkInBtn").disabled = false;
                            document.getElementById("checkInBtn").textContent = "Check In";
                        })
                        .catch(error => {
                            alert("Error during check-in.");
                            document.getElementById("checkInBtn").disabled = false;
                            document.getElementById("checkInBtn").textContent = "Check In";
                        });
                    },
                    function (error) {
                        alert("Error getting current location: " + error.message);
                        document.getElementById("checkInBtn").disabled = false;
                        document.getElementById("checkInBtn").textContent = "Check In";
                    },
                    { timeout: 10000, enableHighAccuracy: true }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
                document.getElementById("checkInBtn").disabled = false;
                document.getElementById("checkInBtn").textContent = "Check In";
            }
        }

        function checkOut() {
            if (!isInsideOffice) {
                alert("You must be inside the office radius to check out!");
                return;
            }
            
            document.getElementById("checkOutBtn").disabled = true;
            document.getElementById("checkOutBtn").textContent = "Processing...";
            
            // Get coordinates from the current position
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(
                    function (position) {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;
                        const address = document.getElementById("address").innerText;
                        
                        // Use check_out.php for check-out (renamed from check_outs)
                        fetch('check_out.php', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/x-www-form-urlencoded',
                            },
                            body: `employee_id=<?php echo $employee_id; ?>&employee_name=<?php echo urlencode($employee_name); ?>&email=<?php echo urlencode($email); ?>&latitude=${latitude}&longitude=${longitude}&address=${encodeURIComponent(address)}&location=inside`
                        })
                        .then(response => response.text())
                        .then(data => {
                            alert(data);
                            document.getElementById("checkOutBtn").disabled = false;
                            document.getElementById("checkOutBtn").textContent = "Check Out";
                        })
                        .catch(error => {
                            alert("Error during check-out.");
                            document.getElementById("checkOutBtn").disabled = false;
                            document.getElementById("checkOutBtn").textContent = "Check Out";
                        });
                    },
                    function (error) {
                        alert("Error getting current location: " + error.message);
                        document.getElementById("checkOutBtn").disabled = false;
                        document.getElementById("checkOutBtn").textContent = "Check Out";
                    },
                    { timeout: 10000, enableHighAccuracy: true }
                );
            } else {
                alert("Geolocation is not supported by this browser.");
                document.getElementById("checkOutBtn").disabled = false;
                document.getElementById("checkOutBtn").textContent = "Check Out";
            }
        }
    </script>
</head>
<body onload="getLocation()">
    <a href="dashboard.php" class="back-button"><span>←</span> Back to Dashboard</a>
    <div class="container">
        <h2>📌 Office Location Checker</h2>
        <p><strong>Employee ID:</strong> <?php echo $employee_id; ?></p>
        <p><strong>Employee Name:</strong> <?php echo $employee_name; ?></p>
        <div id="locationStatus">Initializing location services...</div>
        <div id="address"><strong>📍 Address:</strong> Waiting for location...</div>
        <div id="mapContainer"></div>
        <button id="checkInBtn" style="display: none;" onclick="checkIn()">Check In</button>
        <button id="checkOutBtn" style="display: none;" onclick="checkOut()">Check Out</button>
    </div>
</body>
</html>
