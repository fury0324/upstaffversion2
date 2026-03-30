<?php
$user_name = $_SESSION['firstname'] ?? 'Employee';
?>
<div class="bg-white border-b border-gray-200 shadow-sm">
    <div class="flex justify-between items-center px-6 py-4">
        <div class="flex items-center gap-4">
            <button id="mobileMenuBtn" class="md:hidden text-gray-600 hover:text-blue-600 text-xl">
                <i class="fas fa-bars"></i>
            </button>
            <h1 class="text-xl font-semibold text-gray-800">Employee Dashboard</h1>
        </div>

        <!-- Profile Dropdown -->
        <div class="relative" id="profileDropdown">
            <button onclick="toggleProfile()" class="flex items-center space-x-2 hover:bg-gray-50 p-2 rounded-lg transition-colors">
                <div class="w-8 h-8 rounded-full overflow-hidden bg-gradient-to-r from-blue-500 to-purple-600 flex items-center justify-center text-white font-bold">
                    <?php echo strtoupper(substr($user_name, 0, 1)); ?>
                </div>
                <span class="hidden md:inline font-medium text-gray-700"><?php echo htmlspecialchars($user_name); ?></span>
                <i id="chevronIcon" class="fas fa-chevron-down chevron-icon text-xs text-gray-500"></i>
            </button>

            <div id="profileMenu" class="absolute right-0 mt-2 w-48 bg-white rounded-lg shadow-xl border border-gray-200 z-50 hidden">
                <div class="p-3 border-b bg-gray-50">
                    <p class="text-sm font-semibold text-gray-800"><?php echo htmlspecialchars($user_name); ?></p>
                    <p class="text-xs text-gray-500">Employee</p>
                </div>
                <!-- Profile Information Modal Trigger -->
                <button onclick="openProfileModal()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-user mr-2"></i> Profile Information
                </button>
                <!-- Security Modal Trigger -->
                <button onclick="openSecurityModal()" class="block w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-gray-100">
                    <i class="fas fa-shield-alt mr-2"></i> Security
                </button>
                <div class="border-t my-1"></div>
                <a href="../logout.php" class="block px-4 py-2 text-sm text-red-600 hover:bg-red-50">
                    <i class="fas fa-sign-out-alt mr-2"></i> Logout
                </a>
            </div>
        </div>
    </div>
</div>

<style>
    @keyframes dropdownFadeIn {
        0% { opacity: 0; transform: scale(0.95) translateY(-10px); }
        100% { opacity: 1; transform: scale(1) translateY(0); }
    }
    .dropdown-animate { animation: dropdownFadeIn 0.2s ease-out forwards; }
    .chevron-icon { transition: transform 0.2s ease; }
    .chevron-icon.rotate-180 { transform: rotate(180deg); }
</style>

<script>
function toggleProfile() {
    const menu = document.getElementById('profileMenu');
    const chevron = document.getElementById('chevronIcon');
    if (menu.classList.contains('hidden')) {
        menu.classList.remove('hidden');
        menu.classList.add('dropdown-animate');
        chevron.classList.add('rotate-180');
        setTimeout(() => menu.classList.remove('dropdown-animate'), 200);
    } else {
        menu.classList.add('hidden');
        chevron.classList.remove('rotate-180');
    }
}

// Close dropdown when clicking outside
document.addEventListener('click', function(event) {
    const dropdown = document.getElementById('profileDropdown');
    const menu = document.getElementById('profileMenu');
    const chevron = document.getElementById('chevronIcon');
    if (dropdown && !dropdown.contains(event.target)) {
        if (menu && !menu.classList.contains('hidden')) {
            menu.classList.add('hidden');
            if (chevron) chevron.classList.remove('rotate-180');
        }
    }
});

// Mobile menu toggle
document.getElementById('mobileMenuBtn')?.addEventListener('click', function() {
    const sidebar = document.getElementById('sidebar');
    if (sidebar) sidebar.classList.toggle('mobile-open');
});
</script>