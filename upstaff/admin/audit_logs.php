<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Check admin authentication
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login/login.php");
    exit();
}

// --- Handle filters from GET ---
$search_user = isset($_GET['search_user']) ? trim($_GET['search_user']) : '';
$action_filter = isset($_GET['action']) ? trim($_GET['action']) : '';
$date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$date_to = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';
$page = isset($_GET['page']) ? max(1, intval($_GET['page'])) : 1;
$per_page = 20;

// Build the WHERE clause (always include employee role)
$where = ["u.role = 'employee'"]; // always filter employees only
$params = [];
$types = "";

if ($search_user !== '') {
    $where[] = "(u.firstname LIKE ? OR u.lastname LIKE ? OR u.username LIKE ?)";
    $like = "%$search_user%";
    $params[] = $like; $params[] = $like; $params[] = $like;
    $types .= "sss";
}
if ($action_filter !== '') {
    $where[] = "el.action = ?";
    $params[] = $action_filter;
    $types .= "s";
}
if ($date_from !== '') {
    $where[] = "DATE(el.timestamp) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if ($date_to !== '') {
    $where[] = "DATE(el.timestamp) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$where_sql = "WHERE " . implode(" AND ", $where);

// Count total records for pagination
$count_sql = "SELECT COUNT(*) as total 
            FROM employee_logs el
            INNER JOIN users u ON el.user_id = u.id
            $where_sql";
$stmt = $conn->prepare($count_sql);
if ($params) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$total_result = $stmt->get_result();
$total_rows = $total_result->fetch_assoc()['total'];
$total_pages = ceil($total_rows / $per_page);

// Fetch logs for current page
$offset = ($page - 1) * $per_page;
$query = "SELECT el.id, el.user_id, el.action, el.timestamp, el.ip_address,
                u.firstname, u.lastname, u.username
        FROM employee_logs el
        INNER JOIN users u ON el.user_id = u.id
        $where_sql
        ORDER BY el.timestamp DESC
        LIMIT ? OFFSET ?";

$stmt = $conn->prepare($query);
// Combine filter parameters with pagination parameters
$all_params = array_merge($params, [$per_page, $offset]);
$all_types = $types . "ii";

if (count($all_params) > 0) {
    $stmt->bind_param($all_types, ...$all_params);
} else {
    $stmt->bind_param("ii", $per_page, $offset);
}
$stmt->execute();
$logs = $stmt->get_result();

// Get distinct actions for filter dropdown (only from employee logs)
$actions_result = $conn->query("SELECT DISTINCT el.action 
                                FROM employee_logs el
                                INNER JOIN users u ON el.user_id = u.id
                                WHERE u.role = 'employee'
                                ORDER BY el.action");
$actions = [];
while ($row = $actions_result->fetch_assoc()) {
    $actions[] = $row['action'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Audit Logs - Upstaff</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <style>
        * {
            font-family: 'Inter', system-ui, -apple-system, sans-serif;
        }
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
        .filter-card {
            transition: all 0.2s ease;
        }
        .filter-card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }
        .action-badge {
            transition: transform 0.1s ease;
        }
        .action-badge:hover {
            transform: scale(1.05);
        }
        .table-row {
            transition: background-color 0.2s ease;
        }
        .pagination-btn {
            transition: all 0.2s ease;
        }
        .pagination-btn:hover:not(.disabled) {
            transform: translateY(-1px);
        }
    </style>
</head>
<body class="bg-gradient-to-br from-gray-50 to-gray-100">

    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content" id="mainContent">
        <div class="p-6 md:p-8">

            <!-- Page Header -->
            <div class="mb-8">
                <div class="flex items-center gap-3 mb-2">
                    <div class="p-2 bg-blue-100 rounded-xl">
                        <i class="fas fa-history text-blue-600 text-xl"></i>
                    </div>
                    <h1 class="text-3xl font-bold text-gray-800">Employee Audit Logs</h1>
                </div>
                <p class="text-gray-500 ml-12">Track all employee activities – logins, logouts, password changes, profile updates, and more.</p>
            </div>

            <!-- Auto-filter Card (no submit button) -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-6 mb-8 filter-card">
                <form id="filterForm" method="GET" class="space-y-5">
                    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                        <!-- Employee Search -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                <i class="fas fa-user mr-1 text-gray-400"></i> Employee
                            </label>
                            <div class="relative">
                                <i class="fas fa-search absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <input type="text" name="search_user" id="search_user" value="<?php echo htmlspecialchars($search_user); ?>" 
                                    class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                                    placeholder="Name or username">
                            </div>
                        </div>
                        <!-- Action Filter -->
                        <div class="relative">
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                <i class="fas fa-tag mr-1 text-gray-400"></i> Action
                            </label>
                            <div class="relative">
                                <i class="fas fa-list absolute left-3 top-1/2 -translate-y-1/2 text-gray-400 text-sm"></i>
                                <select name="action" id="action" class="w-full pl-9 pr-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500 appearance-none bg-white">
                                    <option value="">All actions</option>
                                    <?php foreach ($actions as $action): ?>
                                        <option value="<?php echo htmlspecialchars($action); ?>" <?php echo $action_filter === $action ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($action); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <i class="fas fa-chevron-down absolute right-3 top-1/2 -translate-y-1/2 text-gray-400 pointer-events-none text-sm"></i>
                            </div>
                        </div>
                        <!-- From Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                <i class="fas fa-calendar-alt mr-1 text-gray-400"></i> From Date
                            </label>
                            <input type="date" name="date_from" id="date_from" value="<?php echo htmlspecialchars($date_from); ?>" 
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <!-- To Date -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1.5">
                                <i class="fas fa-calendar-check mr-1 text-gray-400"></i> To Date
                            </label>
                            <input type="date" name="date_to" id="date_to" value="<?php echo htmlspecialchars($date_to); ?>" 
                                class="w-full px-3 py-2.5 border border-gray-200 rounded-xl focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                    </div>
                    <!-- Reset button only (no Apply button) -->
                    <div class="flex justify-end">
                        <a href="audit_logs.php" class="px-5 py-2.5 bg-gray-100 hover:bg-gray-200 text-gray-700 font-medium rounded-xl transition flex items-center gap-2">
                            <i class="fas fa-undo-alt"></i> Reset Filters
                        </a>
                    </div>
                </form>
            </div>

            <!-- Logs Table Card -->
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-100">
                        <thead class="bg-gray-50">
                            <tr>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Timestamp</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Employee</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Action</th>
                                <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">IP Address</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <?php if ($logs->num_rows === 0): ?>
                                <tr>
                                    <td colspan="4" class="px-6 py-16 text-center text-gray-400">
                                        <i class="fas fa-inbox text-5xl mb-3 block opacity-50"></i>
                                        <p class="text-sm">No logs found for the selected filters.</p>
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php while ($log = $logs->fetch_assoc()): ?>
                                    <tr class="table-row hover:bg-gray-50 transition">
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700 font-mono">
                                            <?php echo date('M d, Y H:i:s', strtotime($log['timestamp'])); ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php if ($log['firstname']): ?>
                                                <div class="flex items-center gap-2">
                                                    <div class="w-8 h-8 rounded-full bg-gradient-to-r from-blue-400 to-purple-500 flex items-center justify-center text-white text-xs font-bold">
                                                        <?php echo strtoupper(substr($log['firstname'], 0, 1)); ?>
                                                    </div>
                                                    <div>
                                                        <a href="approved_users.php?search=<?php echo urlencode($log['username']); ?>" class="text-blue-600 hover:underline font-medium">
                                                            <?php echo htmlspecialchars($log['firstname'] . ' ' . $log['lastname']); ?>
                                                        </a>
                                                        <div class="text-xs text-gray-400">@<?php echo htmlspecialchars($log['username']); ?></div>
                                                    </div>
                                                </div>
                                            <?php else: ?>
                                                <span class="text-gray-400">User ID <?php echo $log['user_id']; ?> (deleted)</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap">
                                            <?php
                                            $action_lower = strtolower($log['action']);
                                            if (strpos($action_lower, 'login') !== false) {
                                                $badge_class = 'bg-green-100 text-green-800 border-green-200';
                                            } elseif (strpos($action_lower, 'logout') !== false) {
                                                $badge_class = 'bg-gray-100 text-gray-700 border-gray-200';
                                            } elseif (strpos($action_lower, 'password') !== false || strpos($action_lower, 'reset') !== false) {
                                                $badge_class = 'bg-yellow-100 text-yellow-800 border-yellow-200';
                                            } elseif (strpos($action_lower, 'profile') !== false) {
                                                $badge_class = 'bg-blue-100 text-blue-800 border-blue-200';
                                            } else {
                                                $badge_class = 'bg-indigo-100 text-indigo-800 border-indigo-200';
                                            }
                                            ?>
                                            <span class="action-badge inline-flex items-center px-3 py-1 rounded-full text-xs font-medium border <?php echo $badge_class; ?>">
                                                <i class="fas fa-<?php echo strpos($action_lower, 'login') !== false ? 'sign-in-alt' : (strpos($action_lower, 'logout') !== false ? 'sign-out-alt' : (strpos($action_lower, 'password') !== false ? 'key' : 'edit')); ?> mr-1 text-xs"></i>
                                                <?php echo htmlspecialchars($log['action']); ?>
                                            </span>
                                        </td>
                                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 font-mono">
                                            <?php echo htmlspecialchars($log['ip_address']); ?>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Enhanced Pagination -->
                <?php if ($total_pages > 1): ?>
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-100 flex flex-col sm:flex-row items-center justify-between gap-4">
                    <div class="text-sm text-gray-600">
                        Showing <span class="font-medium"><?php echo min($total_rows, ($page-1)*$per_page + 1); ?></span> to
                        <span class="font-medium"><?php echo min($total_rows, $page*$per_page); ?></span> of
                        <span class="font-medium"><?php echo $total_rows; ?></span> results
                    </div>
                    <div class="flex items-center gap-1">
                        <?php if ($page > 1): ?>
                            <a href="?page=<?php echo $page-1; ?>&search_user=<?php echo urlencode($search_user); ?>&action=<?php echo urlencode($action_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                            class="pagination-btn px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-gray-600 hover:bg-gray-50 flex items-center gap-1">
                                <i class="fas fa-chevron-left text-xs"></i> Previous
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 border border-gray-200 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed flex items-center gap-1">
                                <i class="fas fa-chevron-left text-xs"></i> Previous
                            </span>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page-2); $i <= min($total_pages, $page+2); $i++): ?>
                            <a href="?page=<?php echo $i; ?>&search_user=<?php echo urlencode($search_user); ?>&action=<?php echo urlencode($action_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                            class="pagination-btn px-3 py-1.5 border <?php echo $i == $page ? 'border-blue-500 bg-blue-50 text-blue-600 font-medium' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'; ?> rounded-lg">
                                <?php echo $i; ?>
                            </a>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <a href="?page=<?php echo $page+1; ?>&search_user=<?php echo urlencode($search_user); ?>&action=<?php echo urlencode($action_filter); ?>&date_from=<?php echo urlencode($date_from); ?>&date_to=<?php echo urlencode($date_to); ?>" 
                            class="pagination-btn px-3 py-1.5 border border-gray-300 rounded-lg bg-white text-gray-600 hover:bg-gray-50 flex items-center gap-1">
                                Next <i class="fas fa-chevron-right text-xs"></i>
                            </a>
                        <?php else: ?>
                            <span class="px-3 py-1.5 border border-gray-200 rounded-lg bg-gray-100 text-gray-400 cursor-not-allowed flex items-center gap-1">
                                Next <i class="fas fa-chevron-right text-xs"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Auto-submit form when any filter changes (except reset button)
        const filterForm = document.getElementById('filterForm');
        const searchUser = document.getElementById('search_user');
        const actionSelect = document.getElementById('action');
        const dateFrom = document.getElementById('date_from');
        const dateTo = document.getElementById('date_to');

        let timeoutId = null;

        // Function to submit the form
        function submitForm() {
            // Remove page parameter to reset to page 1 when filters change
            const url = new URL(window.location.href);
            url.searchParams.delete('page');
            // Replace current URL with filter parameters but keep other GET params
            const formData = new FormData(filterForm);
            for (let [key, value] of formData.entries()) {
                if (value !== '') {
                    url.searchParams.set(key, value);
                } else {
                    url.searchParams.delete(key);
                }
            }
            window.location.href = url.toString();
        }

        // Debounced input for search field
        searchUser.addEventListener('input', function() {
            if (timeoutId) clearTimeout(timeoutId);
            timeoutId = setTimeout(submitForm, 500);
        });

        // Immediate submit for select and date fields
        actionSelect.addEventListener('change', submitForm);
        dateFrom.addEventListener('change', submitForm);
        dateTo.addEventListener('change', submitForm);

        // Reset button already links to the page without filters, so no extra JS needed.
    </script>

    <!-- Sidebar toggle handler -->
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