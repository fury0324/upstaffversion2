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

// ============================================
// FETCH APPROVED USERS ONLY
// ============================================
$stmt = $conn->prepare("SELECT id, firstname, lastname, username, email, phone, address, dob, position, role, employee_id, status, created_at FROM users WHERE status = 'approved' ORDER BY id DESC");
$stmt->execute();
$result = $stmt->get_result();

$approved_users = [];
while ($row = $result->fetch_assoc()) {
    $approved_users[] = $row;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Approved Users - Upstaff</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
<style>
    * {
        font-family: 'Inter', system-ui, -apple-system, sans-serif;
    }
    
    /* MAIN CONTENT OFFSET - This pushes content to the right of fixed sidebar */
    .main-content {
        margin-left: 16rem;
        width: calc(100% - 16rem);
        min-height: 100vh;
        transition: margin-left 0.3s ease, width 0.3s ease;
    }
    
    /* When sidebar is collapsed */
    .main-content.sidebar-collapsed {
        margin-left: 5rem;
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

    /* Dropdown menu styling */
    .dropdown {
        position: relative;
        display: inline-block;
    }

    .dropdown-content {
        display: none;
        position: absolute;
        right: 0;
        background-color: #fff;
        min-width: 160px;
        box-shadow: 0 8px 16px rgba(0,0,0,0.1);
        border-radius: 8px;
        z-index: 50;
        margin-top: 0.5rem;
        border: 1px solid #e5e7eb;
        overflow: hidden;
    }

    .dropdown-content a,
    .dropdown-content button {
        color: #374151;
        padding: 8px 16px;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 8px;
        width: 100%;
        text-align: left;
        background: none;
        border: none;
        cursor: pointer;
        font-size: 0.875rem;
        transition: background 0.2s;
    }

    .dropdown-content a:hover,
    .dropdown-content button:hover {
        background-color: #f3f4f6;
    }

    .dropdown-content button.delete-btn {
        color: #dc2626;
    }
    .dropdown-content button.delete-btn:hover {
        background-color: #fee2e2;
    }

    /* Show dropdown */
    .dropdown.open .dropdown-content {
        display: block;
    }

    /* Loading spinner */
    .spinner {
        display: inline-block;
        width: 20px;
        height: 20px;
        border: 2px solid rgba(0,0,0,0.1);
        border-radius: 50%;
        border-top-color: #3498db;
        animation: spin 0.6s linear infinite;
    }
    @keyframes spin {
        to { transform: rotate(360deg); }
    }
</style>
</head>

<body class="bg-gray-100">
    <!-- SIDEBAR - Your sidebar included here -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT - with margin-left offset -->
    <div class="main-content" id="mainContent">
        <!-- PAGE CONTENT -->
        <div class="p-6 overflow-auto">

            <h1 class="text-2xl font-bold mb-4">Approved Users</h1>
            <span class="text-sm text-gray-500 mb-6 block">View all approved users</span>
            
            <!-- APPROVED USERS TABLE -->
            <div class="bg-white shadow-lg rounded-xl overflow-hidden border border-gray-200">
                <!-- Table Header -->
                <div class="p-4 border-b border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="flex items-center gap-3">
                        <h2 class="text-lg font-semibold text-gray-800 flex items-center gap-2">
                            <svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path>
                            </svg>
                            Approved Users List
                        </h2>
                        <span class="bg-green-100 text-green-800 text-xs font-medium px-2.5 py-0.5 rounded-full">
                            <?php echo count($approved_users); ?> total
                        </span>
                    </div>
                    
                    <!-- Search Bar -->
                    <div class="relative w-full sm:w-64">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path>
                            </svg>
                        </div>
                        <input type="text" id="approvedSearch" placeholder="Search approved users..." 
                               class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-lg bg-white focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent text-sm">
                    </div>
                </div>

                <!-- Table Container -->
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm text-left text-gray-700" id="approvedTable">
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
                        
                        <tbody class="divide-y divide-gray-200 bg-white" id="approvedBody">
                            <?php if (count($approved_users) > 0): ?>
                                <?php foreach ($approved_users as $index => $user): ?>
                                    <tr class="hover:bg-green-50 transition-colors duration-150 <?php echo $index % 2 === 0 ? 'bg-white' : 'bg-gray-50'; ?> group" data-user-id="<?php echo $user['id']; ?>">
                                        <td class="px-6 py-4 font-medium text-gray-900">#<?php echo str_pad($user['id'], 3, '0', STR_PAD_LEFT); ?></td>
                                        <td class="px-6 py-4">
                                            <div class="flex items-center gap-3">
                                                <div class="w-8 h-8 rounded-full bg-gradient-to-br from-green-500 to-green-600 flex items-center justify-center text-white font-semibold text-sm shadow-sm">
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
                                            <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-full text-xs font-medium border bg-green-100 text-green-800 border-green-200">
                                                <span class="w-1.5 h-1.5 rounded-full bg-green-500"></span>
                                                Approved
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 text-center">
                                            <!-- Three-dots dropdown -->
                                            <div class="dropdown relative inline-block">
                                                <button type="button" class="dropdown-toggle px-2 py-1 text-gray-600 hover:bg-gray-100 rounded-lg transition">
                                                    <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"></path>
                                                    </svg>
                                                </button>
                                                <div class="dropdown-content hidden absolute right-0 mt-2 w-48 bg-white rounded-md shadow-lg z-50 border border-gray-200">
                                                    <button type="button" class="edit-user-btn flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                                        <i class="fas fa-edit text-gray-500"></i> Edit Info
                                                    </button>
                                                    <button type="button" class="view-logs-btn flex items-center gap-2 px-4 py-2 text-sm text-gray-700 hover:bg-gray-100 w-full text-left" data-user-id="<?php echo $user['id']; ?>" data-user-name="<?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?>">
                                                        <i class="fas fa-history text-gray-500"></i> View Logs
                                                    </button>
                                                    <button type="button" class="delete-user-btn flex items-center gap-2 px-4 py-2 text-sm text-red-600 hover:bg-red-50 w-full text-left" data-user-id="<?php echo $user['id']; ?>">
                                                        <i class="fas fa-trash-alt text-red-500"></i> Delete
                                                    </button>
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
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 12H4"></path>
                                            </svg>
                                            <p class="text-gray-500">No approved users found</p>
                                        </div>
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Table Footer with Pagination (placeholder) -->
                <?php if (count($approved_users) > 0): ?>
                <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex flex-col sm:flex-row justify-between items-center gap-4">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-medium">1</span> to <span class="font-medium"><?php echo count($approved_users); ?></span> of <span class="font-medium"><?php echo count($approved_users); ?></span> results
                    </div>
                    <div class="flex items-center gap-2">
                        <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-500 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed" disabled>Previous</button>
                        <button class="px-3 py-1 bg-blue-600 text-white rounded-md hover:bg-blue-700">1</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">2</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">3</button>
                        <span class="px-2">...</span>
                        <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">10</button>
                        <button class="px-3 py-1 border border-gray-300 rounded-md bg-white text-gray-700 hover:bg-gray-50">Next</button>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- MODAL FOR VIEWING LOGS (Blue gradient) -->
    <div id="logsModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-2xl modal-animate-in">
                    <div class="relative bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-history"></i> User Activity Logs
                        </h3>
                        <button onclick="closeLogsModal()" class="absolute right-4 top-4 text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-5 bg-gray-50 max-h-[70vh] overflow-y-auto">
                        <div id="logsContent" class="space-y-2">
                            <p class="text-gray-500 text-center">Loading logs...</p>
                        </div>
                    </div>
                    <div class="bg-gray-100 px-6 py-4 flex justify-end">
                        <button type="button" onclick="closeLogsModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">Close</button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL FOR EDITING USER (Green gradient, matching logs modal style) -->
    <div id="editModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-3xl modal-animate-in">
                    <div class="relative bg-gradient-to-r from-green-600 to-green-700 px-6 py-5">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-user-edit"></i> Edit User
                        </h3>
                        <button onclick="closeEditModal()" class="absolute right-4 top-4 text-white/80 hover:text-white transition-colors">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-5 bg-gray-50 max-h-[70vh] overflow-y-auto">
                        <form id="editUserForm">
                            <input type="hidden" name="user_id" id="edit_user_id">
                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">First Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="firstname" id="edit_firstname" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Last Name <span class="text-red-500">*</span></label>
                                    <input type="text" name="lastname" id="edit_lastname" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Email <span class="text-red-500">*</span></label>
                                    <input type="email" name="email" id="edit_email" class="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-green-500 focus:border-green-500">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                    <input type="text" name="phone" id="edit_phone" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Date of Birth</label>
                                    <input type="date" name="dob" id="edit_dob" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Position</label>
                                    <input type="text" name="position" id="edit_position" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Role</label>
                                    <select name="role" id="edit_role" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="employee">Employee</option>
                                        <option value="admin">Admin</option>
                                    </select>
                                </div>
                                <!-- Employee ID field removed -->
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Status</label>
                                    <select name="status" id="edit_status" class="w-full px-3 py-2 border border-gray-300 rounded-lg">
                                        <option value="approved">Approved</option>
                                        <option value="pending">Pending</option>
                                        <option value="rejected">Rejected</option>
                                    </select>
                                </div>
                                <div class="sm:col-span-2">
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                    <textarea name="address" id="edit_address" rows="2" class="w-full px-3 py-2 border border-gray-300 rounded-lg"></textarea>
                                </div>
                            </div>
                            <div class="mt-6 flex justify-end gap-3">
                                <button type="button" onclick="closeEditModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300 transition">Cancel</button>
                                <button type="submit" id="editSubmitBtn" class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 transition">Save Changes</button>
                            </div>
                            <div id="editMessage" class="mt-3 text-sm hidden"></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // ---------- Dropdown handling ----------
        document.querySelectorAll('.dropdown').forEach(dropdown => {
            const toggleBtn = dropdown.querySelector('.dropdown-toggle');
            const dropdownContent = dropdown.querySelector('.dropdown-content');
            
            toggleBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                document.querySelectorAll('.dropdown').forEach(d => {
                    if (d !== dropdown) d.classList.remove('open');
                });
                dropdown.classList.toggle('open');
            });
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('.dropdown')) {
                document.querySelectorAll('.dropdown').forEach(d => d.classList.remove('open'));
            }
        });

        // ---------- Edit Modal ----------
        const editModal = document.getElementById('editModal');
        const editForm = document.getElementById('editUserForm');
        const editMessage = document.getElementById('editMessage');
        const editSubmitBtn = document.getElementById('editSubmitBtn');

        async function openEditModal(userId) {
            // Reset form
            editForm.reset();
            editMessage.classList.add('hidden');
            editMessage.innerHTML = '';
            
            // Show loading state
            editSubmitBtn.disabled = true;
            editSubmitBtn.innerHTML = '<span class="spinner"></span> Loading...';
            
            // Fetch user data
            try {
                const response = await fetch(`get_user.php?id=${userId}`);
                const data = await response.json();
                if (data.success) {
                    const user = data.user;
                    document.getElementById('edit_user_id').value = user.id;
                    document.getElementById('edit_firstname').value = user.firstname;
                    document.getElementById('edit_lastname').value = user.lastname;
                    document.getElementById('edit_email').value = user.email;
                    document.getElementById('edit_phone').value = user.phone || '';
                    document.getElementById('edit_dob').value = user.dob || '';
                    document.getElementById('edit_position').value = user.position || '';
                    document.getElementById('edit_role').value = user.role || 'employee';
                    // No employee_id field to populate
                    document.getElementById('edit_status').value = user.status || 'approved';
                    document.getElementById('edit_address').value = user.address || '';
                    
                    editModal.classList.remove('hidden');
                } else {
                    alert('Failed to load user data: ' + (data.message || 'Unknown error'));
                }
            } catch (err) {
                console.error(err);
                alert('Error loading user data');
            } finally {
                editSubmitBtn.disabled = false;
                editSubmitBtn.innerHTML = 'Save Changes';
            }
        }

        function closeEditModal() {
            editModal.classList.add('hidden');
        }

        editForm.addEventListener('submit', async function(e) {
            e.preventDefault();
            
            const formData = new FormData(editForm);
            const data = Object.fromEntries(formData.entries());
            
            editSubmitBtn.disabled = true;
            editSubmitBtn.innerHTML = '<span class="spinner"></span> Saving...';
            editMessage.classList.add('hidden');
            
            try {
                const response = await fetch('update_user.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(data)
                });
                const result = await response.json();
                if (result.success) {
                    editMessage.innerHTML = '<div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">User updated successfully. Refreshing...</div>';
                    editMessage.classList.remove('hidden');
                    // Refresh the table row (or reload page)
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    editMessage.innerHTML = `<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">${result.message || 'Update failed'}</div>`;
                    editMessage.classList.remove('hidden');
                    editSubmitBtn.disabled = false;
                    editSubmitBtn.innerHTML = 'Save Changes';
                }
            } catch (err) {
                console.error(err);
                editMessage.innerHTML = '<div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">Network error. Please try again.</div>';
                editMessage.classList.remove('hidden');
                editSubmitBtn.disabled = false;
                editSubmitBtn.innerHTML = 'Save Changes';
            }
        });

        // Attach edit button listeners
        document.addEventListener('click', function(e) {
            const editBtn = e.target.closest('.edit-user-btn');
            if (editBtn) {
                e.preventDefault();
                const userId = editBtn.getAttribute('data-user-id');
                openEditModal(userId);
                editBtn.closest('.dropdown')?.classList.remove('open');
            }
        });

        // ---------- View Logs ----------
        const logsModal = document.getElementById('logsModal');
        const logsContent = document.getElementById('logsContent');

        function openLogsModal(userId) {
            logsContent.innerHTML = '<p class="text-gray-500 text-center">Loading logs...</p>';
            logsModal.classList.remove('hidden');
            
            fetch(`get_user_logs.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.logs.length > 0) {
                        let html = '<ul class="divide-y divide-gray-200">';
                        data.logs.forEach(log => {
                            html += `<li class="py-3">
                                        <div class="text-sm text-gray-900">${escapeHtml(log.action)}</div>
                                        <div class="text-xs text-gray-500">${escapeHtml(log.timestamp)}</div>
                                     </li>`;
                        });
                        html += '</ul>';
                        logsContent.innerHTML = html;
                    } else {
                        logsContent.innerHTML = '<p class="text-gray-500 text-center">No logs found for this user.</p>';
                    }
                })
                .catch(err => {
                    console.error(err);
                    logsContent.innerHTML = '<p class="text-red-500 text-center">Failed to load logs. Please try again.</p>';
                });
        }

        function closeLogsModal() {
            logsModal.classList.add('hidden');
        }

        document.addEventListener('click', function(e) {
            const logsBtn = e.target.closest('.view-logs-btn');
            if (logsBtn) {
                e.preventDefault();
                const userId = logsBtn.getAttribute('data-user-id');
                openLogsModal(userId);
                logsBtn.closest('.dropdown')?.classList.remove('open');
            }
        });

        // ---------- Delete user ----------
        document.addEventListener('click', function(e) {
            const deleteBtn = e.target.closest('.delete-user-btn');
            if (deleteBtn) {
                e.preventDefault();
                const userId = deleteBtn.getAttribute('data-user-id');
                const row = deleteBtn.closest('tr');
                
                if (confirm('Are you sure you want to delete this user? This action cannot be undone.')) {
                    fetch('delete_user.php', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ user_id: userId })
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            row.remove();
                            const countSpan = document.querySelector('.bg-green-100.text-green-800');
                            if (countSpan) {
                                let currentCount = parseInt(countSpan.textContent);
                                countSpan.textContent = (currentCount - 1) + ' total';
                            }
                            alert('User deleted successfully.');
                        } else {
                            alert('Error deleting user: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        alert('Failed to delete user. Please check the server logs.');
                    });
                }
                deleteBtn.closest('.dropdown')?.classList.remove('open');
            }
        });

        // Search functionality
        document.getElementById('approvedSearch')?.addEventListener('keyup', function() {
            const searchValue = this.value.toLowerCase();
            document.querySelectorAll('#approvedBody tr').forEach(row => {
                if (row.querySelector('td[colspan="7"]')) return;
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(searchValue) ? '' : 'none';
            });
        });

        // Sidebar toggle handler
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

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLogsModal();
                closeEditModal();
            }
        });
        // Close modals on backdrop click
        logsModal.addEventListener('click', function(e) {
            if (e.target === logsModal) closeLogsModal();
        });
        editModal.addEventListener('click', function(e) {
            if (e.target === editModal) closeEditModal();
        });

        function escapeHtml(str) {
            if (!str) return '';
            return str.replace(/[&<>]/g, function(m) {
                if (m === '&') return '&amp;';
                if (m === '<') return '&lt;';
                if (m === '>') return '&gt;';
                return m;
            });
        }
    </script>
</body>
</html>