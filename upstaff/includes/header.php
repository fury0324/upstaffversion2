<header class="flex flex-col md:flex-row justify-between items-center bg-white shadow-lg px-4 md:px-6 py-3 sticky top-0 z-40">

    <!-- Mobile Menu Button (for sidebar toggle) -->
    <div class="flex items-center justify-between w-full md:w-auto mb-2 md:mb-0">
        <button id="mobileMenuBtn" class="md:hidden text-gray-600 hover:text-blue-600 text-xl mr-3">
            <i class="fas fa-bars"></i>
        </button>
        
        <!-- Page Title / Breadcrumb -->
        <div class="md:hidden font-semibold text-gray-800">
            <?php 
            $current_page = basename($_SERVER['PHP_SELF'], ".php");
            echo ucwords(str_replace('_', ' ', $current_page));
            ?>
        </div>
        
        <!-- Mobile Profile -->
        <div class="md:hidden flex items-center space-x-2">
            <button class="text-gray-600 hover:text-blue-600 text-lg relative" onclick="toggleNotifications()">
                <i class="fas fa-bell"></i>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center notification-badge">3</span>
            </button>
        </div>
    </div>

    <!-- Right Side Icons -->
    <div class="flex items-center space-x-4 order-2 mt-2 md:mt-0">

        <!-- Notification Dropdown -->
        <div class="relative" id="notificationDropdown">
            <button onclick="toggleNotifications()" class="text-gray-600 hover:text-blue-600 text-lg relative focus:outline-none">
                <i class="fas fa-bell"></i>
                <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-4 h-4 flex items-center justify-center notification-badge">3</span>
            </button>

            <!-- Notification Panel -->
            <div id="notificationPanel" class="absolute right-0 mt-2 w-80 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50">
                <div class="p-3 border-b bg-gray-50 flex justify-between items-center">
                    <h3 class="font-semibold text-gray-700">Notifications</h3>
                    <button onclick="markAllAsRead()" class="text-xs text-blue-600 hover:text-blue-800">Mark all as read</button>
                </div>
                <div class="max-h-96 overflow-y-auto">
                    <!-- Notification Items -->
                    <a href="#" class="block p-3 hover:bg-gray-50 border-b">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-yellow-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-user-clock text-yellow-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800"><span class="font-medium">New user registration</span> pending approval</p>
                                <p class="text-xs text-gray-500 mt-1">2 minutes ago</p>
                            </div>
                        </div>
                    </a>
                    <a href="#" class="block p-3 hover:bg-gray-50 border-b">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-green-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-check-circle text-green-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800"><span class="font-medium">Course completed</span> by John Doe</p>
                                <p class="text-xs text-gray-500 mt-1">1 hour ago</p>
                            </div>
                        </div>
                    </a>
                    <a href="#" class="block p-3 hover:bg-gray-50">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <i class="fas fa-chart-line text-blue-600 text-sm"></i>
                            </div>
                            <div>
                                <p class="text-sm text-gray-800"><span class="font-medium">Monthly report</span> is ready for review</p>
                                <p class="text-xs text-gray-500 mt-1">3 hours ago</p>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="p-2 border-t text-center">
                    <a href="notifications.php" class="text-sm text-blue-600 hover:text-blue-800">View all notifications</a>
                </div>
            </div>
        </div>

        <!-- Profile Dropdown -->
        <div class="relative" id="profileDropdown">
            <button onclick="toggleProfile()" class="flex items-center space-x-2 hover:bg-gray-50 p-2 rounded-lg transition-colors">
                <img src="https://i.pravatar.cc/40?u=<?php echo $_SESSION['user_id'] ?? 1; ?>" class="w-8 h-8 rounded-full border-2 border-blue-500">
                <span class="hidden md:inline font-medium text-gray-700"><?php echo $_SESSION['firstname'] ?? 'Admin'; ?></span>
                <i class="fas fa-chevron-down text-xs text-gray-500"></i>
            </button>

            <!-- Profile Dropdown Menu -->
            <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 hidden z-50">
                <div class="p-3 border-b bg-gray-50">
                    <p class="text-sm font-semibold text-gray-800"><?php echo $_SESSION['firstname'] ?? 'Admin'; ?> <?php echo $_SESSION['lastname'] ?? ''; ?></p>
                    <p class="text-xs text-gray-500"><?php echo $_SESSION['role'] ?? 'Administrator'; ?></p>
                </div>
                <a href="profile.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-user mr-2"></i> My Profile
                </a>
                <a href="settings.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-cog mr-2"></i> Settings
                </a>
                <a href="activity.php" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-history mr-2"></i> Activity Log
                </a>
                <div class="border-t my-1"></div>
                <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </div>

    </div>

</header>

<!-- Add this JavaScript for functionality -->
<script>
// Toggle notification panel
function toggleNotifications() {
    const panel = document.getElementById('notificationPanel');
    const profileMenu = document.getElementById('profileMenu');
    
    // Close profile if open
    if (profileMenu && !profileMenu.classList.contains('hidden')) {
        profileMenu.classList.add('hidden');
    }
    
    panel.classList.toggle('hidden');
}

// Toggle profile menu
function toggleProfile() {
    const menu = document.getElementById('profileMenu');
    const notificationPanel = document.getElementById('notificationPanel');
    
    // Close notifications if open
    if (notificationPanel && !notificationPanel.classList.contains('hidden')) {
        notificationPanel.classList.add('hidden');
    }
    
    menu.classList.toggle('hidden');
}

