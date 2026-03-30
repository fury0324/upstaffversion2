<!-- SIDEBAR - ENHANCED DROPDOWNS WITH SEPARATORS & PROGRESS BAR LINE -->
<aside id="sidebar" class="fixed top-0 left-0 w-64 h-screen bg-gradient-to-br from-gray-900 to-gray-800 text-white flex flex-col shadow-2xl transition-all duration-300 z-50">

    <div class="p-5 border-b border-gray-700/50 flex items-center justify-between">
        <div class="flex items-center space-x-3 logo-area">
            <img src="../landingpage/assets/logo.png" class="w-10 h-10 rounded-lg shadow-md" alt="Logo">
            <span class="text-lg font-bold tracking-tight sidebar-text" style="font-family: monospace;">𝚞𝚙𝚜𝚝𝚊𝚏𝚏 </span>
        </div>
        <button id="collapseBtn" class="text-gray-300 hover:text-white transition-colors duration-200 focus:outline-none text-2xl">
            <i class="fas fa-bars"></i> <!-- unchanged icon -->
        </button>
    </div>

    <nav class="flex-1 py-6 px-3 space-y-1 overflow-y-auto">
        <!-- Dashboard -->
        <a href="../admin/dashboard.php" 
           class="nav-link flex items-center px-4 py-3 rounded-xl transition-all duration-200 group 
                  <?php echo basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : ''; ?>">
            <i class="fas fa-tachometer-alt w-6 text-lg"></i>
            <span class="ml-3 sidebar-text">Dashboard</span>
        </a>

        <!-- User Management Dropdown -->
        <div class="relative">
            <button id="userMenuBtn"
                    class="nav-link flex items-center justify-between w-full px-4 py-3 rounded-xl transition-all duration-200 group
                           <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_approval.php' || basename($_SERVER['PHP_SELF']) == 'approved_users.php' || basename($_SERVER['PHP_SELF']) == 'rejected_user.php') ? 'active' : ''; ?>">
                <div class="flex items-center">
                    <i class="fas fa-users w-6 text-lg"></i>
                    <span class="ml-3 sidebar-text">User Management</span>
                </div>
                <i id="userArrow" class="fas fa-chevron-down text-xs transition-transform duration-300 sidebar-text 
                   <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_approval.php' || basename($_SERVER['PHP_SELF']) == 'approved_users.php' || basename($_SERVER['PHP_SELF']) == 'rejected_user.php') ? 'rotate-180' : ''; ?>"></i>
            </button>

            <div id="userDropdown" class="mt-2 ml-8 space-y-0 <?php echo (basename($_SERVER['PHP_SELF']) == 'admin_approval.php' || basename($_SERVER['PHP_SELF']) == 'approved_users.php' || basename($_SERVER['PHP_SELF']) == 'rejected_user.php') ? '' : 'hidden'; ?>">
                <a href="../login/admin_approval.php"
                   class="dropdown-link flex items-center px-4 py-2.5 rounded-lg text-sm transition-all duration-200
                          <?php echo basename($_SERVER['PHP_SELF']) == 'admin_approval.php' ? 'active-progress text-white' : 'text-gray-300 hover:text-white hover:bg-white/5'; ?>">
                    <i class="fas fa-clock w-5 text-xs mr-3"></i>
                    <span>Pending Approval</span>
                </a>
                <div class="border-t border-gray-700/30 my-1"></div> <!-- separator -->
                <a href="../admin/approved_users.php"
                class="dropdown-link flex items-center px-4 py-2.5 rounded-lg text-sm transition-all duration-200
                        <?php echo basename($_SERVER['PHP_SELF']) == 'approved_users.php' ? 'active-progress text-white' : 'text-gray-300 hover:text-white hover:bg-white/5'; ?>">
                    <i class="fas fa-check-circle w-5 text-xs mr-3"></i>
                    <span>Approved Users</span>
                </a>
                <div class="border-t border-gray-700/30 my-1"></div> <!-- separator -->
                <a href="../admin/rejected_user.php"
                
                class="dropdown-link flex items-center px-4 py-2.5 rounded-lg text-sm transition-all duration-200
                        <?php echo basename($_SERVER['PHP_SELF']) == 'rejected_user.php' ? 'active-progress text-white' : 'text-gray-300 hover:text-white hover:bg-white/5'; ?>">
                    <i class="fas fa-ban w-5 text-xs mr-3"></i>
                    <span>Rejected Users</span>
                </a>
            </div>
        </div>

        <!-- Courses -->
        <a href="#" class="nav-link flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
            <i class="fas fa-book w-6 text-lg"></i>
            <span class="ml-3 sidebar-text">Courses</span>
        </a>

        <!-- Quizzes -->
        <a href="#" class="nav-link flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
            <i class="fas fa-question-circle w-6 text-lg"></i>
            <span class="ml-3 sidebar-text">Quizzes</span>
        </a>

        <!-- Reports -->
        <a href="#" class="nav-link flex items-center px-4 py-3 rounded-xl transition-all duration-200 group">
            <i class="fas fa-chart-line w-6 text-lg"></i>
            <span class="ml-3 sidebar-text">Reports</span>
        </a>

        <!-- Settings Dropdown (with Audit Logs) -->
        <div class="relative">
            <button id="settingsBtn"
                    class="nav-link flex items-center justify-between w-full px-4 py-3 rounded-xl transition-all duration-200 group">
                <div class="flex items-center">
                    <i class="fas fa-cog w-6 text-lg"></i>
                    <span class="ml-3 sidebar-text">Settings</span>
                </div>
                <i id="settingsArrow" class="fas fa-chevron-down text-xs transition-transform duration-300 sidebar-text"></i>
            </button>
            <div id="settingsDropdown" class="mt-2 ml-8 space-y-0 hidden">
                <a href="../admin/audit_logs.php" 
                class="dropdown-link flex items-center px-4 py-2.5 rounded-lg text-sm transition-all duration-200
                        <?php echo basename($_SERVER['PHP_SELF']) == 'audit_logs.php' ? 'active-progress text-white' : 'text-gray-300 hover:text-white hover:bg-white/5'; ?>">
                    <i class="fas fa-history w-5 text-xs mr-3"></i>
                    <span>Audit Logs</span>
                </a>
                <div class="border-t border-gray-700/30 my-1"></div> <!-- separator -->
                <a href="#" class="dropdown-link flex items-center px-4 py-2.5 rounded-lg text-sm text-gray-300 hover:text-white hover:bg-white/5 transition-all duration-200">
                    <i class="fas fa-user-cog w-5 text-xs mr-3"></i>
                    <span>Account Settings</span>
                </a>
            </div>
        </div>

        <!-- Logout -->
        <a href="../logout.php" class="nav-link flex items-center px-4 py-3 rounded-xl transition-all duration-200 group mt-6 text-red-400 hover:text-red-300 hover:bg-red-500/10">
            <i class="fas fa-sign-out-alt w-6 text-lg"></i>
            <span class="ml-3 sidebar-text">Logout</span>
        </a>
    </nav>

    <!-- User Profile Section (Bottom) -->
    <div class="border-t border-gray-700/50 p-4">
        <div class="flex items-center space-x-3">
            <div class="w-9 h-9 rounded-full bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                <?php echo strtoupper(substr($_SESSION['firstname'] ?? 'A', 0, 1)); ?>
            </div>
            <div class="sidebar-text">
                <p class="text-sm font-medium truncate"><?php echo htmlspecialchars($_SESSION['firstname'] ?? 'Admin'); ?></p>
                <p class="text-xs text-gray-400 truncate">Administrator</p>
            </div>
        </div>
    </div>
