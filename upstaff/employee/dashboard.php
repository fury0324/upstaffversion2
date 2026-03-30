<?php
session_start();
require_once __DIR__ . '/../config/db.php';

// Authentication and data fetching (unchanged)...
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'employee') {
    header("Location: ../login/login.php");
    exit();
}
$user_id = $_SESSION['user_id'];

// Helper: check if table exists
function tableExists($conn, $table) {
    $result = $conn->query("SHOW TABLES LIKE '$table'");
    return $result && $result->num_rows > 0;
}

// Initialize stats
$stats = ['completed_modules' => 0, 'learning_hours' => 0, 'streak' => 0];
$courses = [];
$news = [];
$certifications = [];

$missingTables = [];
if (!tableExists($conn, 'user_courses')) $missingTables[] = 'user_courses';
if (!tableExists($conn, 'course_modules')) $missingTables[] = 'course_modules';
if (!tableExists($conn, 'courses')) $missingTables[] = 'courses';
if (!tableExists($conn, 'academy_news')) $missingTables[] = 'academy_news';
if (!tableExists($conn, 'user_certifications')) $missingTables[] = 'user_certifications';
if (!tableExists($conn, 'certifications')) $missingTables[] = 'certifications';

// Fetch stats (only if required tables exist)
if (!in_array('user_courses', $missingTables) && !in_array('course_modules', $missingTables)) {
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT cm.id) as completed 
                            FROM user_courses uc
                            JOIN course_modules cm ON uc.course_id = cm.course_id
                            WHERE uc.user_id = ? AND uc.status = 'completed'");
    if ($stmt !== false) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['completed_modules'] = $result->fetch_assoc()['completed'] ?? 0;
    }
}

if (!in_array('user_courses', $missingTables) && !in_array('courses', $missingTables)) {
    $stmt = $conn->prepare("SELECT SUM(duration_hours) as total_hours 
                             FROM user_courses uc
                             JOIN courses c ON uc.course_id = c.id
                             WHERE uc.user_id = ? AND uc.status = 'completed'");
    if ($stmt !== false) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['learning_hours'] = round($result->fetch_assoc()['total_hours'] ?? 0, 1);
    }
}

if (tableExists($conn, 'user_logs')) {
    $stmt = $conn->prepare("SELECT DATEDIFF(CURDATE(), MAX(login_date)) as streak 
                             FROM user_logs 
                             WHERE user_id = ? AND action = 'login'");
    if ($stmt !== false) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        $stats['streak'] = $result->fetch_assoc()['streak'] ?? 0;
    }
}

// General courses
if (!in_array('courses', $missingTables)) {
    $stmt = $conn->prepare("SELECT c.id, c.title, c.description, c.thumbnail, 
                                   uc.progress, uc.status, uc.completed_at,
                                   (SELECT COUNT(*) FROM course_modules WHERE course_id = c.id) as total_modules
                            FROM courses c
                            LEFT JOIN user_courses uc ON c.id = uc.course_id AND uc.user_id = ?
                            WHERE c.type = 'general' AND c.status = 'published'
                            ORDER BY c.id");
    if ($stmt !== false) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $row['progress'] = $row['progress'] ?? 0;
            $row['status'] = $row['status'] ?? 'not_started';
            $courses[] = $row;
        }
    }
}

