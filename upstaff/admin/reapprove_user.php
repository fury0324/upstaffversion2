<?php
// reapprove_user.php
session_start();
require_once __DIR__ . '/../config/db.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

// Include PHPMailer
require_once __DIR__ . '/../PHP-Mailer-master/src/PHPMailer.php';
require_once __DIR__ . '/../PHP-Mailer-master/src/SMTP.php';
require_once __DIR__ . '/../PHP-Mailer-master/src/Exception.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    echo "Unauthorized";
    exit();
}

if (isset($_POST['id'])) {
    $id = intval($_POST['id']);
    
    // Fetch user info first (to get email)
    $stmt = $conn->prepare("SELECT * FROM users WHERE id = ? AND status = 'rejected'");
    $stmt->bind_param("i", $id);
    $stmt->execute();
    $result = $stmt->get_result();
    $user = $result->fetch_assoc();
    $stmt->close();
    
    if (!$user) {
        echo "User not found or already pending";
        exit();
    }
    
    // Update user status from 'rejected' to 'pending'
    $update = $conn->prepare("UPDATE users SET status = 'pending' WHERE id = ?");
    $update->bind_param("i", $id);
    
    if ($update->execute()) {
        // Send email notification to user
        $mail = new PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = 'smtp.gmail.com';
            $mail->SMTPAuth = true;
            $mail->Username = 'upstaff7@gmail.com'; // admin email
            $mail->Password = 'adthzbjsnqjfhjky'; // app password
            $mail->SMTPSecure = 'tls';
            $mail->Port = 587;

            // Bypass SSL verification for localhost
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
            $mail->Subject = 'Your Upstaff Account is Pending Approval';
            $mail->Body = "
                <h3>Hello {$user['firstname']},</h3>
                <p>Your Upstaff account has been re-approved by the admin and is now <strong>pending</strong> approval.</p>
                <p>You will receive an email once your account is fully approved.</p>
                <p><a href='http://localhost/upstaff/login/login.php'>Login Here</a></p>
                <br>
                <p>Thank you,<br>Upstaff Team</p>
            ";

            $mail->send();
            echo "success";

        } catch (Exception $e) {
            error_log("PHPMailer Error: {$mail->ErrorInfo}");
            echo "pending_no_email"; // status updated but email failed
        }

    } else {
        echo "Database error";
    }

    $update->close();
} else {
    echo "No ID provided";
}

$conn->close();
?>