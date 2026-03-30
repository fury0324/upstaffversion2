<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Accept both 'employee' and 'user' roles (for old sessions)
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['employee', 'user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$user_id = $_SESSION['user_id'];
$current = $input['current_password'] ?? '';
$new     = $input['new_password'] ?? '';
$confirm = $input['confirm_password'] ?? '';

if (empty($current) || empty($new) || empty($confirm)) {
    echo json_encode(['success' => false, 'message' => 'All fields are required']);
    exit();
}
if ($new !== $confirm) {
    echo json_encode(['success' => false, 'message' => 'New passwords do not match']);
    exit();
}
if (strlen($new) < 6) {
    echo json_encode(['success' => false, 'message' => 'Password must be at least 6 characters']);
    exit();
}

$stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$row = $result->fetch_assoc();

if (!password_verify($current, $row['password'])) {
    echo json_encode(['success' => false, 'message' => 'Current password is incorrect']);
    exit();
}

$new_hash = password_hash($new, PASSWORD_DEFAULT);
$update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
$update->bind_param("si", $new_hash, $user_id);
if ($update->execute()) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $details = json_encode(['changed' => 'password']);
    $log_stmt = $conn->prepare("INSERT INTO employee_logs (user_id, action, details, ip_address) VALUES (?, 'Password changed', ?, ?)");
    $log_stmt->bind_param("iss", $user_id, $details, $ip);
    $log_stmt->execute();
    echo json_encode(['success' => true, 'message' => 'Password changed']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>