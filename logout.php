<?php
session_start();

// Clear specific session variables if they exist
if (isset($_SESSION['login_redirect'])) {
    unset($_SESSION['login_redirect']);
}
if (isset($_SESSION['first_login'])) {
    unset($_SESSION['first_login']);
}

// Destroy the session
session_destroy();
header("Location: login.php");
?>