// Mark all notifications as read
function markAllAsRead() {
    // Update badge
    const badge = document.querySelector('.notification-badge');
    if (badge) {
        badge.style.display = 'none';
    }
    
    // Here you would typically make an AJAX call to update the database
    fetch('mark_notifications_read.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'all=true'
    });
    
    // Show feedback
    showNotification('All notifications marked as read', 'success');
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    const notificationDropdown = document.getElementById('notificationDropdown');
    const profileDropdown = document.getElementById('profileDropdown');
    const notificationPanel = document.getElementById('notificationPanel');
    const profileMenu = document.getElementById('profileMenu');
    
    if (notificationDropdown && !notificationDropdown.contains(event.target)) {
        if (notificationPanel) notificationPanel.classList.add('hidden');
    }
    
    if (profileDropdown && !profileDropdown.contains(event.target)) {
        if (profileMenu) profileMenu.classList.add('hidden');
    }
});

// Mobile menu toggle
document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
    const sidebar = document.querySelector('.sidebar'); // Adjust selector based on your sidebar
    if (sidebar) {
        sidebar.classList.toggle('hidden');
        sidebar.classList.toggle('mobile-show');
    }
});

// Global search functionality
const searchInput = document.getElementById('globalSearch');
const searchResults = document.getElementById('searchResults');
const searchResultsList = document.getElementById('searchResultsList');
let searchTimeout;

searchInput.addEventListener('input', function() {
    const query = this.value.trim();
    
    clearTimeout(searchTimeout);
    
    if (query.length < 2) {
        searchResults.classList.add('hidden');
        return;
    }
    
    searchTimeout = setTimeout(() => {
        // Show loading state
        searchResultsList.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center"><i class="fas fa-spinner fa-spin mr-2"></i>Searching...</div>';
        searchResults.classList.remove('hidden');
        
        // Perform search (AJAX call)
        fetch(`search.php?q=${encodeURIComponent(query)}`)
            .then(response => response.json())
            .then(data => {
                if (data.length > 0) {
                    let html = '';
                    data.forEach(item => {
                        html += `
                            <a href="${item.url}" class="block p-3 hover:bg-gray-50">
                                <div class="flex items-center gap-3">
                                    <div class="w-8 h-8 bg-${item.color}-100 rounded-full flex items-center justify-center">
                                        <i class="fas ${item.icon} text-${item.color}-600 text-sm"></i>
                                    </div>
                                    <div>
                                        <p class="text-sm font-medium text-gray-800">${item.title}</p>
                                        <p class="text-xs text-gray-500">${item.subtitle}</p>
                                    </div>
                                </div>
                            </a>
                        `;
                    });
                    searchResultsList.innerHTML = html;
                } else {
                    searchResultsList.innerHTML = '<div class="p-3 text-sm text-gray-500 text-center">No results found</div>';
                }
            })
            .catch(error => {
                searchResultsList.innerHTML = '<div class="p-3 text-sm text-red-500 text-center">Error searching</div>';
            });
    }, 300);
});

// Close search results when clicking outside
document.addEventListener('click', function(event) {
    if (searchInput && !searchInput.contains(event.target) && !searchResults.contains(event.target)) {
        searchResults.classList.add('hidden');
    }
});

// Show notification function
function showNotification(message, type = 'info') {
    const notification = document.createElement('div');
    notification.className = `fixed top-4 right-4 z-50 px-4 py-3 rounded-lg shadow-lg text-white transform transition-all duration-300 translate-x-0 flex items-center gap-2 ${
        type === 'success' ? 'bg-green-500' : 
        type === 'error' ? 'bg-red-500' : 
        'bg-blue-500'
    }`;
    notification.innerHTML = `
        <i class="fas ${type === 'success' ? 'fa-check-circle' : type === 'error' ? 'fa-exclamation-circle' : 'fa-info-circle'}"></i>
        <span>${message}</span>
    `;
    document.body.appendChild(notification);
    
    setTimeout(() => {
        notification.remove();
    }, 3000);
}

// Optional: Add keyboard shortcut (Ctrl+K) to focus search
document.addEventListener('keydown', function(e) {
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        searchInput.focus();
    }
});

// Update notification count periodically
setInterval(() => {
    fetch('get_notification_count.php')
        .then(response => response.json())
        .then(data => {
            const badge = document.querySelector('.notification-badge');
            if (badge) {
                if (data.count > 0) {
                    badge.textContent = data.count;
                    badge.style.display = 'flex';
                } else {
                    badge.style.display = 'none';
                }
            }
        });
}, 30000); // Update every 30 seconds
</script>

<!-- Add this CSS for better mobile experience -->
<style>
@media (max-width: 768px) {
    .sidebar.mobile-show {
        transform: translateX(0);
    }
    .notification-badge {
        font-size: 0.6rem;
        width: 1.2rem;
        height: 1.2rem;
    }
}

/* Smooth transitions */
#notificationPanel, #profileMenu, #searchResults {
    transition: opacity 0.2s ease, transform 0.2s ease;
}

/* Custom scrollbar for dropdowns */
#searchResults::-webkit-scrollbar,
#notificationPanel .overflow-y-auto::-webkit-scrollbar {
    width: 6px;
}
#searchResults::-webkit-scrollbar-track,
#notificationPanel .overflow-y-auto::-webkit-scrollbar-track {
    background: #f1f1f1;
}
#searchResults::-webkit-scrollbar-thumb,
#notificationPanel .overflow-y-auto::-webkit-scrollbar-thumb {
    background: #888;
    border-radius: 3px;
}
#searchResults::-webkit-scrollbar-thumb:hover,
#notificationPanel .overflow-y-auto::-webkit-scrollbar-thumb:hover {
    background: #555;
}
</style>