// Academy News
if (!in_array('academy_news', $missingTables)) {
    $news_result = $conn->query("SELECT title, summary, created_at, category, image 
                                  FROM academy_news 
                                  ORDER BY created_at DESC LIMIT 2");
    if ($news_result) {
        while ($row = $news_result->fetch_assoc()) {
            $news[] = $row;
        }
    }
}

// Certifications
if (!in_array('user_certifications', $missingTables) && !in_array('certifications', $missingTables)) {
    $stmt = $conn->prepare("SELECT c.name, c.image, uc.earned_at, uc.expiry_date 
                             FROM user_certifications uc
                             JOIN certifications c ON uc.certification_id = c.id
                             WHERE uc.user_id = ? AND uc.status = 'active'
                             ORDER BY uc.earned_at DESC");
    if ($stmt !== false) {
        $stmt->bind_param("i", $user_id);
        $stmt->execute();
        $result = $stmt->get_result();
        while ($row = $result->fetch_assoc()) {
            $certifications[] = $row;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employee Dashboard - UpStaff Academy</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"/>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * {
            font-family: 'Inter', sans-serif;
        }
        
        /* MAIN CONTENT OFFSET */
        .main-content {
            margin-left: 15rem; 
            width: calc(100% - 15rem);
            min-height: 100vh;
            transition: margin-left 0.3s ease, width 0.3s ease;
        }
        .main-content.sidebar-collapsed {
            margin-left: 5rem;
            width: calc(100% - 5rem);
        }
        
        /* Enhanced stat cards */
        .stat-card {
            transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            backdrop-filter: blur(10px);
        }
        .stat-card:hover {
            transform: translateY(-8px) scale(1.02);
            box-shadow: 0 25px 40px -12px rgba(0, 0, 0, 0.2);
        }
        
        /* Course card hover effect */
        .course-card {
            transition: all 0.3s ease;
        }
        .course-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -12px rgba(0, 0, 0, 0.15);
        }
        
        /* Progress bar animation */
        .progress-bar {
            transition: width 0.6s ease-out;
        }
        
        /* Glass card effect */
        .glass-card {
            backdrop-filter: blur(12px);
            background: rgba(255, 255, 255, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }
        .glass-card:hover {
            background: rgba(255, 255, 255, 0.5);
            transform: translateY(-2px);
        }
        
        /* Fade in animation */
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .fade-in {
            animation: fadeInUp 0.6s ease-out forwards;
        }
        
        /* Locked section overlay */
        .locked-overlay {
            backdrop-filter: blur(2px);
        }
        
        @media (max-width: 768px) {
            .main-content {
                margin-left: 0 !important;
                width: 100% !important;
            }
        }
    </style>
</head>
<body class="bg-gradient-to-br from-slate-50 to-gray-100">
    <?php include __DIR__ . '/sidebar.php'; ?>
    <div class="main-content" id="mainContent">
        <?php include __DIR__ . '/header.php'; ?>
        
        <div class="px-8 md:px-9 py-8">
            <div class="max-w-8xl mx-auto">
                <!-- Welcome Section with Animation -->
                <div class="mb-12 fade-in">
                    <div class="flex flex-col md:flex-row md:items-end justify-between gap-6">
                        <div>
                            <h1 class="text-4xl md:text-5xl font-extrabold text-gray-900 tracking-tight">
                                Welcome back, <span class="bg-gradient-to-r from-blue-600 to-purple-600 bg-clip-text text-transparent"><?php echo htmlspecialchars($_SESSION['firstname']); ?></span>!
                            </h1>
                            <p class="mt-3 text-gray-600 text-lg max-w-2xl">Continue your learning journey. You're making great progress!</p>
                        </div>
                    </div>
                </div>

                <!-- Stats Grid (Enhanced) -->
                <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-6 mb-12 fade-in">
                    <div class="stat-card bg-gradient-to-br from-blue-500 to-blue-600 text-white p-6 rounded-2xl shadow-xl">
                        <div class="flex items-center justify-between mb-3">
                            <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                                <i class="fas fa-book-open text-2xl"></i>
                            </div>
                            <span class="text-xs bg-white/20 px-3 py-1 rounded-full">modules</span>
                        </div>
                        <h3 class="text-sm font-medium opacity-90 mb-1">Completed Modules</h3>
                        <div class="flex items-end justify-between">
                            <h2 class="text-4xl font-bold"><?php echo $stats['completed_modules']; ?></h2>
                            <span class="text-sm opacity-75">completed</span>
                        </div>
                        <div class="mt-3 h-1 bg-white/20 rounded-full">
                            <div class="h-full bg-white rounded-full" style="width: <?php echo min(100, $stats['completed_modules'] * 5); ?>%"></div>
                        </div>
                    </div>

                    <div class="stat-card bg-gradient-to-br from-green-500 to-emerald-600 text-white p-6 rounded-2xl shadow-xl">
                        <div class="flex items-center justify-between mb-3">
                            <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                                <i class="fas fa-clock text-2xl"></i>
                            </div>
                            <span class="text-xs bg-white/20 px-3 py-1 rounded-full">total</span>
                        </div>
                        <h3 class="text-sm font-medium opacity-90 mb-1">Learning Hours</h3>
                        <div class="flex items-end justify-between">
                            <h2 class="text-4xl font-bold"><?php echo $stats['learning_hours']; ?></h2>
                            <span class="text-sm opacity-75">hours</span>
                        </div>
                    </div>

                    <div class="stat-card bg-gradient-to-br from-purple-500 to-pink-500 text-white p-6 rounded-2xl shadow-xl">
                        <div class="flex items-center justify-between mb-3">
                            <div class="bg-white/20 p-3 rounded-xl backdrop-blur-sm">
                                <i class="fas fa-fire text-2xl"></i>
                            </div>
                            <span class="text-xs bg-white/20 px-3 py-1 rounded-full">streak</span>
                        </div>
                        <h3 class="text-sm font-medium opacity-90 mb-1">Active Streak</h3>
                        <div class="flex items-end justify-between">
                            <h2 class="text-4xl font-bold"><?php echo $stats['streak']; ?></h2>
                            <span class="text-sm opacity-75">days</span>
                        </div>
                    </div>
                </div>

                <!-- Main Grid -->
                <div class="grid grid-cols-12 gap-8">
                    <!-- Left Column: Courses -->
                    <div class="col-span-12 lg:col-span-8 space-y-10">
                        <!-- General Courses Section -->
                        <div class="fade-in">
                            <div class="flex items-center justify-between mb-6">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-blue-100 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-graduation-cap text-blue-600 text-sm"></i>
                                    </div>
                                    <h2 class="text-2xl font-bold text-gray-800">General Courses</h2>
                                </div>
                                <a href="#" class="text-blue-600 hover:text-blue-700 font-medium text-sm flex items-center gap-1">
                                    View all <i class="fas fa-arrow-right text-xs"></i>
                                </a>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                                <?php if (empty($courses)): ?>
                                    <div class="col-span-2 text-center py-12 text-gray-500 bg-white/50 rounded-2xl">
                                        <i class="fas fa-book-open text-4xl mb-3 opacity-50"></i>
                                        <p>No general courses available yet.</p>
                                    </div>
                                <?php else: ?>
                                    <?php foreach ($courses as $course): ?>
                                    <div class="course-card bg-white rounded-2xl overflow-hidden shadow-md hover:shadow-xl transition-all">
                                        <div class="relative h-40 overflow-hidden">
                                            <img class="w-full h-full object-cover transition-transform duration-500 group-hover:scale-110" 
                                                 src="<?php echo htmlspecialchars($course['thumbnail'] ?? 'https://placehold.co/400x200?text=Course'); ?>" 
                                                 alt="<?php echo htmlspecialchars($course['title']); ?>">
                                            <?php if ($course['status'] == 'completed'): ?>
                                                <div class="absolute top-3 left-3 bg-green-500 text-white px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1">
                                                    <i class="fas fa-check-circle text-xs"></i> Completed
                                                </div>
                                            <?php elseif ($course['status'] == 'in_progress'): ?>
                                                <div class="absolute top-3 left-3 bg-blue-600 text-white px-3 py-1 rounded-full text-xs font-semibold flex items-center gap-1">
                                                    <i class="fas fa-play text-xs"></i> In Progress
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="p-5">
                                            <h3 class="font-bold text-lg text-gray-800 mb-2"><?php echo htmlspecialchars($course['title']); ?></h3>
                                            <p class="text-sm text-gray-500 mb-4 line-clamp-2"><?php echo htmlspecialchars($course['description']); ?></p>
                                            
                                            <?php if ($course['status'] == 'in_progress'): ?>
                                                <div class="space-y-2">
                                    <div class="flex justify-between text-xs text-gray-600">
                                        <span>Progress</span>
                                        <span><?php echo $course['progress']; ?>%</span>
                                    </div>
                                    <div class="w-full bg-gray-200 rounded-full h-2">
                                        <div class="progress-bar bg-blue-600 h-2 rounded-full" style="width: <?php echo $course['progress']; ?>%"></div>
                                    </div>
                                    <div class="flex justify-between text-xs text-gray-500">
                                        <span><?php echo $course['total_modules'] - round(($course['total_modules'] * $course['progress'])/100); ?> modules left</span>
                                        <a href="continue_course.php?id=<?php echo $course['id']; ?>" class="text-blue-600 font-medium hover:underline">Continue →</a>
                                    </div>
                                </div>
                                            <?php elseif ($course['status'] == 'not_started'): ?>
                                                <a href="start_course.php?id=<?php echo $course['id']; ?>" class="inline-flex items-center gap-2 text-blue-600 font-medium hover:text-blue-700 transition">
                                                    Start Course <i class="fas fa-arrow-right text-xs"></i>
                                                </a>
                                            <?php elseif ($course['status'] == 'completed'): ?>
                                                <div class="flex items-center gap-2 text-green-600">
                                                    <i class="fas fa-check-circle"></i>
                                                    <span class="text-sm font-medium">Completed on <?php echo date('M d, Y', strtotime($course['completed_at'])); ?></span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Upskilling Tracks (Locked) -->
                        <div class="fade-in">
                            <div class="flex items-center justify-between mb-2">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-gray-200 rounded-lg flex items-center justify-center">
                                        <i class="fas fa-chart-line text-gray-500 text-sm"></i>
                                    </div>
                                    <h2 class="text-2xl font-bold text-gray-800">Upskilling Tracks</h2>
                                </div>
                                <span class="bg-gray-200 text-gray-600 text-xs font-semibold px-3 py-1 rounded-full">Prerequisites Required</span>
                            </div>

                            <div class="mb-6 bg-blue-50 border border-blue-200 rounded-xl p-4 flex items-start gap-3">
                                <i class="fas fa-info-circle text-blue-500 text-xl mt-0.5"></i>
                                <p class="text-sm text-gray-700">
                                    <span class="font-semibold">Access Restricted:</span> Complete all General Courses to unlock specialized Upskilling tracks and technical certifications.
                                </p>
                            </div>

                            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 opacity-60 relative">
                                <div class="absolute inset-0 bg-gray-100/40 rounded-2xl z-10 cursor-not-allowed backdrop-blur-[1px]"></div>
                                <!-- Locked Card 1 -->
                                <div class="bg-white rounded-2xl overflow-hidden shadow-md border border-gray-200">
                                    <div class="h-40 bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center">
                                        <i class="fas fa-database text-white text-5xl opacity-70"></i>
                                    </div>
                                    <div class="p-5">
                                        <h3 class="font-bold text-lg text-gray-800 mb-2">Advanced Data Architecture</h3>
                                        <p class="text-sm text-gray-500 mb-4">Deep dive into scalable database structures and cloud storage optimization.</p>
                                        <div class="flex items-center gap-2 text-gray-500">
                                            <i class="fas fa-lock text-sm"></i>
                                            <span class="text-xs font-medium uppercase tracking-wide">Locked - Level 4</span>
                                        </div>
                                    </div>
                                </div>
                                <!-- Locked Card 2 -->
                                <div class="bg-white rounded-2xl overflow-hidden shadow-md border border-gray-200">
                                    <div class="h-40 bg-gradient-to-br from-gray-700 to-gray-900 flex items-center justify-center">
                                        <i class="fas fa-cloud-upload-alt text-white text-5xl opacity-70"></i>
                                    </div>
                                    <div class="p-5">
                                        <h3 class="font-bold text-lg text-gray-800 mb-2">Cloud Maestro</h3>
                                        <p class="text-sm text-gray-500 mb-4">Mastering AWS and Azure deployment pipelines for global infrastructure.</p>
                                        <div class="flex items-center gap-2 text-gray-500">
                                            <i class="fas fa-lock text-sm"></i>
                                            <span class="text-xs font-medium uppercase tracking-wide">Locked - Level 5</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Right Column: Widgets (Enhanced) -->
                    <aside class="col-span-12 lg:col-span-4 space-y-8 fade-in">
                        <!-- Academy News -->
                        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
                            <div class="flex items-center justify-between mb-5">
                                <h3 class="font-bold text-lg text-gray-800 flex items-center gap-2">
                                    <i class="fas fa-newspaper text-blue-500"></i>
                                    Academy News
                                </h3>
                                <button class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-ellipsis-h"></i>
                                </button>
                            </div>
                            <div class="space-y-5">
                                <?php if (empty($news)): ?>
                                    <p class="text-gray-500 text-center py-8">No recent news.</p>
                                <?php else: ?>
                                    <?php foreach ($news as $item): ?>
                                    <div class="flex gap-4 group cursor-pointer">
                                        <div class="w-16 h-16 rounded-xl overflow-hidden flex-shrink-0 bg-gray-100">
                                            <img class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-300" 
                                                 src="<?php echo htmlspecialchars($item['image'] ?? 'https://placehold.co/80x80?text=News'); ?>" 
                                                 alt="<?php echo htmlspecialchars($item['title']); ?>">
                                        </div>
                                        <div class="flex-1">
                                            <p class="text-xs font-semibold text-blue-600 uppercase tracking-wider mb-1"><?php echo htmlspecialchars($item['category'] ?? 'Update'); ?></p>
                                            <h4 class="font-semibold text-gray-800 group-hover:text-blue-600 transition-colors"><?php echo htmlspecialchars($item['title']); ?></h4>
                                            <p class="text-xs text-gray-500 mt-1"><?php echo date('M d, H:i', strtotime($item['created_at'])); ?></p>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </div>
                            <button class="w-full mt-6 py-2 text-sm font-medium text-blue-600 bg-blue-50 rounded-xl hover:bg-blue-100 transition-colors">
                                Read All Updates
                            </button>
                        </div>

                        <!-- Knowledge Glass (Glassmorphism) -->
                        <div class="glass-card rounded-2xl p-6 shadow-lg">
                            <div class="flex items-center gap-2 mb-4">
                                <i class="fas fa-lightbulb text-yellow-500 text-xl"></i>
                                <span class="text-xs font-bold uppercase tracking-wider text-gray-600">Knowledge Glass</span>
                            </div>
                            <p class="text-gray-700 leading-relaxed italic">"The fastest path to Senior Engineering is completing the Data Architecture track. Most employees finish the prerequisites in 3 weeks."</p>
                        </div>

                        <!-- Certifications -->
                        <div class="bg-white rounded-2xl shadow-md p-6 border border-gray-100">
                            <h3 class="font-bold text-lg text-gray-800 mb-5 flex items-center gap-2">
                                <i class="fas fa-certificate text-purple-500"></i>
                                Your Certifications
                            </h3>
                            <div class="space-y-4">
                                <?php if (count($certifications) > 0): ?>
                                    <?php foreach ($certifications as $cert): ?>
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl hover:bg-gray-100 transition">
                                        <div class="flex items-center gap-3">
                                            <div class="w-10 h-10 bg-gradient-to-br from-blue-100 to-purple-100 rounded-full flex items-center justify-center">
                                                <i class="fas fa-award text-purple-600 text-sm"></i>
                                            </div>
                                            <div>
                                                <p class="font-semibold text-gray-800"><?php echo htmlspecialchars($cert['name']); ?></p>
                                                <p class="text-xs text-gray-500">Valid thru <?php echo date('Y', strtotime($cert['expiry_date'])); ?></p>
                                            </div>
                                        </div>
                                        <i class="fas fa-download text-gray-400 hover:text-blue-500 cursor-pointer"></i>
                                    </div>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <p class="text-gray-500 text-center py-4">No certifications earned yet.</p>
                                <?php endif; ?>
                            </div>
                            <button class="w-full mt-6 text-sm text-gray-500 hover:text-blue-600 font-medium transition-colors">
                                Explore All Badges →
                            </button>
                        </div>
                    </aside>
                </div>
            </div>
        </div>
    </div>

    <!-- ==================== MODALS ==================== -->
<!-- Profile Information Modal (updated) -->
<div id="profileModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
    <div class="fixed inset-0 overflow-y-auto">
        <div class="flex min-h-full items-center justify-center p-4">
            <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-2xl">
                <div class="relative bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-5">
                    <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                        <i class="fas fa-user-edit"></i> Profile Information
                    </h3>
                    <button onclick="closeProfileModal()" class="absolute right-4 top-4 text-white/80 hover:text-white">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                        </svg>
                    </button>
                </div>
                <div class="px-6 py-5 bg-gray-50 max-h-[70vh] overflow-y-auto">
                    <form id="profileForm">
                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">First Name *</label>
                                <input type="text" name="firstname" id="profile_firstname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Last Name *</label>
                                <input type="text" name="lastname" id="profile_lastname" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Email *</label>
                                <input type="email" name="email" id="profile_email" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <!-- New Position field -->
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Position / Job Title</label>
                                <input type="text" name="position" id="profile_position" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-blue-500 focus:border-blue-500">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Phone</label>
                                <input type="text" name="phone" id="profile_phone" class="w-full px-4 py-2 border border-gray-300 rounded-lg">
                            </div>
                            <div>
                                <label class="block text-sm font-medium text-gray-700 mb-1">Address</label>
                                <textarea name="address" id="profile_address" rows="2" class="w-full px-4 py-2 border border-gray-300 rounded-lg"></textarea>
                            </div>
                            <div id="profileMessage" class="hidden text-sm"></div>
                            <div class="flex justify-end gap-3 pt-4">
                                <button type="button" onclick="closeProfileModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Cancel</button>
                                <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700">Save Changes</button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

    <!-- Security Modal (Change Password) -->
    <div id="securityModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
        <div class="fixed inset-0 overflow-y-auto">
            <div class="flex min-h-full items-center justify-center p-4">
                <div class="relative transform overflow-hidden rounded-xl bg-white shadow-2xl transition-all sm:w-full sm:max-w-md">
                    <div class="relative bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-5">
                        <h3 class="text-xl font-semibold text-white flex items-center gap-2">
                            <i class="fas fa-key"></i> Change Password
                        </h3>
                        <button onclick="closeSecurityModal()" class="absolute right-4 top-4 text-white/80 hover:text-white">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
                            </svg>
                        </button>
                    </div>
                    <div class="px-6 py-5 bg-gray-50">
                        <form id="securityForm">
                            <div class="space-y-4">
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Current Password</label>
                                    <input type="password" name="current_password" id="current_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" required>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">New Password</label>
                                    <input type="password" name="new_password" id="new_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" required>
                                    <p class="text-xs text-gray-500 mt-1">Minimum 6 characters</p>
                                </div>
                                <div>
                                    <label class="block text-sm font-medium text-gray-700 mb-1">Confirm New Password</label>
                                    <input type="password" name="confirm_password" id="confirm_password" class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-purple-500 focus:border-purple-500" required>
                                </div>
                                <div id="securityMessage" class="hidden text-sm"></div>
                                <div class="flex justify-end gap-3 pt-4">
                                    <button type="button" onclick="closeSecurityModal()" class="px-4 py-2 bg-gray-200 text-gray-800 rounded-lg hover:bg-gray-300">Cancel</button>
                                    <button type="submit" class="px-4 py-2 bg-purple-600 text-white rounded-lg hover:bg-purple-700">Update Password</button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal JavaScript -->
    <script>
        // --- Profile Modal ---
        function openProfileModal() {
            const modal = document.getElementById('profileModal');
            const messageDiv = document.getElementById('profileMessage');
            messageDiv.classList.add('hidden');
            modal.classList.remove('hidden');
            
            fetch('get_user_profile.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        document.getElementById('profile_firstname').value = data.user.firstname;
                        document.getElementById('profile_lastname').value = data.user.lastname;
                        document.getElementById('profile_email').value = data.user.email;
                        document.getElementById('profile_phone').value = data.user.phone || '';
                        document.getElementById('profile_address').value = data.user.address || '';
                    } else {
                        alert('Error loading profile data');
                        closeProfileModal();
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Network error');
                });
        }

        function closeProfileModal() {
            document.getElementById('profileModal').classList.add('hidden');
        }

        // --- Security Modal ---
        function openSecurityModal() {
            document.getElementById('securityModal').classList.remove('hidden');
            document.getElementById('securityMessage').classList.add('hidden');
            document.getElementById('securityForm').reset();
        }

        function closeSecurityModal() {
            document.getElementById('securityModal').classList.add('hidden');
        }

        // --- Profile Form Submit ---
        document.getElementById('profileForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Saving...';
            
            fetch('update_profile.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                const messageDiv = document.getElementById('profileMessage');
                if (result.success) {
                    messageDiv.className = 'mt-3 p-3 bg-green-100 text-green-700 rounded-lg text-sm';
                    messageDiv.innerHTML = 'Profile updated successfully!';
                    messageDiv.classList.remove('hidden');
                    // Update the name displayed in header dropdown
                    const headerName = document.querySelector('#profileDropdown .font-medium');
                    if (headerName) headerName.textContent = data.firstname;
                } else {
                    messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
                    messageDiv.innerHTML = result.message || 'Update failed';
                    messageDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                const messageDiv = document.getElementById('profileMessage');
                messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
                messageDiv.innerHTML = 'Network error. Please try again.';
                messageDiv.classList.remove('hidden');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });

        // --- Security Form Submit ---
        document.getElementById('securityForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const formData = new FormData(this);
            const data = Object.fromEntries(formData.entries());
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Updating...';
            
            if (data.new_password !== data.confirm_password) {
                const messageDiv = document.getElementById('securityMessage');
                messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
                messageDiv.innerHTML = 'New password and confirmation do not match.';
                messageDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }
            if (data.new_password.length < 6) {
                const messageDiv = document.getElementById('securityMessage');
                messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
                messageDiv.innerHTML = 'Password must be at least 6 characters.';
                messageDiv.classList.remove('hidden');
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
                return;
            }
            
            fetch('change_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
            .then(response => response.json())
            .then(result => {
                const messageDiv = document.getElementById('securityMessage');
                if (result.success) {
                    messageDiv.className = 'mt-3 p-3 bg-green-100 text-green-700 rounded-lg text-sm';
                    messageDiv.innerHTML = 'Password changed successfully!';
                    messageDiv.classList.remove('hidden');
                    setTimeout(() => closeSecurityModal(), 1500);
                } else {
                    messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
                    messageDiv.innerHTML = result.message || 'Password change failed';
                    messageDiv.classList.remove('hidden');
                }
            })
            .catch(err => {
                const messageDiv = document.getElementById('securityMessage');
                messageDiv.className = 'mt-3 p-3 bg-red-100 text-red-700 rounded-lg text-sm';
                messageDiv.innerHTML = 'Network error. Please try again.';
                messageDiv.classList.remove('hidden');
            })
            .finally(() => {
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalText;
            });
        });

        // Close modals on escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeProfileModal();
                closeSecurityModal();
            }
        });
        // Close modals on backdrop click
        document.getElementById('profileModal').addEventListener('click', function(e) {
            if (e.target === this) closeProfileModal();
        });
        document.getElementById('securityModal').addEventListener('click', function(e) {
            if (e.target === this) closeSecurityModal();
        });
    </script>

    <!-- Sidebar toggle handler (already present) -->
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