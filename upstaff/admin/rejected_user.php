<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in and is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}


// CHECK AND ADD MISSING COLUMNS AUTOMATICALLY
function addMissingColumns($conn) {
    $columns_to_check = [
        'lastname' => "ALTER TABLE users ADD COLUMN lastname VARCHAR(100) NULL AFTER firstname",
        'position' => "ALTER TABLE users ADD COLUMN position VARCHAR(100) NULL AFTER dob",
        'employee_id' => "ALTER TABLE users ADD COLUMN employee_id VARCHAR(50) NULL AFTER position"
    ];
    
    // Get existing columns
    $result = $conn->query("SHOW COLUMNS FROM users");
    $existing_columns = [];
    while ($row = $result->fetch_assoc()) {
        $existing_columns[] = $row['Field'];
    }
    
    // Add missing columns
    foreach ($columns_to_check as $column => $sql) {
        if (!in_array($column, $existing_columns)) {
            if ($conn->query($sql)) {
                error_log("Column '$column' added successfully to users table");
                
                // If lastname was added, try to split existing firstname values
                if ($column == 'lastname') {
                    $conn->query("
                        UPDATE users 
                        SET 
                            lastname = SUBSTRING_INDEX(firstname, ' ', -1),
                            firstname = SUBSTRING_INDEX(firstname, ' ', 1)
                        WHERE INSTR(firstname, ' ') > 0 AND lastname IS NULL
                    ");
                }
                
                // If employee_id was added, generate for existing users
                if ($column == 'employee_id') {
                    $conn->query("
                        UPDATE users 
                        SET employee_id = CONCAT('EMP-', LPAD(id, 4, '0')) 
                        WHERE employee_id IS NULL
                    ");
                }
                
                // If position was added, set default for existing users
                if ($column == 'position') {
                    $conn->query("
                        UPDATE users 
                        SET position = 'Staff' 
                        WHERE position IS NULL AND role = 'employee'
                    ");
                }
            } else {
                error_log("Failed to add column '$column': " . $conn->error);
            }
        }
    }
}

// Call the function to add missing columns
addMissingColumns($conn);


// FETCH REJECTED USERS ONLY
$stmt = $conn->prepare("SELECT id, firstname, lastname, username, email, phone, address, dob, position, role, employee_id, status, created_at FROM users WHERE status = 'rejected' ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();

$rejected_users = [];
while ($row = $result->fetch_assoc()) {
    $rejected_users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Rejected Users - Upstaff</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
<style>
    * {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    
    /* MAIN CONTENT OFFSET - This pushes content to the right of fixed sidebar */
    .main-content {
        margin-left: 16rem; /* Same as sidebar width (w-64) */
        width: calc(100% - 16rem);
        min-height: 100vh;
        transition: margin-left 0.3s ease, width 0.3s ease;
    }
    
    /* When sidebar is collapsed */
    .main-content.sidebar-collapsed {
        margin-left: 5rem; /* w-20 */
        width: calc(100% - 5rem);
    }
    
    /* Mobile responsive */
    @media (max-width: 768px) {
        .main-content {
            margin-left: 0 !important;
            width: 100% !important;
        }
    }
    
    @keyframes modalFadeIn {
        from {
            opacity: 0;
            transform: translateY(20px) scale(0.95);
        }
        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }
    
    .modal-animate-in {
        animation: modalFadeIn 0.3s ease-out forwards;
    }
</style>
</head>

<body class="bg-gray-100">
    <!-- SIDEBAR - Your sidebar included here (make sure it has fixed positioning) -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT - with margin-left offset -->
    <div class="main-content" id="mainContent">
        <!-- PAGE CONTENT -->
        <div class="p-6 overflow-auto">

            <h1 class="text-2xl font-bold mb-4">Rejected Users</h1>
            <span class="text-sm text-gray-500 mb-6 block">View all rejected user registrations</span>
            
            <!-- REJECTED USERS TABLE -->
            <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
                <!-- Table Header -->
                <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path>
                            </svg>
                            Rejected Users List
                        </h2>
                        <span class="bg-red-100 text-red-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo count($rejected_users); ?> total
                        </span>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="rejectedSearch" placeholder="Search rejected users..." 
                        class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-red-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <!-- Table Container -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700" id="rejectedTable">
                        <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-4 font-semibold">ID</th>
                                <th class="px-6 py-4 font-semibold">Name</th>
                                <th class="px-6 py-4 font-semibold">Email</th>
                                <th class="px-6 py-4 font-semibold">Username</th>
                                <th class="px-6 py-4 font-semibold">Position</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold text-center">Action</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-gray-200 bg-white" id="rejectedBody">
                            <?php if (count($rejected_users) > 0): ?>
                                <?php foreach ($rejected_users as $index => $user): ?>
                                    <tr class="hover:bg-red-50 transition-colors duration-150 <?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?> group">
                                        <td class="px-6 py-4 font-medium text-gray-900">#<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                        
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-red-500 to-red-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                                    <?php echo strtoupper(substr($user['firstname'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>
                                                    <div class="text-xs text-gray-500">ID: <?php echo $user['id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['email']); ?></td>
                                        <td class="px-6 py-4">@<?php echo htmlspecialchars($user['username']); ?></td>
                                        <td class="px-6 py-4"><?php echo htmlspecialchars($user['position'] ?? 'Not specified'); ?></td>
                                        
                                        <td class="px-6 py-4">
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border bg-red-100 text-red-800 border-red-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-red-500"></span>
                                                Rejected
                                            </span>
                                        </td>
                                        
                                        <td class="px-6 py-4 text-center">
                                            <div class="relative group inline-block">
                                                <button onclick='openModal(<?php echo json_encode([
                                                    'id' => $user['id'],
                                                    'firstname' => $user['firstname'],
                                                    'lastname' => $user['lastname'] ?? '',
                                                    'username' => $user['username'],
                                                    'email' => $user['email'],
                                                    'phone' => $user['phone'] ?? 'Not provided',
                                                    'address' => $user['address'] ?? 'Not provided',
                                                    'dob' => $user['dob'] ?? 'Not provided',
                                                    'position' => $user['position'] ?? 'Not specified',
                                                    'role' => $user['role'] ?? 'employee',
                                                    'employee_id' => $user['employee_id'] ?? 'N/A',
                                                    'status' => $user['status'] ?? 'rejected',
                                                    'created_at' => $user['created_at'] ?? ''
                                                ]); ?>)'
                                                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2 mx-auto">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View Info
                                                </button>
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                                                    View rejected user information
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                        <div class="flex flex-col items-center">
                                            <svg class="w-12 h-12 text-gray-400 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                                            </svg>
                                            <p class="text-gray-500">No rejected users found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- PROFESSIONAL MODAL -->
    <div id="infoModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
        
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-2xl scale-95 opacity-0 translate-y-4 sm:translate-y-0" id="modalContent">
                    
                    <div class="relative bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Rejected User Details
                        </h3>
                        <button onclick="closeModal()" class="absolute right-4 top-4 text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <div class="px-6 py-5 bg-gray-50 max-h-[70vh] overflow-y-auto">
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">First Name</label>
                                <p id="info_firstname" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Last Name</label>
                                <p id="info_lastname" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Username</label>
                                <p id="info_username" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Position</label>
                                <p id="info_position" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="sm:col-span-2 bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Email Address</label>
                                <p id="info_email" class="text-gray-900 font-medium mt-1 break-all"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</label>
                                <p id="info_phone" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date of Birth</label>
                                <p id="info_dob" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="sm:col-span-2 bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Address</label>
                                <p id="info_address" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</label>
                                <p id="info_employee_id" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Role</label>
                                <p id="info_role" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Account Status</label>
                                <p id="info_status" class="mt-1"></p>
                            </div>
                            <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Registered On</label>
                                <p id="info_created_at" class="text-gray-900 font-medium mt-1"></p>
                            </div>
                        </div>
                    </div>

                    <div class="bg-gray-100 px-6 py-4 flex flex-col sm:flex-row sm:justify-end gap-3">
                        <button type="button" onclick="closeModal()" 
                                class="inline-flex justify-center items-center gap-2 px-5 py-2.5 text-sm font-medium text-gray-700 bg-gray-200 rounded-lg hover:bg-gray-300 transition-colors">
                            Close
                        </button>
                        <button type="button" onclick="reapproveUser()" id="reapproveBtn" 
                                class="inline-flex justify-center items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                            </svg>
                            Re-approve User
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    let currentUser = {};

    function openModal(data) {
        currentUser = data;
        
        function formatDate(dateString) {
            if (!dateString) return 'Not provided';
            try {
                return new Date(dateString).toLocaleDateString('en-US', {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric'
                });
            } catch (e) {
                return dateString;
            }
        }
        
        document.getElementById('info_firstname').textContent = data.firstname || 'Not provided';
        document.getElementById('info_lastname').textContent = data.lastname || 'Not provided';
        document.getElementById('info_username').textContent = data.username ? '@' + data.username : 'Not provided';
        document.getElementById('info_position').textContent = data.position || 'Not specified';
        document.getElementById('info_email').textContent = data.email || 'Not provided';
        document.getElementById('info_phone').textContent = data.phone || 'Not provided';
        document.getElementById('info_address').textContent = data.address || 'Not provided';
        document.getElementById('info_dob').textContent = formatDate(data.dob);
        document.getElementById('info_role').textContent = data.role ? data.role.charAt(0).toUpperCase() + data.role.slice(1) : 'Employee';
        document.getElementById('info_employee_id').textContent = data.employee_id || 'Not assigned';
        document.getElementById('info_created_at').textContent = data.created_at ? formatDate(data.created_at) : 'Not available';
        
        const statusElement = document.getElementById('info_status');
        statusElement.innerHTML = `
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium bg-red-100 text-red-800">
                <span class="w-2 h-2 bg-red-500 rounded-full mr-2"></span>
                Rejected
            </span>
        `;

        const modal = document.getElementById('infoModal');
        const modalContent = document.getElementById('modalContent');
        modal.classList.remove('hidden');
        
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0', 'translate-y-4');
            modalContent.classList.add('scale-100', 'opacity-100', 'translate-y-0', 'modal-animate-in');
        }, 10);
    }

    function closeModal() {
        const modal = document.getElementById('infoModal');
        const modalContent = document.getElementById('modalContent');
        
        modalContent.classList.remove('scale-100', 'opacity-100', 'translate-y-0', 'modal-animate-in');
        modalContent.classList.add('scale-95', 'opacity-0', 'translate-y-4');
        
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    // ============================================
    // RE-APPROVE USER FUNCTION
    // ============================================
    function reapproveUser() {
        if (!currentUser.id) {
            showNotification('No user selected', 'error');
            return;
        }
        
        // Confirm action
        if (!confirm('Are you sure you want to re-approve this user? They will be moved to pending status for admin approval.')) {
            return;
        }
        
        // Show loading state
        const reapproveBtn = document.getElementById('reapproveBtn');
        const originalText = reapproveBtn.innerHTML;
        reapproveBtn.disabled = true;
        reapproveBtn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg> Processing...';
        
        // Send request to reapprove user (update status to 'pending')
        fetch('reapprove_user.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'id=' + currentUser.id
        })
        .then(res => res.text())
        .then(res => {
            if (res.trim() === 'success') {
                showNotification('User moved to pending for approval!', 'success');
                setTimeout(() => location.reload(), 1500);
            } else {
                showNotification('Error: ' + res, 'error');
                reapproveBtn.innerHTML = originalText;
                reapproveBtn.disabled = false;
            }
        })
        .catch(error => {
            showNotification('Network error occurred', 'error');
            reapproveBtn.innerHTML = originalText;
            reapproveBtn.disabled = false;
        });
    }

    // ============================================
    // NOTIFICATION FUNCTION
    // ============================================
    function showNotification(message, type = 'info') {
        // Remove existing notification
        const existingNotification = document.querySelector('.custom-notification');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `custom-notification fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-x-0 flex items-center gap-2 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            'bg-blue-500'
        }`;
        
        // Add icon based on type
        let icon = '';
        if (type === 'success') icon = '✓';
        else if (type === 'error') icon = '✗';
        else icon = 'ℹ';
        
        notification.innerHTML = `
            <span class="font-bold text-lg flex items-center justify-center w-6 h-6 rounded-full bg-white bg-opacity-20">${icon}</span>
            <span class="flex-1">${message}</span>
            <button onclick="this.parentElement.remove()" class="text-white hover:text-gray-200">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                </svg>
            </button>
        `;

        document.body.appendChild(notification);
        
        // Auto remove after 4 seconds
        setTimeout(() => {
            if (notification.parentNode) {
                notification.remove();
            }
        }, 4000);
    }

    // ============================================
    // SEARCH FUNCTIONALITY
    // ============================================
    document.getElementById('rejectedSearch')?.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        document.querySelectorAll('#rejectedBody tr').forEach(row => {
            if (row.querySelector('td[colspan="7"]')) return;
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(searchValue) ? '' : 'none';
        });
    });

    // ============================================
    // MODAL CLOSE EVENTS
    // ============================================
    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') closeModal();
    });

    // Close modal on backdrop click
    document.getElementById('infoModal').addEventListener('click', function(e) {
        if (e.target === this) closeModal();
    });

    // ============================================
    // SIDEBAR TOGGLE HANDLER
    // ============================================
    const mainContent = document.getElementById("mainContent");
    
    // Listen for sidebar toggle events
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

    // Check initial state
    const sidebar = document.getElementById("sidebar");
    if (sidebar && !sidebar.classList.contains("w-64")) {
        mainContent.classList.add("sidebar-collapsed");
    }

    // ============================================
    // ADD LOADING SPINNER STYLE
    // ============================================
    const style = document.createElement('style');
    style.textContent = `
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        .animate-spin {
            animation: spin 1s linear infinite;
        }
    `;
    document.head.appendChild(style);
    </script>

</body>
</html>