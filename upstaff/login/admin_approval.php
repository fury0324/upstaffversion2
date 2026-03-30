<?php
// Start session safely
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Include database
require_once __DIR__ . '/../config/db.php';


if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: login.php");
    exit();
}

// ============================================
// CHECK AND ADD MISSING COLUMNS AUTOMATICALLY
// ============================================
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

// ============================================
// FETCH PENDING USERS WITH ALL FIELDS
// ============================================
$stmt = $conn->prepare("SELECT id, firstname, lastname, username, email, phone, address, dob, position, role, employee_id, status FROM users WHERE status = 'pending' ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();

$users = [];
while ($row = $result->fetch_assoc()) {
    // Format data for display
    $users[] = [
        'id' => $row['id'],
        'firstname' => $row['firstname'] ?? '',
        'lastname' => $row['lastname'] ?? '',
        'username' => $row['username'] ?? '',
        'email' => $row['email'] ?? '',
        'phone' => $row['phone'] ?? '',
        'address' => $row['address'] ?? '',
        'dob' => $row['dob'] ?? '',
        'position' => $row['position'] ?? 'Not specified',
        'role' => $row['role'] ?? 'employee',
        'employee_id' => $row['employee_id'] ?? '',
        'status' => $row['status'] ?? 'pending'
    ];
}

// Optional: Count total users for statistics
$total_pending = count($users);

// Optional: Get statistics for dashboard
$stats = [
    'total_pending' => $total_pending,
    'total_approved' => 0,
    'total_rejected' => 0
];

// Get counts for other statuses if needed
$count_result = $conn->query("SELECT status, COUNT(*) as count FROM users GROUP BY status");
while ($row = $count_result->fetch_assoc()) {
    if ($row['status'] == 'approved') $stats['total_approved'] = $row['count'];
    if ($row['status'] == 'rejected') $stats['total_rejected'] = $row['count'];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Admin Approval</title>
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
    <!-- SIDEBAR -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT - with margin-left offset -->
    <div class="main-content" id="mainContent">
        <!-- PAGE CONTENT -->
        <div class="p-6 overflow-auto">

            <h1 class="text-2xl font-bold mb-4">Account Approvals</h1>
            <span class="text-sm text-gray-500 mb-6 block">Manage pending registrations</span>
            
            <!-- ENHANCED TABLE CONTAINER -->
            <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
                <!-- Table Header with Search and Filters -->
                <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-800">Pending Users</h2>
                        <span class="bg-blue-100 text-blue-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo count($users); ?> total
                        </span>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="tableSearch" placeholder="Search users..." 
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <!-- Table Container with horizontal scroll on mobile -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700" id="usersTable">
                        <!-- Table Head with improved styling -->
                        <thead class="bg-gradient-to-r from-gray-800 to-gray-900 text-white uppercase text-xs tracking-wider">
                            <tr>
                                <th class="px-6 py-4 font-semibold">ID</th>
                                <th class="px-6 py-4 font-semibold">
                                    <div class="flex items-center gap-1">
                                        Name
                                        <svg class="w-4 h-4 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path>
                                        </svg>
                                    </div>
                                </th>
                                <th class="px-6 py-4 font-semibold">Email</th>
                                <th class="px-6 py-4 font-semibold">Username</th>
                                <th class="px-6 py-4 font-semibold">Status</th>
                                <th class="px-6 py-4 font-semibold text-center">Action</th>
                            </tr>
                        </thead>
                        
                        <tbody class="divide-y divide-gray-200 bg-white" id="tableBody">
                            <?php if (count($users) > 0): ?>
                                <?php foreach ($users as $index => $user): ?>
                                    <tr class="hover:bg-blue-50 transition-colors duration-150 <?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?> group">
                                        <td class="px-6 py-4 font-medium text-gray-900">#<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                        
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <!-- Avatar with initials -->
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-blue-500 to-blue-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
                                                    <?php echo strtoupper(substr($user['firstname'], 0, 1)); ?>
                                                </div>
                                                <div>
                                                    <div class="font-medium text-gray-900"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></div>
                                                    <div class="text-xs text-gray-500">ID: <?php echo $user['id']; ?></div>
                                                </div>
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path>
                                                </svg>
                                                <span class="text-gray-600"><?php echo htmlspecialchars($user['email']); ?></span>
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-2">
                                                <svg class="w-4 h-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                                                </svg>
                                                <span class="text-gray-600">@<?php echo htmlspecialchars($user['username']); ?></span>
                                            </div>
                                        </td>
                                        
                                        <td class="px-6 py-4">
                                            <?php
                                            $status = strtolower($user['status'] ?? 'pending');
                                            $statusColors = [
                                                'pending' => 'bg-yellow-100 text-yellow-800 border-yellow-200',
                                                'approved' => 'bg-green-100 text-green-800 border-green-200',
                                                'rejected' => 'bg-red-100 text-red-800 border-red-200',
                                                'active' => 'bg-blue-100 text-blue-800 border-blue-200',
                                                'inactive' => 'bg-gray-100 text-gray-800 border-gray-200'
                                            ];
                                            $statusColor = $statusColors[$status] ?? 'bg-gray-100 text-gray-800 border-gray-200';
                                            ?>
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border <?php echo $statusColor; ?>">
                                                <span class="w-1.5 h-1.5 rounded-full <?php 
                                                    echo $status === 'pending' ? 'bg-yellow-500' : 
                                                        ($status === 'approved' ? 'bg-green-500' : 
                                                        ($status === 'rejected' ? 'bg-red-500' : 'bg-gray-500')); 
                                                ?>"></span>
                                                <?php echo ucfirst($status); ?>
                                            </span>
                                        </td>
                                        
                                        <td class="px-6 py-4 text-center">
                                            <!-- View Button with ALL fields -->
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
                                                    'status' => $user['status'] ?? 'pending'
                                                ]); ?>)'
                                                class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-all duration-200 shadow-sm hover:shadow-md flex items-center gap-2 group-hover:scale-105 mx-auto">
                                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path>
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path>
                                                    </svg>
                                                    View Info
                                                </button>
                                                <!-- Tooltip -->
                                                <div class="absolute bottom-full left-1/2 transform -translate-x-1/2 mb-2 px-2 py-1 bg-gray-900 text-white text-xs rounded opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap pointer-events-none">
                                                    View complete employee information
                                                </div>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <!-- Empty State -->
                                <tr>
                                    <td colspan="6" class="px-6 py-12">
                                        <div class="text-center">
                                            <div class="inline-flex items-center justify-center w-16 h-16 bg-gray-100 rounded-full mb-4">
                                                <svg class="w-8 h-8 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path>
                                                </svg>
                                            </div>
                                            <h3 class="text-lg font-medium text-gray-900 mb-2">No pending users</h3>
                                            <p class="text-gray-500 mb-4">There are no users pending approval at the moment.</p>
                                            <button onclick="location.reload()" class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path>
                                                </svg>
                                                Refresh
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer with Pagination -->
                <?php if (count($users) > 0): ?>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-medium">1</span> to <span class="font-medium"><?php echo count($users); ?></span> of <span class="font-medium"><?php echo count($users); ?></span> results
                    </div>
                    
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>
                            Previous
                        </button>
                        <button class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700">1</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">2</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">3</button>
                        <span class="px-2">...</span>
                        <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">10</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">
                            Next
                        </button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- PROFESSIONAL MODAL -->
    <div id="infoModal" class="fixed inset-0 z-50 hidden">
        <!-- Backdrop with blur effect -->
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
        
        <!-- Modal Container -->
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <!-- Modal Content -->
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-2xl scale-95 opacity-0 translate-y-4 sm:translate-y-0" id="modalContent">
                    
                    <!-- Header -->
                    <div class="relative bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path>
                            </svg>
                            Complete Registration Details
                        </h3>
                        <p class="text-blue-100 text-sm mt-1" id="info_registered"></p>
                        <button onclick="closeModal()" class="absolute right-4 top-4 text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>

                    <!-- Body -->
                    <div class="px-6 py-5 bg-gray-50 max-h-[70vh] overflow-y-auto">
                        <!-- Personal Information Section -->
                        <div class="mb-4">
                            <h4 class="text-sm font-bold text-gray-700 border-b border-gray-200 pb-2 mb-3">Personal Information</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- First Name -->
                                <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">First Name</label>
                                    <p id="info_firstname" class="text-gray-900 font-medium mt-1"></p>
                                </div>
                                
                                <!-- Last Name -->
                                <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Last Name</label>
                                    <p id="info_lastname" class="text-gray-900 font-medium mt-1"></p>
                                </div>
                                
                                <!-- Username -->
                                <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Username</label>
                                    <p id="info_username" class="text-gray-900 font-medium mt-1">@<span class="ml-0"></span></p>
                                </div>
                                
                                <!-- Position -->
                                <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Position</label>
                                    <p id="info_position" class="text-gray-900 font-medium mt-1"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Contact Information Section -->
                        <div class="mb-4">
                            <h4 class="text-sm font-bold text-gray-700 border-b border-gray-200 pb-2 mb-3">Contact Information</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Email -->
                                <div class="sm:col-span-2 bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Email Address</label>
                                    <p id="info_email" class="text-gray-900 font-medium mt-1 break-all"></p>
                                </div>
                                
                                <!-- Phone -->
                                <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Phone</label>
                                    <p id="info_phone" class="text-gray-900 font-medium mt-1"></p>
                                </div>
                                
                                <!-- Date of Birth -->
                                <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Date of Birth</label>
                                    <p id="info_dob" class="text-gray-900 font-medium mt-1"></p>
                                </div>
                                
                                <!-- Address -->
                                <div class="sm:col-span-2 bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Address</label>
                                    <p id="info_address" class="text-gray-900 font-medium mt-1"></p>
                                </div>
                            </div>
                        </div>

                        <!-- Account Information Section -->
                        <div class="mb-4">
                            <h4 class="text-sm font-bold text-gray-700 border-b border-gray-200 pb-2 mb-3">Account Information</h4>
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <!-- Employee ID -->
                                <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Employee ID</label>
                                    <p id="info_employee_id" class="text-gray-900 font-medium mt-1"></p>
                                </div>
                                
                                <!-- Role -->
                                <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Role</label>
                                    <p id="info_role" class="text-gray-900 font-medium mt-1"></p>
                                </div>
                                
                                <!-- Status -->
                                <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Account Status</label>
                                    <p id="info_status" class="mt-1"></p>
                                </div>
                                
                                <!-- Registration Date -->
                                <div class="bg-white p-3 rounded-lg border border-gray-100 shadow-sm">
                                    <label class="text-xs font-medium text-gray-500 uppercase tracking-wider">Registered On</label>
                                    <p id="info_created_at" class="text-gray-900 font-medium mt-1"></p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Footer -->
                    <div class="bg-gray-100 px-6 py-4 flex flex-col sm:flex-row sm:justify-end gap-3">
                        <button type="button" onclick="rejectUser()" id="rejectBtn" 
                                class="inline-flex justify-center items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-red-600 rounded-lg hover:bg-red-700 focus:ring-4 focus:ring-red-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                            Reject
                        </button>
                        
                        <button type="button" onclick="approveUser()" id="approveBtn" 
                                class="inline-flex justify-center items-center gap-2 px-5 py-2.5 text-sm font-medium text-white bg-green-600 rounded-lg hover:bg-green-700 focus:ring-4 focus:ring-green-300 transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                            </svg>
                            Approve
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <style>
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

    <script>
    let currentUser = {};

    function openModal(data) {
        currentUser = data;
        
        // Format date function
        function formatDate(dateString) {
            if (!dateString || dateString === 'N/A' || dateString === 'Not provided') return 'Not provided';
            try {
                const options = { year: 'numeric', month: 'long', day: 'numeric' };
                return new Date(dateString).toLocaleDateString(undefined, options);
            } catch (e) {
                return dateString;
            }
        }
        
        // Update all fields with better formatting
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
        
        // Add registration date if available
        if (document.getElementById('info_created_at')) {
            document.getElementById('info_created_at').textContent = data.created_at ? formatDate(data.created_at) : 'Not available';
        }
        
        // Update status with appropriate badge color
        const statusElement = document.getElementById('info_status');
        const status = data.status || 'pending';
        const statusColors = {
            'pending': 'bg-yellow-100 text-yellow-800',
            'approved': 'bg-green-100 text-green-800',
            'rejected': 'bg-red-100 text-red-800',
            'active': 'bg-blue-100 text-blue-800',
            'inactive': 'bg-gray-100 text-gray-800'
        };
        
        const statusColor = statusColors[status.toLowerCase()] || 'bg-gray-100 text-gray-800';
        
        statusElement.innerHTML = `
            <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-medium ${statusColor}">
                <span class="w-2 h-2 bg-current rounded-full mr-2"></span>
                ${status.charAt(0).toUpperCase() + status.slice(1)}
            </span>
        `;

        // Show modal with animation
        const modal = document.getElementById('infoModal');
        const modalContent = document.getElementById('modalContent');
        
        modal.classList.remove('hidden');
        
        // Trigger animation
        setTimeout(() => {
            modalContent.classList.remove('scale-95', 'opacity-0', 'translate-y-4');
            modalContent.classList.add('scale-100', 'opacity-100', 'translate-y-0');
        }, 10);
    }

    function closeModal() {
        const modal = document.getElementById('infoModal');
        const modalContent = document.getElementById('modalContent');
        
        // Reverse animation
        modalContent.classList.remove('scale-100', 'opacity-100', 'translate-y-0');
        modalContent.classList.add('scale-95', 'opacity-0', 'translate-y-4');
        
        // Hide modal after animation
        setTimeout(() => {
            modal.classList.add('hidden');
        }, 200);
    }

    function approveUser() {
        if (!currentUser.id) {
            showNotification('No user selected', 'error');
            return;
        }
        
        // Confirm action
        if (!confirm('Are you sure you want to approve this user?')) {
            return;
        }
        
        // Show loading state
        const approveBtn = document.getElementById('approveBtn');
        const rejectBtn = document.getElementById('rejectBtn');
        const originalText = approveBtn.innerHTML;
        
        approveBtn.disabled = true;
        rejectBtn.disabled = true;
        approveBtn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg> Processing...';
        
        fetch("../approve_user.php", {
            method: "POST",
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: "id=" + currentUser.id
        })
        .then(res => res.text())
        .then(res => {
            if (res.trim() === "success") {
                showNotification('User approved successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error: ' + res, 'error');
                approveBtn.innerHTML = originalText;
                approveBtn.disabled = false;
                rejectBtn.disabled = false;
            }
        })
        .catch(error => {
            showNotification('Network error occurred', 'error');
            approveBtn.innerHTML = originalText;
            approveBtn.disabled = false;
            rejectBtn.disabled = false;
        });
    }

    function rejectUser() {
        if (!currentUser.id) {
            showNotification('No user selected', 'error');
            return;
        }
        
        // Confirm action
        if (!confirm('Are you sure you want to reject this user?')) {
            return;
        }
        
        // Show loading state
        const rejectBtn = document.getElementById('rejectBtn');
        const approveBtn = document.getElementById('approveBtn');
        const originalText = rejectBtn.innerHTML;
        
        rejectBtn.disabled = true;
        approveBtn.disabled = true;
        rejectBtn.innerHTML = '<svg class="animate-spin h-4 w-4 mr-2" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" fill="none"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"/></svg> Processing...';
        
        fetch("../reject_user.php", {
            method: "POST",
            headers: { "Content-Type": "application/x-www-form-urlencoded" },
            body: "id=" + encodeURIComponent(currentUser.id)
        })
        .then(res => res.text())
        .then(res => {
            if (res.trim() === "success") {
                showNotification('User rejected successfully!', 'success');
                setTimeout(() => location.reload(), 1000);
            } else {
                showNotification('Error: ' + res, 'error');
                rejectBtn.innerHTML = originalText;
                rejectBtn.disabled = false;
                approveBtn.disabled = false;
            }
        })
        .catch(error => {
            showNotification('Network error occurred', 'error');
            rejectBtn.innerHTML = originalText;
            rejectBtn.disabled = false;
            approveBtn.disabled = false;
        });
    }

    // Toast notification function
    function showNotification(message, type = 'info') {
        // Remove existing notification
        const existingNotification = document.querySelector('.custom-toast');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        // Create notification element
        const notification = document.createElement('div');
        notification.className = `custom-toast fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-x-full flex items-center gap-2 ${
            type === 'success' ? 'bg-green-500' : 
            type === 'error' ? 'bg-red-500' : 
            'bg-blue-500'
        }`;
        
        // Add icon based on type
        const icon = type === 'success' ? '✓' : type === 'error' ? '✗' : 'ℹ';
        notification.innerHTML = `<span class="font-bold text-lg">${icon}</span> <span>${message}</span>`;
        
        // Add to body
        document.body.appendChild(notification);
        
        // Animate in
        setTimeout(() => {
            notification.classList.remove('translate-x-full');
        }, 10);
        
        // Auto remove
        setTimeout(() => {
            notification.classList.add('translate-x-full');
            setTimeout(() => notification.remove(), 300);
        }, 3000);
    }

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

    // Close modal on escape key
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeModal();
        }
    });

    // Close modal on backdrop click
    document.getElementById('infoModal').addEventListener('click', function(e) {
        if (e.target === this || e.target.classList.contains('absolute')) {
            closeModal();
        }
    });

    // Table search functionality
    document.getElementById('tableSearch')?.addEventListener('keyup', function() {
        const searchValue = this.value.toLowerCase();
        const tableRows = document.querySelectorAll('#tableBody tr');
        let visibleCount = 0;
        
        tableRows.forEach(row => {
            // Skip empty state row
            if (row.querySelector('td[colspan="6"]')) return;
            
            const text = row.textContent.toLowerCase();
            if (text.includes(searchValue)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
        
        // Show/hide empty state message if needed
        const emptyState = document.querySelector('#tableBody tr td[colspan="6"]');
        if (emptyState) {
            if (visibleCount === 0 && searchValue !== '') {
                emptyState.style.display = '';
                emptyState.innerHTML = `<td colspan="6" class="px-6 py-12 text-center">
                    <div class="text-gray-500">No results found for "${searchValue}"</div>
                </td>`;
            } else {
                emptyState.style.display = 'none';
            }
        }
    });

    // Sorting functionality
    let sortDirections = []; // Track sort direction for each column

    document.querySelectorAll('#usersTable th .flex.items-center.gap-1').forEach((header, index) => {
        // Initialize sort direction
        sortDirections[index] = 'asc';
        
        header.addEventListener('click', function() {
            sortTable(index);
            
            // Update sort icon
            const icon = this.querySelector('svg');
            if (icon) {
                if (sortDirections[index] === 'asc') {
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"></path>';
                } else {
                    icon.innerHTML = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>';
                }
            }
        });
    });

    function sortTable(columnIndex) {
        const table = document.getElementById('usersTable');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr:not(.empty-state)'));
        
        // Toggle sort direction
        sortDirections[columnIndex] = sortDirections[columnIndex] === 'asc' ? 'desc' : 'asc';
        
        const sortedRows = rows.sort((a, b) => {
            let aValue = a.cells[columnIndex].textContent.trim();
            let bValue = b.cells[columnIndex].textContent.trim();
            
            // Remove # from ID if sorting by ID column
            if (columnIndex === 0) {
                aValue = parseInt(aValue.replace('#', ''));
                bValue = parseInt(bValue.replace('#', ''));
            }
            
            // Handle numeric comparison
            if (!isNaN(aValue) && !isNaN(bValue)) {
                aValue = parseFloat(aValue);
                bValue = parseFloat(bValue);
                return sortDirections[columnIndex] === 'asc' ? aValue - bValue : bValue - aValue;
            }
            
            // String comparison
            const comparison = aValue.localeCompare(bValue);
            return sortDirections[columnIndex] === 'asc' ? comparison : -comparison;
        });
        
        tbody.innerHTML = '';
        sortedRows.forEach(row => tbody.appendChild(row));
        
        // Add back empty state if needed
        if (rows.length === 0) {
            const emptyRow = document.createElement('tr');
            emptyRow.className = 'empty-state';
            emptyRow.innerHTML = `<td colspan="6" class="px-6 py-12 text-center">
                <div class="text-gray-500">No users found</div>
            </td>`;
            tbody.appendChild(emptyRow);
        }
    }

    // Clear search button functionality (optional)
    function clearSearch() {
        const searchInput = document.getElementById('tableSearch');
        if (searchInput) {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('keyup'));
        }
    }

    // Add debounce to search for better performance
    function debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    // Replace the search event listener with debounced version
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        const debouncedSearch = debounce(function(e) {
            const searchValue = e.target.value.toLowerCase();
            const tableRows = document.querySelectorAll('#tableBody tr');
            
            tableRows.forEach(row => {
                // Skip empty state row
                if (row.querySelector('td[colspan="6"]')) return;
                
                const text = row.textContent.toLowerCase();
                if (text.includes(searchValue)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        }, 300);
        
        searchInput.addEventListener('keyup', debouncedSearch);
    }

    // Refresh button functionality
    document.querySelector('button:contains("Refresh")')?.addEventListener('click', function() {
        location.reload();
    });

    // Initialize tooltips or other UI elements on page load
    document.addEventListener('DOMContentLoaded', function() {
        // Add any initialization code here
        console.log('Admin page loaded');
    });
    </script>

</body>
</html>