</aside>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@700;800;900&display=swap" rel="stylesheet">
<style>
/* Additional sidebar styles */
#sidebar {
    position: fixed;
    top: 0;
    left: 0;
    height: 100vh;
    width: 16rem;
    z-index: 50;
    overflow-y: auto;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    scrollbar-width: thin;
}

#sidebar.w-20 {
    width: 5rem;
}

/* Hide text when collapsed */
#sidebar.w-20 .sidebar-text {
    display: none;
}

#sidebar.w-20 .logo-area {
    display: none;
}

#sidebar.w-20 .nav-link i {
    margin-right: 0;
    width: 100%;
    text-align: center;
}

#sidebar.w-20 .dropdown-link span {
    display: none;
}

#sidebar.w-20 .dropdown-link i {
    margin-right: 0;
}

#sidebar.w-20 #userDropdown,
#sidebar.w-20 #settingsDropdown {
    display: none !important;
}

/* Active link styling for main nav */
.nav-link {
    position: relative;
    font-weight: 500;
    letter-spacing: 0.01em;
}

.nav-link.active {
    background: rgba(255, 255, 255, 0.1);
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.nav-link.active::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 3px;
    background: #3b82f6;
    border-radius: 0 2px 2px 0;
}

.nav-link:not(.active):hover {
    background: rgba(255, 255, 255, 0.05);
    transform: translateX(2px);
}

/* Dropdown link enhancements */
.dropdown-link {
    transition: all 0.2s ease;
    margin: 0;
    border-radius: 0.5rem;
    position: relative;
    background: transparent;
}

.dropdown-link i {
    width: 1.25rem;
    text-align: center;
}

/* Active dropdown link with progress bar line */
.dropdown-link.active-progress {
    background: rgba(255, 255, 255, 0.1);
    color: white;
}

.dropdown-link.active-progress::before {
    content: '';
    position: absolute;
    left: 0;
    top: 0;
    bottom: 0;
    width: 4px;
    background: linear-gradient(180deg, #3b82f6, #8b5cf6);
    border-radius: 0 4px 4px 0;
    box-shadow: 0 0 8px rgba(59,130,246,0.5);
    transition: all 0.2s ease;
}

/* Separator lines */
#userDropdown .border-t,
#settingsDropdown .border-t {
    opacity: 0.5;
    margin: 0.25rem 0;
}

