<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication check
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../login/login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$message = "";
$messageType = "";

// Fetch current user data
$stmt = $conn->prepare("SELECT firstname, lastname, email, phone, address FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_profile'])) {
        $firstname = trim($_POST['firstname'] ?? '');
        $lastname  = trim($_POST['lastname'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $phone     = trim($_POST['phone'] ?? '');
        $address   = trim($_POST['address'] ?? '');

        if (empty($firstname) || empty($lastname) || empty($email)) {
            $message = "First name, last name, and email are required.";
            $messageType = "error";
        } else {
            // Check if email already in use by another user
            $check = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $check->bind_param("si", $email, $user_id);
            $check->execute();
            $check->store_result();
            if ($check->num_rows > 0) {
                $message = "Email already in use by another account.";
                $messageType = "error";
            } else {
                $update = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, phone = ?, address = ? WHERE id = ?");
                $update->bind_param("sssssi", $firstname, $lastname, $email, $phone, $address, $user_id);
                if ($update->execute()) {
                    $_SESSION['firstname'] = $firstname;
                    $message = "Profile updated successfully!";
                    $messageType = "success";
                    // Refresh user data
                    $user['firstname'] = $firstname;
                    $user['lastname']  = $lastname;
                    $user['email']     = $email;
                    $user['phone']     = $phone;
                    $user['address']   = $address;
                } else {
                    $message = "Database error. Please try again.";
                    $messageType = "error";
                }
            }
        }
    }

    // Handle password change
    elseif (isset($_POST['change_password'])) {
        $current_password = $_POST['current_password'] ?? '';
        $new_password     = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
            $message = "All password fields are required.";
            $messageType = "error";
        } elseif ($new_password !== $confirm_password) {
            $message = "New password and confirmation do not match.";
            $messageType = "error";
        } elseif (strlen($new_password) < 6) {
            $message = "Password must be at least 6 characters.";
            $messageType = "error";
        } else {
            // Verify current password
            $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->bind_param("i", $user_id);
            $stmt->execute();
            $result = $stmt->get_result();
            $row = $result->fetch_assoc();
            if (password_verify($current_password, $row['password'])) {
                $new_hashed = password_hash($new_password, PASSWORD_DEFAULT);
                $update = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
                $update->bind_param("si", $new_hashed, $user_id);
                if ($update->execute()) {
                    $message = "Password changed successfully!";
                    $messageType = "success";
                } else {
                    $message = "Database error. Please try again.";
                    $messageType = "error";
                }
            } else {
                $message = "Current password is incorrect.";
                $messageType = "error";
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Account Settings - UpStaff Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        * { font-family: 'Inter', sans-serif; }
        .main-content {
            margin-left: 16rem;
            width: calc(100% - 16rem);
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .main-content.sidebar-collapsed {
            margin-left: 5rem;
            width: calc(100% - 5rem);
        }
        @media (max-width: 768px) {
            .main-content { margin-left: 0 !important; width: 100% !important; }
        }
        .setting-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        }
        .setting-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -12px rgba(0,0,0,0.15);
        }
        .avatar-initials {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .form-input:focus {
            box-shadow: 0 0 0 3px rgba(59,130,246,0.1);
        }
        @keyframes slideDown {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .message-animation {
            animation: slideDown 0.3s ease-out;
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-gray-100">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-content" id="mainContent">
        <?php include __DIR__ . '/header.php'; ?>
        <div class="p-6 md:p-8">
            <div class="max-w-6xl mx-auto">
                <!-- Page Header with Breadcrumb -->
                <div class="mb-8">
                    <div class="flex items-center text-sm text-gray-500 mb-2">
                        <a href="dashboard.php" class="hover:text-blue-600 transition">Dashboard</a>
                        <i class="fas fa-chevron-right mx-2 text-xs"></i>
                        <span class="text-gray-700">Account Settings</span>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-800">Account Settings</h1>
                    <p class="text-gray-500 mt-1">Manage your profile and security preferences</p>
                </div>

                <!-- Success/Error Message -->
                <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-xl shadow-sm <?php echo $messageType === 'success' ? 'bg-green-50 border border-green-200 text-green-700' : 'bg-red-50 border border-red-200 text-red-700'; ?> message-animation">
                        <div class="flex items-center gap-3">
                            <i class="fas <?php echo $messageType === 'success' ? 'fa-check-circle' : 'fa-exclamation-circle'; ?> text-xl"></i>
                            <span><?php echo htmlspecialchars($message); ?></span>
                        </div>
                    </div>
                <?php endif; ?>

                <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
                    <!-- Left Side: Profile Summary Card -->
                    <div class="lg:col-span-1">
                        <div class="bg-white rounded-2xl shadow-lg overflow-hidden sticky top-24">
                            <div class="avatar-initials h-32 flex items-center justify-center">
                                <div class="w-24 h-24 bg-white rounded-full flex items-center justify-center shadow-lg">
                                    <span class="text-4xl font-bold text-gray-700">
                                        <?php echo strtoupper(substr($user['firstname'], 0, 1) . substr($user['lastname'], 0, 1)); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="p-6 text-center">
                                <h3 class="text-xl font-bold text-gray-800"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></h3>
                                <p class="text-sm text-gray-500 mt-1"><?php echo htmlspecialchars($user['email']); ?></p>
                                <div class="mt-4 pt-4 border-t border-gray-100">
                                    <div class="flex items-center justify-between text-sm">
                                        <span class="text-gray-500">Member since</span>
                                        <span class="font-medium text-gray-700"><?php echo date('M d, Y'); ?></span>
                                    </div>
                                    <div class="flex items-center justify-between text-sm mt-2">
                                        <span class="text-gray-500">Role</span>
                                        <span class="font-medium text-gray-700">Employee</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Side: Settings Forms (2 columns) -->
                    <div class="lg:col-span-2">
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                            <!-- Profile Information Card -->
                            <div class="bg-white rounded-2xl shadow-lg overflow-hidden setting-card">
                                <div class="bg-gradient-to-r from-blue-500 to-blue-600 px-6 py-4">
                                    <h2 class="text-white text-lg font-semibold flex items-center gap-2">
                                        <i class="fas fa-user-edit"></i> Profile Information
                                    </h2>
                                </div>
                                <div class="p-6">
                                    <form method="POST" action="">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                                <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" 
                                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all form-input">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                                <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" 
                                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all form-input">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Email Address *</label>
                                                <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all form-input">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone Number</label>
                                                <input type="tel" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all form-input">
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                                <textarea name="address" rows="2" class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition-all form-input"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                                            </div>
                                            <button type="submit" name="update_profile" class="w-full bg-blue-600 hover:bg-blue-700 text-white font-semibold py-2.5 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg">
                                                <i class="fas fa-save"></i> Save Changes
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>

                            <!-- Change Password Card -->
                            <div class="bg-white rounded-2xl shadow-lg overflow-hidden setting-card">
                                <div class="bg-gradient-to-r from-purple-500 to-purple-600 px-6 py-4">
                                    <h2 class="text-white text-lg font-semibold flex items-center gap-2">
                                        <i class="fas fa-key"></i> Security
                                    </h2>
                                </div>
                                <div class="p-6">
                                    <form method="POST" action="">
                                        <div class="space-y-4">
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                                <input type="password" name="current_password" 
                                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all form-input"
                                                       required>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                                <input type="password" name="new_password" 
                                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all form-input"
                                                       required>
                                                <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                                            </div>
                                            <div>
                                                <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                                <input type="password" name="confirm_password" 
                                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl focus:ring-2 focus:ring-purple-500 focus:border-purple-500 transition-all form-input"
                                                       required>
                                            </div>
                                            <button type="submit" name="change_password" class="w-full bg-purple-600 hover:bg-purple-700 text-white font-semibold py-2.5 rounded-xl transition duration-200 flex items-center justify-center gap-2 shadow-md hover:shadow-lg">
                                                <i class="fas fa-lock"></i> Update Password
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- Security Tips Card -->
                        <div class="mt-6 bg-gradient-to-r from-blue-50 to-indigo-50 rounded-2xl p-5 border border-blue-100 shadow-sm">
                            <div class="flex items-start gap-4">
                                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                    <i class="fas fa-shield-alt text-blue-600 text-lg"></i>
                                </div>
                                <div>
                                    <h3 class="font-semibold text-gray-800">Security Recommendations</h3>
                                    <ul class="mt-2 text-sm text-gray-600 space-y-1 list-disc list-inside">
                                        <li>Use a unique password you haven't used elsewhere.</li>
                                        <li>Enable two‑factor authentication if available.</li>
                                        <li>Never share your credentials.</li>
                                        <li>Keep your email address current for account recovery.</li>
                                    </ul>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        const mainContent = document.getElementById("mainContent");
        window.addEventListener('sidebarToggle', function() {
            const sidebar = document.getElementById("sidebar");
            if (sidebar) {
                if (sidebar.classList.contains("w-64")) {
                    mainContent.classList.remove("sidebar-collapsed");
                } else {
                    mainContent.classList.add("sidebar-collapsed");
                }
            }
        });
        const sidebar = document.getElementById("sidebar");
        if (sidebar && !sidebar.classList.contains("w-64")) {
            mainContent.classList.add("sidebar-collapsed");
        }
    </script>
</body>
</html>