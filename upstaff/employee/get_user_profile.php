<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Accept both 'employee' and 'user' roles
if (!isset($_SESSION['user_id']) || !in_array($_SESSION['role'], ['employee', 'user'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT firstname, lastname, email, position, phone, address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

echo json_encode(['success' => true, 'user' => $user]);
?>