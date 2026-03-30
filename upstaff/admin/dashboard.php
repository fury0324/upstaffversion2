<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login/login.php");
    exit();
}

// Get dashboard statistics
$stats = [];

// Total employees (users with role 'employee')
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE role = 'employee'");
$stats['total_employees'] = $result->fetch_assoc()['total'];

// Total courses (you'll need a courses table - adjust this query based on your actual table)
$result = $conn->query("SELECT COUNT(*) as total FROM courses");
if ($result) {
    $stats['total_courses'] = $result->fetch_assoc()['total'];
} else {
    $stats['total_courses'] = 0;
}

// Completed today (placeholder - adjust based on your actual data)
$today = date('Y-m-d');
$result = $conn->query("SELECT COUNT(*) as total FROM course_completions WHERE DATE(completed_at) = '$today'");
if ($result) {
    $stats['completed_today'] = $result->fetch_assoc()['total'];
} else {
    $stats['completed_today'] = 0;
}

// Pending approvals
$result = $conn->query("SELECT COUNT(*) as total FROM users WHERE status = 'pending'");
$stats['pending_approvals'] = $result->fetch_assoc()['total'];

// Get recent activities
$recent_users = $conn->query("SELECT firstname, lastname, created_at FROM users ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - UpStaff Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
        
        /* MAIN CONTENT OFFSET  */
        .main-content {
            margin-left: 16rem; 
            width: calc(100% - 16rem);
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        
        /* When sidebar is collapsed */
        .main-content.sidebar-collapsed {
            margin-left: 5rem; /* w-20 */
            width: calc(100% - 5rem);
        }
        
        .stat-card {
            transition: all 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .fade-in {
            animation: fadeIn 0.5s ease-out;
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
    <!-- SIDEBAR KO -->
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <!-- MAIN CONTENT - with margin-left offset -->
    <div class="main-content" id="mainContent">
        <!-- HEADER -->
        <?php include __DIR__ . '/../includes/header.php'; ?>
        
        <!-- PAGE CONTENT -->
        <div class="p-6 overflow-auto">
            <!-- Welcome Section -->
            <div class="mb-8 fade-in">
                <h1 class="text-2xl md:text-3xl font-bold text-gray-800">Welcome back, <?php echo htmlspecialchars($_SESSION['firstname'] ?? 'Admin'); ?>!</h1>
                <p class="text-gray-500 mt-1">Here's what's happening with UpStaff Academy today</p>
            </div>

            <!-- Stats Grid -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 md:gap-6 mb-8 fade-in">
                <!-- Total Employees Card -->
                <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-xl shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg">
                            <i class="fas fa-users text-xl"></i>
                        </div>
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-full">+12% this month</span>
                    </div>
                    <h3 class="text-sm font-medium opacity-90">Total Employees</h3>
                    <div class="flex items-end justify-between">
                        <h2 class="text-3xl font-bold"><?php echo $stats['total_employees']; ?></h2>
                        <span class="text-sm opacity-75">active accounts</span>
                    </div>
                </div>

                <!-- Total Courses Card -->
                <div class="stat-card bg-gradient-to-br from-purple-500 to-purple-600 text-white p-6 rounded-xl shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg">
                            <i class="fas fa-book-open text-xl"></i>
                        </div>
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-full"><?php echo $stats['total_courses']; ?> active</span>
                    </div>
                    <h3 class="text-sm font-medium opacity-90">Total Courses</h3>
                    <div class="flex items-end justify-between">
                        <h2 class="text-3xl font-bold"><?php echo $stats['total_courses']; ?></h2>
                        <span class="text-sm opacity-75">available</span>
                    </div>
                </div>

                <!-- Completed Today Card -->
                <div class="stat-card bg-gradient-to-br from-green-500 to-green-600 text-white p-6 rounded-xl shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg">
                            <i class="fas fa-check-circle text-xl"></i>
                        </div>
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-full">today</span>
                    </div>
                    <h3 class="text-sm font-medium opacity-90">Completed Today</h3>
                    <div class="flex items-end justify-between">
                        <h2 class="text-3xl font-bold"><?php echo $stats['completed_today']; ?></h2>
                        <span class="text-sm opacity-75">courses</span>
                    </div>
                </div>

                <!-- Pending Approvals Card -->
                <div class="stat-card bg-gradient-to-br from-yellow-500 to-yellow-600 text-white p-6 rounded-xl shadow-lg">
                    <div class="flex items-center justify-between mb-3">
                        <div class="bg-white/20 p-3 rounded-lg">
                            <i class="fas fa-clock text-xl"></i>
                        </div>
                        <span class="text-xs bg-white/20 px-2 py-1 rounded-full">action needed</span>
                    </div>
                    <h3 class="text-sm font-medium opacity-90">Pending Approvals</h3>
                    <div class="flex items-end justify-between">
                        <h2 class="text-3xl font-bold"><?php echo $stats['pending_approvals']; ?></h2>
                        <a href="../login/admin_approval.php" class="text-sm opacity-75 hover:opacity-100 transition-opacity">review →</a>
                    </div>
                </div>
            </div>

            <!-- Recent Activity Section -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-8 fade-in">
                <!-- Recent Registrations -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-lg font-semibold text-gray-800">Recent Registrations</h3>
                        <span class="text-xs bg-blue-100 text-blue-800 px-2 py-1 rounded-full">newest first</span>
                    </div>
                    <div class="space-y-4 max-h-96 overflow-y-auto pr-2">
                        <?php if ($recent_users && $recent_users->num_rows > 0): ?>
                            <?php while($user = $recent_users->fetch_assoc()): ?>
                                <div class="flex items-center gap-3 p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                                    <div class="w-10 h-10 bg-gradient-to-br from-blue-500 to-blue-600 rounded-full flex items-center justify-center text-white font-semibold text-sm">
                                        <?php echo strtoupper(substr($user['firstname'], 0, 1)); ?>
                                    </div>
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-800"><?php echo htmlspecialchars($user['firstname'] . ' ' . $user['lastname']); ?></p>
                                        <p class="text-xs text-gray-500"><?php echo date('M d, Y', strtotime($user['created_at'])); ?></p>
                                    </div>
                                    <span class="text-xs bg-yellow-100 text-yellow-800 px-2 py-1 rounded-full">new</span>
                                </div>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <p class="text-gray-500 text-sm">No recent registrations</p>
                        <?php endif; ?>
                    </div>
                    <a href="../login/admin_approval.php" class="mt-4 inline-block text-sm text-blue-600 hover:text-blue-700 font-medium">
                        View all registrations →
                    </a>
                </div>

                <!-- Quick Actions -->
                <div class="bg-white p-6 rounded-xl shadow-lg">
                    <h3 class="text-lg font-semibold text-gray-800 mb-4">Quick Actions</h3>
                    <div class="space-y-3">
                        <a href="../login/admin_approval.php" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-user-check text-yellow-600 text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Pending Approvals</span>
                            </div>
                            <span class="bg-yellow-500 text-white text-xs px-2 py-1 rounded-full"><?php echo $stats['pending_approvals']; ?></span>
                        </a>
                        
                        <a href="courses.php" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-purple-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-plus-circle text-purple-600 text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Add New Course</span>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400"></i>
                        </a>
                        
                        <a href="reports.php" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-chart-bar text-green-600 text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">Generate Reports</span>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400"></i>
                        </a>
                        
                        <a href="settings.php" class="flex items-center justify-between p-3 bg-gray-50 rounded-lg hover:bg-gray-100 transition-colors">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-gray-100 rounded-full flex items-center justify-center">
                                    <i class="fas fa-cog text-gray-600 text-sm"></i>
                                </div>
                                <span class="text-sm font-medium text-gray-700">System Settings</span>
                            </div>
                            <i class="fas fa-arrow-right text-gray-400"></i>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener("DOMContentLoaded", () => {
        const mainContent = document.getElementById("mainContent");
        
        // Listen for sidebar toggle events
        window.addEventListener('sidebarToggle', function() {
            const sidebar = document.getElementById("sidebar");
            if (sidebar.classList.contains("w-64")) {
                mainContent.classList.remove("sidebar-collapsed");
            } else {
                mainContent.classList.add("sidebar-collapsed");
            }
        });

        // Check initial state
        const sidebar = document.getElementById("sidebar");
        if (sidebar && !sidebar.classList.contains("w-64")) {
            mainContent.classList.add("sidebar-collapsed");
        }
    });
    </script>
</body>
</html>