/* Scrollbar styling */
#sidebar::-webkit-scrollbar {
    width: 5px;
}
#sidebar::-webkit-scrollbar-track {
    background: rgba(255, 255, 255, 0.05);
    border-radius: 10px;
}
#sidebar::-webkit-scrollbar-thumb {
    background: #4b5563;
    border-radius: 10px;
}
#sidebar::-webkit-scrollbar-thumb:hover {
    background: #6b7280;
}

/* Mobile responsive */
@media (max-width: 768px) {
    #sidebar {
        transform: translateX(-100%);
        box-shadow: none;
    }
    #sidebar.mobile-open {
        transform: translateX(0);
        box-shadow: 5px 0 25px rgba(0,0,0,0.2);
    }
}

.logo-area span {
    font-family: 'Poppins', 'Inter', sans-serif;
    font-weight: 800;
    letter-spacing: 1px;
    background: linear-gradient(135deg, #ffffff 0%, #e0e7ff 100%);
    -webkit-background-clip: text;
    background-clip: text;
    color: transparent;
    text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.3);
    transition: all 0.3s ease;
}

.logo-area:hover span {
    text-shadow: 0 0 8px rgba(59,130,246,0.8);
    letter-spacing: 2px;
}
</style>

<script>
document.addEventListener("DOMContentLoaded", () => {
    const collapseBtn = document.getElementById("collapseBtn");
    const sidebar = document.getElementById("sidebar");
    const texts = document.querySelectorAll(".sidebar-text");
    const logo = document.querySelector(".logo-area");

    const userBtn = document.getElementById("userMenuBtn");
    const userDropdown = document.getElementById("userDropdown");
    const userArrow = document.getElementById("userArrow");

    const settingsBtn = document.getElementById("settingsBtn");
    const settingsDropdown = document.getElementById("settingsDropdown");
    const settingsArrow = document.getElementById("settingsArrow");

    // Collapse sidebar (preserve original behavior)
    collapseBtn.addEventListener("click", () => {
        sidebar.classList.toggle("w-64");
        sidebar.classList.toggle("w-20");

        // Automatically hide dropdowns when collapsed
        if (userDropdown && !sidebar.classList.contains("w-64")) {
            userDropdown.classList.add("hidden");
            userArrow.classList.remove("rotate-180");
        }
        if (settingsDropdown && !sidebar.classList.contains("w-64")) {
            settingsDropdown.classList.add("hidden");
            settingsArrow.classList.remove("rotate-180");
        }

        // Dispatch event for main content adjustment
        window.dispatchEvent(new Event('sidebarToggle'));
    });

    // User Management dropdown toggle
    if (userBtn) {
        userBtn.addEventListener("click", () => {
            if (sidebar.classList.contains("w-64")) {
                userDropdown.classList.toggle("hidden");
                userArrow.classList.toggle("rotate-180");
            }
        });
    }

    // Settings dropdown toggle
    if (settingsBtn) {
        settingsBtn.addEventListener("click", () => {
            if (sidebar.classList.contains("w-64")) {
                settingsDropdown.classList.toggle("hidden");
                settingsArrow.classList.toggle("rotate-180");
            }
        });
    }

    // Keep dropdowns open if current page is inside them
    const currentPath = window.location.pathname;
    if (currentPath.includes('admin_approval.php') || currentPath.includes('approved_users.php') || currentPath.includes('rejected_user.php')) {
        if (userDropdown) {
            userDropdown.classList.remove("hidden");
            userArrow.classList.add("rotate-180");
        }
    }
    if (currentPath.includes('audit_logs.php')) {
        if (settingsDropdown) {
            settingsDropdown.classList.remove("hidden");
            settingsArrow.classList.add("rotate-180");
        }
    }

    // Mobile menu toggle (optional)
    const createMobileToggle = () => {
        if (window.innerWidth <= 768 && !document.querySelector('.mobile-menu-toggle')) {
            const menuToggle = document.createElement('button');
            menuToggle.className = 'mobile-menu-toggle fixed top-4 left-4 z-50 text-gray-700 hover:text-gray-900 text-xl bg-white p-2 rounded-lg shadow-lg';
            menuToggle.innerHTML = '<i class="fas fa-bars"></i>';
            document.body.appendChild(menuToggle);
            menuToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                sidebar.classList.toggle('mobile-open');
            });
        }
    };
    createMobileToggle();

    document.addEventListener('click', (event) => {
        if (window.innerWidth <= 768) {
            if (!sidebar.contains(event.target) && !event.target.closest('.mobile-menu-toggle')) {
                sidebar.classList.remove('mobile-open');
            }
        }
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 768) {
            sidebar.classList.remove('mobile-open');
        } else {
            createMobileToggle();
        }
    });
});
</script>