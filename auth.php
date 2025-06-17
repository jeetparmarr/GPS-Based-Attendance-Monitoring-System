<?php
include 'config.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = $_POST['email'];
    $password = $_POST['password'];

    $stmt = $conn->prepare("SELECT * FROM employees WHERE email=? AND otp_verified=1");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        session_start();
        $_SESSION['email'] = $email;
        header("Location: store.php");
        exit; 
    } else {
        echo "<div style='background-color: #ffe6e6; border: 1px solid #ffcccc; padding: 15px; margin: 20px auto; width: 320px; text-align: center; border-radius: 8px;'>Invalid Login or OTP not verified!</div>";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>Login</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #f4f4f4;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            margin: 0;
        }

        .container {
            background-color: #ffffff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            width: 350px;
            text-align: center;
        }

        h2 {
            color: #333;
            margin-bottom: 30px;
            font-size: 28px;
            font-weight: 600;
        }

        input[type="email"], input[type="password"] {
            width: 100%;
            padding: 14px;
            margin-bottom: 25px;
            border: 1px solid #ccc;
            border-radius: 8px;
            box-sizing: border-box;
            font-size: 17px;
            transition: border-color 0.3s;
        }

        input[type="email"]:focus, input[type="password"]:focus {
            border-color: #008CBA;
            outline: none;
        }

        button[type="submit"] {
            background-color: #008CBA;
            color: white;
            padding: 16px 22px;
            border: none;
            border-radius: 10px;
            cursor: pointer;
            width: 100%;
            font-size: 18px;
            box-shadow: 0 3px 8px rgba(0, 0, 0, 0.2);
            transition: background-color 0.3s;
        }

        button[type="submit"]:hover {
            background-color: #0077A3;
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Login</h2>
        <form action="" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
    </div>
</body>
</html>