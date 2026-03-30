<?php
session_start();
require_once __DIR__ . '/../config/db.php';

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
$firstname = trim($input['firstname'] ?? '');
$lastname  = trim($input['lastname'] ?? '');
$email     = trim($input['email'] ?? '');
$position  = trim($input['position'] ?? '');
$phone     = trim($input['phone'] ?? '');
$address   = trim($input['address'] ?? '');

if (empty($firstname) || empty($lastname) || empty($email)) {
    echo json_encode(['success' => false, 'message' => 'First name, last name, and email are required']);
    exit();
}

// Fetch current data for comparison
$stmt = $conn->prepare("SELECT firstname, lastname, email, position, phone, address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$old = $stmt->get_result()->fetch_assoc();

// Check email uniqueness
$check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
$check->bind_param("si", $email, $user_id);
$check->execute();
$check->store_result();
if ($check->num_rows > 0) {
    echo json_encode(['success' => false, 'message' => 'Email already in use by another account']);
    exit();
}

$update = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, position = ?, phone = ?, address = ? WHERE id = ?");
$update->bind_param("ssssssi", $firstname, $lastname, $email, $position, $phone, $address, $user_id);
if ($update->execute()) {
    $_SESSION['firstname'] = $firstname;

    // Build change details
    $changes = [];
    $fields = ['firstname', 'lastname', 'email', 'position', 'phone', 'address'];
    foreach ($fields as $field) {
        if ($old[$field] !== $$field) {
            $changes[$field] = ['old' => $old[$field], 'new' => $$field];
        }
    }

    if (!empty($changes)) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $details = json_encode($changes, JSON_UNESCAPED_UNICODE);
        $log_stmt = $conn->prepare("INSERT INTO employee_logs (user_id, action, details, ip_address) VALUES (?, 'Profile updated', ?, ?)");
        $log_stmt->bind_param("iss", $user_id, $details, $ip);
        $log_stmt->execute();
    }

    echo json_encode(['success' => true, 'message' => 'Profile updated']);
} else {
    echo json_encode(['success' => false, 'message' => 'Database error']);
}
?>