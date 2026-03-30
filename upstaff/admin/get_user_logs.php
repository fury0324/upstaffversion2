<?php
session_start();
require_once __DIR__ . '/../config/db.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : 0;
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid user ID']);
    exit();
}

$logs = [];
// Fetch from employee_logs (change table name if needed)
$result = $conn->query("SELECT action, timestamp, ip_address FROM employee_logs WHERE user_id = $user_id ORDER BY timestamp DESC LIMIT 50");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $logs[] = [
            'action' => $row['action'],
            'timestamp' => date('M d, Y H:i:s', strtotime($row['timestamp'])),
            'ip' => $row['ip_address'] ?? ''
        ];
    }
}

echo json_encode(['success' => true, 'logs' => $logs]);
?>