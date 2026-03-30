<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer
require_once __DIR__ . '/PHP-Mailer-master/src/PHPMailer.php';
require_once __DIR__ . '/PHP-Mailer-master/src/SMTP.php';
require_once __DIR__ . '/PHP-Mailer-master/src/Exception.php';

// Check if admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Unauthorized";
    exit();
}

// IMPORTANT: Must be POST
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo "Invalid request method";
    exit();
}

// Check ID
if (!isset($_POST['id'])) {
    echo "No user ID provided";
    exit();
}

$id = intval($_POST['id']);

// Get user info
$stmt = $conn->prepare("SELECT * FROM users WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    echo "User not found";
    exit();
}

// ❗ CHANGE HERE: set to REJECTED
$update = $conn->prepare("UPDATE users SET status = 'rejected' WHERE id = ?");
$update->bind_param("i", $id);
$update->execute();


// ========== SEND EMAIL ==========
$mail = new PHPMailer(true);

try {
    $mail->isSMTP();
    $mail->Host = 'smtp.gmail.com';
    $mail->SMTPAuth = true;
    $mail->Username = 'upstaff7@gmail.com';
    $mail->Password = 'adthzbjsnqjfhjky'; // app password
    $mail->SMTPSecure = 'tls';
    $mail->Port = 587;

    // Fix for localhost
    $mail->SMTPOptions = [
        'ssl' => [
            'verify_peer' => false,
            'verify_peer_name' => false,
            'allow_self_signed' => true
        ]
    ];

    $mail->setFrom('upstaff7@gmail.com', 'Upstaff Zamboanga');
    $mail->addAddress($user['email']);

    $mail->isHTML(true);
    $mail->Subject = 'Your Upstaff Account Has Been Rejected';
    $mail->Body = "
        <h3>Hello {$user['firstname']},</h3>
        <p>We regret to inform you that your account request has been <strong>rejected</strong>.</p>
        <p>If you believe this is a mistake, please contact the administrator.</p>
        <br>
        <p>Thank you,<br>Upstaff Team</p>
    ";

    $mail->send();
    echo "success";

} catch (Exception $e) {
    error_log("Mailer Error: " . $mail->ErrorInfo);
    echo "rejected_no_email";
}
?>