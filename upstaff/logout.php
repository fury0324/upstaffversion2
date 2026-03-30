<?php
session_start();

// Include database connection
require_once __DIR__ . '/config/db.php';

// Log the logout event if user is logged in
if (isset($_SESSION['user_id'])) {
    $user_id = $_SESSION['user_id'];
    $ip = $_SERVER['REMOTE_ADDR'];
    $conn->query("INSERT INTO employee_logs (user_id, action, ip_address) VALUES ($user_id, 'Logout', '$ip')");
}

// Destroy session
$_SESSION = [];
session_destroy();

// Redirect to login page
header("Location: login/login.php");
exit();
?>