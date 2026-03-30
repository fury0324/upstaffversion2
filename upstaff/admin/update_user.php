<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid input']);
    exit();
}

$user_id = intval($input['user_id'] ?? 0);
$firstname = trim($input['firstname'] ?? '');
$lastname  = trim($input['lastname'] ?? '');
$email     = trim($input['email'] ?? '');
$phone     = trim($input['phone'] ?? '');
$address   = trim($input['address'] ?? '');
$dob       = trim($input['dob'] ?? '');
$position  = trim($input['position'] ?? '');
$role      = trim($input['role'] ?? 'employee');
$status    = trim($input['status'] ?? 'approved');

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}
if (empty($firstname) || empty($lastname) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
    exit();
}

// Check email uniqueness
$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check->bind_param("si", $email, $user_id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already in use by another user']);
    exit();
}

$update = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, phone = ?, address = ?, dob = ?, position = ?, role = ?, status = ? WHERE id = ?");
$update->bind_param("sssssssssi", $firstname, $lastname, $email, $phone, $address, $dob, $position, $role, $status, $user_id);
if ($update->execute()) {
    echo json_encode(['success' => true, 'message' => 'User updated successfully']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $conn->error]);
}
?>