<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}

// Get user ID from URL
$user_id = isset($_GET['id']) ? intval($_GET['id']) : 0;
if ($user_id <= 0) {
    header("Location: approved_users.php?error=invalid_id");
    exit();
}

// Fetch current user data (employee_id removed)
$stmt = $conn->prepare("SELECT id, firstname, lastname, username, email, phone, address, dob, position, role, status FROM users WHERE id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

if (!$user) {
    header("Location: approved_users.php?error=user_not_found");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $firstname = trim($_POST['firstname'] ?? '');
    $lastname  = trim($_POST['lastname'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $phone     = trim($_POST['phone'] ?? '');
    $address   = trim($_POST['address'] ?? '');
    $dob       = trim($_POST['dob'] ?? '');
    $position  = trim($_POST['position'] ?? '');
    $role      = trim($_POST['role'] ?? 'employee');
    $status    = trim($_POST['status'] ?? 'approved');

    // Validate required fields
    if (empty($firstname) || empty($lastname) || empty($email)) {
        $error = "First name, last name, and email are required.";
    } else {
        // Update user (employee_id removed)
        $update = $conn->prepare("UPDATE users SET firstname = ?, lastname = ?, email = ?, phone = ?, address = ?, dob = ?, position = ?, role = ?, status = ? WHERE id = ?");
        $update->bind_param("sssssssssi", $firstname, $lastname, $email, $phone, $address, $dob, $position, $role, $status, $user_id);
        if ($update->execute()) {
            header("Location: approved_users.php?success=user_updated");
            exit();
        } else {
            $error = "Database error: " . $conn->error;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - UpStaff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <style>
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
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body class="bg-gray-100">
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content" id="mainContent">
        <div class="max-w-2xl mx-auto bg-white rounded-xl shadow-lg p-6 mt-6">
            <div class="flex items-center justify-between mb-6">
                <h1 class="text-2xl font-bold text-gray-800">Edit User</h1>
                <a href="approved_users.php" class="text-gray-500 hover:text-gray-700">
                    <i class="fas fa-times"></i>
                </a>
            </div>

            <?php if (isset($error)): ?>
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>

            <form method="POST" action="">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                        <input type="text" name="firstname" value="<?php echo htmlspecialchars($user['firstname']); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                        <input type="text" name="lastname" value="<?php echo htmlspecialchars($user['lastname']); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                        <input type="email" name="email" value="<?php echo htmlspecialchars($user['email']); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                        <input type="text" name="phone" value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                        <input type="date" name="dob" value="<?php echo htmlspecialchars($user['dob'] ?? ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                        <input type="text" name="position" value="<?php echo htmlspecialchars($user['position'] ?? ''); ?>" 
                               class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                        <select name="role" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="employee" <?php echo ($user['role'] === 'employee') ? 'selected' : ''; ?>>Employee</option>
                            <option value="admin" <?php echo ($user['role'] === 'admin') ? 'selected' : ''; ?>>Admin</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                        <select name="status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                            <option value="approved" <?php echo ($user['status'] === 'approved') ? 'selected' : ''; ?>>Approved</option>
                            <option value="pending" <?php echo ($user['status'] === 'pending') ? 'selected' : ''; ?>>Pending</option>
                            <option value="rejected" <?php echo ($user['status'] === 'rejected') ? 'selected' : ''; ?>>Rejected</option>
                        </select>
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                        <textarea name="address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a href="approved_users.php" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">Cancel</a>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">Save Changes</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        const mainContent = document.getElementById('mainContent');
        window.addEventListener('sidebarToggle', function() {
            const sidebar = document.getElementById('sidebar');
            if (sidebar && sidebar.classList.contains('w-64')) {
                mainContent.classList.remove('sidebar-collapsed');
            } else {
                mainContent.classList.add('sidebar-collapsed');
            }
        });
        const sidebar = document.getElementById('sidebar');
        if (sidebar && !sidebar.classList.contains('w-64')) {
            mainContent.classList.add('sidebar-collapsed');
        }
    </script>
</body>
</html>